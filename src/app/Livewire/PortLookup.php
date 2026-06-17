<?php

namespace App\Livewire;

use App\Models\Server;
use Livewire\Component;

class PortLookup extends Component
{
    public string $query = '';

    public function render()
    {
        $servers = Server::where('is_active', true)
            ->with('latestSnapshot')
            ->orderBy('name')
            ->get();

        // Flatten all container ports across all servers into searchable rows
        $rows = [];
        foreach ($servers as $server) {
            $snap = $server->latestSnapshot;
            if (! $snap || ! $snap->online) {
                continue;
            }
            foreach ($snap->containers ?? [] as $container) {
                $seen = [];
                foreach ($container['ports'] ?? [] as $port) {
                    if (in_array($port, $seen)) {
                        continue;
                    }
                    $seen[] = $port;
                    $rows[] = [
                        'server'    => $server->name,
                        'host'      => $server->host,
                        'container' => $container['name'] ?? '—',
                        'image'     => $container['image'] ?? '—',
                        'status'    => $container['status'] ?? '—',
                        'port'      => $port,
                    ];
                }
            }
        }

        // Filter by query — matches port number, container name, server name, or image
        $q = trim($this->query);
        if ($q !== '') {
            $rows = array_values(array_filter($rows, function ($row) use ($q) {
                return str_contains($row['port'], $q)
                    || str_contains(strtolower($row['container']), strtolower($q))
                    || str_contains(strtolower($row['server']), strtolower($q))
                    || str_contains(strtolower($row['image']), strtolower($q));
            }));
        }

        // Sort by host port number
        usort($rows, fn ($a, $b) => (int) $a['port'] <=> (int) $b['port']);

        return view('livewire.port-lookup', ['rows' => $rows]);
    }
}
