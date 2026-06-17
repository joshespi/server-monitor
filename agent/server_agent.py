import os
import time
import psutil
from flask import Flask, jsonify, request, abort

try:
    import docker
    DOCKER_AVAILABLE = True
except ImportError:
    DOCKER_AVAILABLE = False

app = Flask(__name__)

MONITOR_TOKEN = os.environ.get("MONITOR_TOKEN", "changeme")


def get_uptime_seconds():
    return int(time.time() - psutil.boot_time())


def get_container_stats(container):
    try:
        stats = container.stats(stream=False)

        cpu_delta = (
            stats["cpu_stats"]["cpu_usage"]["total_usage"]
            - stats["precpu_stats"]["cpu_usage"]["total_usage"]
        )
        system_delta = (
            stats["cpu_stats"]["system_cpu_usage"]
            - stats["precpu_stats"]["system_cpu_usage"]
        )
        num_cpus = stats["cpu_stats"].get("online_cpus") or len(
            stats["cpu_stats"]["cpu_usage"].get("percpu_usage", [1])
        )
        cpu_percent = (cpu_delta / system_delta) * num_cpus * 100 if system_delta > 0 else 0.0

        mem_usage = stats.get("memory_stats", {}).get("usage", 0)
        mem_mb = mem_usage / 1024 / 1024

        return round(cpu_percent, 2), round(mem_mb, 2)
    except Exception:
        return 0.0, 0.0


def get_container_health(container):
    try:
        return container.attrs["State"]["Health"]["Status"]
    except (KeyError, TypeError):
        return "none"


def get_container_uptime(container):
    try:
        started = container.attrs["State"]["StartedAt"]
        # e.g. "2024-01-01T12:00:00.000000000Z"
        from datetime import datetime, timezone
        started_dt = datetime.fromisoformat(started.replace("Z", "+00:00"))
        delta = datetime.now(timezone.utc) - started_dt
        days = delta.days
        hours, remainder = divmod(delta.seconds, 3600)
        minutes = remainder // 60
        if days > 0:
            return f"{days} day{'s' if days != 1 else ''}"
        if hours > 0:
            return f"{hours} hour{'s' if hours != 1 else ''}"
        return f"{minutes} minute{'s' if minutes != 1 else ''}"
    except Exception:
        return "unknown"


def get_containers():
    if not DOCKER_AVAILABLE:
        return []

    try:
        client = docker.from_env()
        containers = client.containers.list(all=True)
        result = []

        for c in containers:
            cpu_pct, mem_mb = get_container_stats(c) if c.status == "running" else (0.0, 0.0)

            ports_raw = c.ports or {}
            seen_ports = set()
            ports = []
            for container_port, bindings in ports_raw.items():
                if bindings:
                    for b in bindings:
                        entry = f"{b['HostPort']}:{container_port.split('/')[0]}"
                        if entry not in seen_ports:
                            seen_ports.add(entry)
                            ports.append(entry)
                else:
                    entry = container_port.split('/')[0]
                    if entry not in seen_ports:
                        seen_ports.add(entry)
                        ports.append(entry)

            result.append({
                "id": c.short_id,
                "name": c.name,
                "image": c.image.tags[0] if c.image.tags else c.image.short_id,
                "status": c.status,
                "health": get_container_health(c),
                "uptime": get_container_uptime(c),
                "ports": ports,
                "cpu_percent": cpu_pct,
                "memory_mb": mem_mb,
            })

        return result
    except Exception:
        return []


@app.before_request
def check_auth():
    auth = request.headers.get("Authorization", "")
    if not auth.startswith("Bearer ") or auth[len("Bearer "):] != MONITOR_TOKEN:
        abort(401)


def get_disks():
    disks = []
    seen = set()
    for part in psutil.disk_partitions():
        # Skip pseudo/virtual filesystems and unmanageable system partitions
        if part.fstype in ("", "squashfs", "tmpfs", "devtmpfs", "devfs", "overlay", "vfat", "efi"):
            continue
        if part.mountpoint in ("/boot", "/boot/efi", "/boot/firmware"):
            continue
        if part.mountpoint in seen:
            continue
        seen.add(part.mountpoint)
        try:
            usage = psutil.disk_usage(part.mountpoint)
            disks.append({
                "mountpoint": part.mountpoint,
                "device":     part.device,
                "fstype":     part.fstype,
                "total_gb":   round(usage.total / 1024 / 1024 / 1024, 1),
                "used_gb":    round(usage.used / 1024 / 1024 / 1024, 1),
                "percent":    round(usage.used / usage.total * 100, 1),
            })
        except (PermissionError, OSError):
            continue
    return disks


@app.route("/stats")
def stats():
    cpu = psutil.cpu_percent(interval=0.5)
    mem = psutil.virtual_memory()
    load = list(os.getloadavg())

    return jsonify({
        "hostname": os.uname().nodename,
        "uptime_seconds": get_uptime_seconds(),
        "cpu_percent": cpu,
        "memory": {
            "total_mb": round(mem.total / 1024 / 1024, 1),
            "used_mb": round(mem.used / 1024 / 1024, 1),
            "percent": mem.percent,
        },
        "disks": get_disks(),
        "load_avg": [round(x, 2) for x in load],
        "containers": get_containers(),
    })


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=8888)
