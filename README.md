# Server Monitor

A web dashboard that polls lightweight Python agents running on each server and
displays CPU, memory, disk, load, uptime, and Docker container stats.

- **Dashboard** — Laravel + Livewire, served via Nginx in Docker. UI at `http://localhost:8082/monitor`.
- **Agent** — a small Flask app (`agent/server_agent.py`) that exposes a single
  `/stats` endpoint over HTTP. One agent per monitored server.

```
┌──────────────┐   poll every 180s    ┌─────────────────────┐
│  Dashboard   │  ──── HTTP GET ────►  │  Agent  :8888       │
│  (Docker)    │   Bearer <token>      │  /stats (Flask)     │
│  :8082       │  ◄──── JSON ───────   │  psutil + docker SDK │
└──────────────┘                       └─────────────────────┘
```

The dashboard's `scheduler` container runs `php artisan monitor:poll` every 180s,
fetching all active servers in parallel and storing a snapshot. Snapshots older
than 24h are pruned automatically.

---

## Part 1 — Deploy the Agent

The agent runs on **each server you want to monitor** as a `systemd` service on
port **8888**. It needs Python 3, and the Docker SDK only if you want container
stats (it degrades gracefully without Docker).

### Why these steps

The agent authenticates every request against a shared `MONITOR_TOKEN` (Bearer
token, see [`server_agent.py`](agent/server_agent.py#L117-L121)). The **same token**
must exist on the dashboard and on every agent — that's how the dashboard proves
it's allowed to read stats. Keep the token file `chmod 600` because anyone who can
read it can pull full host + container telemetry.

### 1. Copy files to the server

```bash
scp agent/server_agent.py \
    agent/requirements.txt \
    agent/server-monitor-agent.service \
    joshe@<server-ip>:/tmp/
```

### 2. SSH in and install dependencies

```bash
ssh joshe@<server-ip>

# --break-system-packages is needed on newer Debian/Ubuntu (PEP 668 external-managed env).
# Drop `docker` from the list if the host has no Docker — the agent skips container stats.
sudo pip3 install flask psutil docker --break-system-packages --ignore-installed blinker
```

### 3. Install the agent

```bash
sudo mkdir -p /opt/server-monitor-agent
sudo cp /tmp/server_agent.py /opt/server-monitor-agent/
```

### 4. Set the token

Use the **same** `MONITOR_TOKEN` value as the dashboard's `.env`.

```bash
sudo tee /etc/server-monitor-agent.env >/dev/null <<'EOF'
MONITOR_TOKEN=<your-shared-token>
EOF
sudo chmod 600 /etc/server-monitor-agent.env
```

### 5. Install and start the service

```bash
sudo cp /tmp/server-monitor-agent.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now server-monitor-agent
```

### 6. Verify

```bash
# On the server — should return JSON:
curl -s -H "Authorization: Bearer <your-shared-token>" http://localhost:8888/stats

# Service health / logs:
systemctl status server-monitor-agent
journalctl -u server-monitor-agent -f
```

A request **without** a valid token returns `401`. That's expected.

> **Network note:** The PCSD SSL-inspection proxy intercepts unfamiliar ports.
> Ports 9100/9101 are blocked; **8888 passes** but adds a ~54s delay on first
> connect. The poll timeout is set to 120s to absorb this — don't lower it below
> ~60s for proxied servers.

---

## Part 2 — Run the Dashboard

The dashboard runs in Docker Compose: a PHP-FPM `app`, an `nginx` frontend, a
`mariadb` database, and a `scheduler` that polls on a loop.

### 1. Configure environment

```bash
cp .env.example .env
```

Edit [`.env`](.env.example) and set a strong shared token plus DB credentials:

```env
DB_USERNAME=laravel
DB_PASSWORD=<change-me>
DB_ROOT_PASSWORD=<change-me>
MONITOR_TOKEN=<your-shared-token>   # must match every agent
```

### 2. Define which servers to monitor

Edit [`servers.json`](servers.json). Each entry is seeded into the DB on first
boot (only if the `servers` table is empty — see [`entrypoint.sh`](entrypoint.sh)):

```json
[
    { "name": "Web-PreDeploy", "host": "158.91.1.130", "port": 8888 },
    { "name": "dock-host1",    "host": "158.91.1.128", "port": 8888 }
]
```

### 3. Build and start

```bash
docker compose up -d --build
```

On first boot the `app` container runs migrations and seeds servers from
`servers.json`. Open the dashboard:

- **Server stats:** http://localhost:8082/monitor
- **Port map:** http://localhost:8082/monitor/ports

### 4. Force a poll immediately (optional)

The scheduler polls every 180s. To poll right now:

```bash
docker compose exec app php artisan monitor:poll
```

---

## Adding a server later

`servers.json` is only auto-seeded when the table is empty. To add a server after
the first boot, either insert it directly or re-run the seeder:

```bash
docker compose exec app php artisan db:seed --class=ServerSeeder --force
```

The seeder uses `firstOrCreate` on `host`+`port`, so re-running it won't duplicate
existing servers — it only adds new ones from `servers.json`.

---

## What the agent reports

`GET /stats` (Bearer-authenticated) returns:

| Field            | Source                                              |
|------------------|-----------------------------------------------------|
| `hostname`       | `os.uname().nodename`                                |
| `uptime_seconds` | from `psutil.boot_time()`                            |
| `cpu_percent`    | `psutil.cpu_percent`                                 |
| `memory`         | total / used MB + percent                            |
| `disks`          | real partitions only (pseudo/virtual FS filtered)   |
| `load_avg`       | 1/5/15-minute load                                  |
| `containers`     | per-container status, health, uptime, ports, CPU, mem (Docker SDK; empty if no Docker) |

---

## Troubleshooting

| Symptom                                | Likely cause / fix                                                                 |
|----------------------------------------|------------------------------------------------------------------------------------|
| Server shows offline in dashboard      | Agent down, firewall, or token mismatch. Check `journalctl -u server-monitor-agent` and confirm `MONITOR_TOKEN` matches on both sides. |
| `curl` to agent returns `401`          | Wrong/missing Bearer token.                                                        |
| Slow first poll (~1 min) on a server   | Proxy delay on port 8888 — expected. Don't shorten the poll timeout.               |
| Containers list empty                  | Host has no Docker, or `docker` Python package not installed, or service can't reach the Docker socket (service runs as `root`, which it needs). |
| New server in `servers.json` not showing | Table already seeded — re-run the seeder (see "Adding a server later").           |

> **Security:** The `/monitor` route has **no auth** yet (see the `TODO` in
> [`routes/web.php`](src/routes/web.php)). Don't expose port 8082 to a shared
> network until auth middleware is added.
