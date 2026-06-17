<?php

namespace App\Livewire;

use App\Models\Server;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Poll;
use Livewire\Component;

class ServerDashboard extends Component
{
    // Warning thresholds — adjust here to change all dashboard coloring
    const CPU_WARN    = 80.0;
    const MEM_WARN    = 85.0;
    const DISK_WARN   = 85.0;

    public array $expandedServers = [];

    public function toggleContainers(int $serverId): void
    {
        if (in_array($serverId, $this->expandedServers)) {
            $this->expandedServers = array_values(array_filter($this->expandedServers, fn ($id) => $id !== $serverId));
        } else {
            $this->expandedServers[] = $serverId;
        }
    }

    #[Poll(30000)]
    public function render()
    {
        $servers = Server::where('is_active', true)
            ->with('latestSnapshot')
            ->orderBy('name')
            ->get();

        return view('livewire.server-dashboard', [
            'servers'      => $servers,
            'summary'      => $this->buildSummary($servers),
            'lastUpdated'  => Carbon::now(),
        ]);
    }

    private function buildSummary(Collection $servers): array
    {
        $onlineServers = $servers->filter(fn ($s) => $s->latestSnapshot?->online);

        $cpuValues  = $onlineServers->map(fn ($s) => $s->latestSnapshot->cpu_percent)->filter()->values();
        $memValues  = $onlineServers->map(fn ($s) => $s->latestSnapshot->memory_percent)->filter()->values();

        $warnings = $servers->filter(fn ($s) => $this->hasWarning($s))->count();

        return [
            'total'    => $servers->count(),
            'online'   => $onlineServers->count(),
            'offline'  => $servers->count() - $onlineServers->count(),
            'warnings' => $warnings,
            'avg_cpu'  => $cpuValues->isNotEmpty() ? round($cpuValues->avg(), 1) : null,
            'avg_mem'  => $memValues->isNotEmpty() ? round($memValues->avg(), 1) : null,
        ];
    }

    public function hasWarning(Server $server): bool
    {
        $snap = $server->latestSnapshot;
        if (! $snap || ! $snap->online) {
            return false;
        }

        $diskWarning = collect($snap->disks ?? [])->contains(fn ($d) => ($d['percent'] ?? 0) > self::DISK_WARN);

        return ($snap->cpu_percent    !== null && $snap->cpu_percent    > self::CPU_WARN)
            || ($snap->memory_percent !== null && $snap->memory_percent > self::MEM_WARN)
            || $diskWarning;
    }

    public function metricColor(float|null $value, float $warn = 70.0, float $crit = 85.0): string
    {
        if ($value === null) {
            return 'gray';
        }
        if ($value >= $crit) {
            return 'red';
        }
        if ($value >= $warn) {
            return 'amber';
        }
        return 'green';
    }

    public function formatUptime(int|null $seconds): string
    {
        if ($seconds === null) {
            return '—';
        }
        $days    = intdiv($seconds, 86400);
        $hours   = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        if ($days > 0) {
            return "{$days}d {$hours}h";
        }
        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }
        return "{$minutes}m";
    }
}
