<?php

namespace App\Console\Commands;

use App\Models\Server;
use App\Models\ServerSnapshot;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class MonitorPoll extends Command
{
    protected $signature = 'monitor:poll';
    protected $description = 'Poll all active servers concurrently and store snapshots';

    public function handle(): void
    {
        $servers = Server::where('is_active', true)->get();

        // Build a pool request per server — all fire in parallel
        $responses = Http::pool(function ($pool) use ($servers) {
            return $servers->map(function ($server) use ($pool) {
                return $pool->as($server->id)
                    ->withToken($server->token)
                    ->timeout(120)
                    ->get("http://{$server->host}:{$server->port}/stats");
            })->all();
        });

        foreach ($servers as $server) {
            $response = $responses[$server->id] ?? null;

            try {
                if (! $response || $response instanceof \Throwable || ! $response->successful()) {
                    $this->recordOffline($server);
                    continue;
                }

                $data = $response->json();

                ServerSnapshot::create([
                    'server_id'       => $server->id,
                    'online'          => true,
                    'cpu_percent'     => $data['cpu_percent'] ?? null,
                    'memory_percent'  => $data['memory']['percent'] ?? null,
                    'memory_used_mb'  => $data['memory']['used_mb'] ?? null,
                    'memory_total_mb' => $data['memory']['total_mb'] ?? null,
                    'disks'           => $data['disks'] ?? [],
                    'load_avg'        => $data['load_avg'] ?? null,
                    'uptime_seconds'  => $data['uptime_seconds'] ?? null,
                    'containers'      => $data['containers'] ?? [],
                ]);
            } catch (\Exception $e) {
                $this->error("{$server->name}: " . $e->getMessage());
                $this->recordOffline($server);
            }
        }

        // Prune snapshots older than 24 hours to keep the table lean
        ServerSnapshot::where('created_at', '<', Carbon::now()->subDay())->delete();
    }

    private function recordOffline(Server $server): void
    {
        ServerSnapshot::create([
            'server_id' => $server->id,
            'online'    => false,
        ]);
    }
}
