<div class="min-h-screen bg-gray-950 text-gray-100 p-6" wire:poll.60000ms>

    {{-- ===== TOP BAR ===== --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <svg class="w-7 h-7 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M5 12H3m18 0h-2M12 5V3m0 18v-2M6.343 6.343 4.929 4.929m14.142 14.142-1.414-1.414M17.657 6.343l1.414-1.414M4.929 19.071l1.414-1.414M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z"/>
            </svg>
            <h1 class="text-xl font-semibold tracking-wide text-white">infrastructure monitor</h1>
        </div>
        <div class="flex items-center gap-3 text-sm">
            <span class="inline-flex items-center gap-1.5 bg-green-900/40 text-green-300 border border-green-700/50 rounded-full px-3 py-1">
                <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
                {{ $summary['online'] }} online
            </span>
            @if ($summary['warnings'] > 0)
                <span class="inline-flex items-center gap-1.5 bg-amber-900/40 text-amber-300 border border-amber-700/50 rounded-full px-3 py-1">
                    <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                    {{ $summary['warnings'] }} warning{{ $summary['warnings'] !== 1 ? 's' : '' }}
                </span>
            @endif
            <a href="/monitor/ports" class="text-xs text-gray-500 hover:text-indigo-400 transition-colors">port lookup</a>
            <span class="text-gray-500 text-xs">updated {{ $lastUpdated->format('H:i:s') }}</span>
        </div>
    </div>

    {{-- ===== SUMMARY METRIC CARDS ===== --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-4">
            <p class="text-xs text-gray-500 uppercase tracking-widest mb-1">Total Servers</p>
            <p class="text-3xl font-bold text-white">{{ $summary['total'] }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ $summary['online'] }} online · {{ $summary['offline'] }} offline</p>
        </div>
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-4">
            <p class="text-xs text-gray-500 uppercase tracking-widest mb-1">Avg CPU</p>
            <p class="text-3xl font-bold text-white">
                {{ $summary['avg_cpu'] !== null ? $summary['avg_cpu'] . '%' : '—' }}
            </p>
            <p class="text-xs text-gray-500 mt-1">across online servers</p>
        </div>
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-4">
            <p class="text-xs text-gray-500 uppercase tracking-widest mb-1">Avg Memory</p>
            <p class="text-3xl font-bold text-white">
                {{ $summary['avg_mem'] !== null ? $summary['avg_mem'] . '%' : '—' }}
            </p>
            <p class="text-xs text-gray-500 mt-1">across online servers</p>
        </div>
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-4 {{ $summary['warnings'] > 0 ? 'border-amber-700/60' : '' }}">
            <p class="text-xs {{ $summary['warnings'] > 0 ? 'text-amber-400' : 'text-gray-500' }} uppercase tracking-widest mb-1">Alerts</p>
            <p class="text-3xl font-bold {{ $summary['warnings'] > 0 ? 'text-amber-300' : 'text-white' }}">
                {{ $summary['warnings'] }}
            </p>
            <p class="text-xs text-gray-500 mt-1">threshold breaches</p>
        </div>
    </div>

    {{-- ===== SERVER LIST ===== --}}
    <div class="space-y-4">
        @forelse ($servers as $server)
            @php
                $snap     = $server->latestSnapshot;
                $online   = $snap?->online ?? false;
                $warning  = $this->hasWarning($server);
                $cpuColor = $this->metricColor($snap?->cpu_percent);
                $memColor = $this->metricColor($snap?->memory_percent);

                $statusDot = match(true) {
                    ! $online  => 'bg-red-500',
                    $warning   => 'bg-amber-400',
                    default    => 'bg-green-400',
                };

                $cardBorder = match(true) {
                    ! $online  => 'border-red-900/50',
                    $warning   => 'border-amber-800/50',
                    default    => 'border-gray-800',
                };

                $colorClass = fn(string $c) => match($c) {
                    'red'   => ['bar' => 'bg-red-500',   'text' => 'text-red-400'],
                    'amber' => ['bar' => 'bg-amber-400', 'text' => 'text-amber-300'],
                    'green' => ['bar' => 'bg-green-500', 'text' => 'text-green-400'],
                    default => ['bar' => 'bg-gray-600',  'text' => 'text-gray-400'],
                };

            @endphp

            <div class="bg-gray-900 border {{ $cardBorder }} rounded-xl px-4 py-3">

                {{-- Server header row --}}
                <div class="flex items-center gap-3 mb-3">
                    <span class="w-2.5 h-2.5 rounded-full {{ $statusDot }} {{ $online ? 'animate-pulse' : '' }}"></span>
                    <span class="font-semibold text-white">{{ $server->name }}</span>
                    <span class="text-gray-500 text-sm">{{ $server->host }}:{{ $server->port }}</span>
                    @if (! $online && $snap)
                        <span class="text-xs bg-red-900/50 text-red-300 border border-red-800/50 rounded px-2 py-0.5">offline</span>
                    @elseif ($warning)
                        <span class="text-xs bg-amber-900/50 text-amber-300 border border-amber-800/50 rounded px-2 py-0.5">warning</span>
                    @endif
                    @if (! $snap)
                        <span class="text-xs bg-gray-800 text-gray-400 border border-gray-700 rounded px-2 py-0.5">never polled</span>
                    @endif
                </div>

                @if ($online && $snap)
                    {{-- Metrics + Disks in one row --}}
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-2 mb-2">

                        {{-- CPU --}}
                        @php $c = $colorClass($cpuColor); @endphp
                        <div class="bg-gray-800/60 rounded-lg px-3 py-2">
                            <p class="text-xs text-gray-500">CPU</p>
                            <p class="text-base font-semibold {{ $c['text'] }}">{{ $snap->cpu_percent !== null ? round($snap->cpu_percent, 1) . '%' : '—' }}</p>
                            <div class="mt-1 h-1 bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full {{ $c['bar'] }} rounded-full" style="width: {{ min($snap->cpu_percent ?? 0, 100) }}%"></div>
                            </div>
                        </div>

                        {{-- Memory --}}
                        @php $c = $colorClass($memColor); @endphp
                        <div class="bg-gray-800/60 rounded-lg px-3 py-2">
                            <p class="text-xs text-gray-500">Memory</p>
                            <p class="text-base font-semibold {{ $c['text'] }}">{{ $snap->memory_percent !== null ? round($snap->memory_percent, 1) . '%' : '—' }}</p>
                            <div class="mt-1 h-1 bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full {{ $c['bar'] }} rounded-full" style="width: {{ min($snap->memory_percent ?? 0, 100) }}%"></div>
                            </div>
                        </div>

                        {{-- Load Average --}}
                        <div class="bg-gray-800/60 rounded-lg px-3 py-2">
                            <p class="text-xs text-gray-500">Load</p>
                            @if ($snap->load_avg && count($snap->load_avg) === 3)
                                <p class="text-base font-semibold text-gray-200">{{ $snap->load_avg[0] }}</p>
                                <p class="text-xs text-gray-600">{{ $snap->load_avg[1] }} · {{ $snap->load_avg[2] }}</p>
                            @else
                                <p class="text-base font-semibold text-gray-500">—</p>
                            @endif
                        </div>

                        {{-- Uptime --}}
                        <div class="bg-gray-800/60 rounded-lg px-3 py-2">
                            <p class="text-xs text-gray-500">Uptime</p>
                            <p class="text-base font-semibold text-gray-200">{{ $this->formatUptime($snap->uptime_seconds) }}</p>
                        </div>

                        {{-- Disks inline --}}
                        @foreach ($snap->disks ?? [] as $disk)
                            @php $c = $colorClass($this->metricColor($disk['percent'] ?? null)); @endphp
                            <div class="bg-gray-800/60 rounded-lg px-3 py-2">
                                <p class="text-xs text-gray-500 truncate" title="{{ $disk['mountpoint'] }}">{{ $disk['mountpoint'] }}</p>
                                <p class="text-base font-semibold {{ $c['text'] }}">{{ $disk['percent'] ?? '—' }}%</p>
                                <div class="mt-1 h-1 bg-gray-700 rounded-full overflow-hidden">
                                    <div class="h-full {{ $c['bar'] }} rounded-full" style="width: {{ min($disk['percent'] ?? 0, 100) }}%"></div>
                                </div>
                            </div>
                        @endforeach

                    </div>

                    {{-- Docker containers --}}
                    <div class="border-t border-gray-800 pt-3">
                        <button wire:click="toggleContainers({{ $server->id }})"
                                class="flex items-center gap-2 text-xs text-gray-500 uppercase tracking-widest hover:text-gray-300 transition-colors w-full text-left">
                            <svg class="w-3.5 h-3.5 transition-transform duration-200 {{ in_array($server->id, $expandedServers) ? 'rotate-90' : '' }}"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                            Containers ({{ count($snap->containers ?? []) }})
                        </button>

                        @if (in_array($server->id, $expandedServers))
                            @if (! empty($snap->containers))
                                <div class="overflow-x-auto mt-3">
                                    <table class="w-full text-sm">
                                        <thead>
                                            <tr class="text-xs text-gray-600 uppercase">
                                                <th class="text-left pb-2 pr-4 font-medium">Name</th>
                                                <th class="text-left pb-2 pr-4 font-medium">Image</th>
                                                <th class="text-left pb-2 pr-4 font-medium">Status</th>
                                                <th class="text-left pb-2 pr-4 font-medium">Health</th>
                                                <th class="text-left pb-2 pr-4 font-medium">Ports</th>
                                                <th class="text-right pb-2 pr-4 font-medium">CPU</th>
                                                <th class="text-right pb-2 font-medium">Mem</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-800/60">
                                            @foreach ($snap->containers as $container)
                                                @php
                                                    $statusPill = match($container['status'] ?? '') {
                                                        'running' => 'bg-green-900/50 text-green-300 border-green-800/50',
                                                        'exited'  => 'bg-red-900/50 text-red-300 border-red-800/50',
                                                        'paused'  => 'bg-amber-900/50 text-amber-300 border-amber-800/50',
                                                        default   => 'bg-gray-800 text-gray-400 border-gray-700',
                                                    };
                                                    $healthPill = match($container['health'] ?? 'none') {
                                                        'healthy'   => 'bg-green-900/50 text-green-300 border-green-800/50',
                                                        'unhealthy' => 'bg-red-900/50 text-red-300 border-red-800/50',
                                                        'starting'  => 'bg-amber-900/50 text-amber-300 border-amber-800/50',
                                                        default     => 'bg-gray-800/50 text-gray-500 border-gray-700',
                                                    };
                                                @endphp
                                                <tr>
                                                    <td class="py-2 pr-4 text-white font-medium">{{ $container['name'] ?? '—' }}</td>
                                                    <td class="py-2 pr-4 text-gray-400 font-mono text-xs">{{ $container['image'] ?? '—' }}</td>
                                                    <td class="py-2 pr-4">
                                                        <span class="text-xs border rounded px-1.5 py-0.5 {{ $statusPill }}">
                                                            {{ $container['status'] ?? '—' }}
                                                        </span>
                                                    </td>
                                                    <td class="py-2 pr-4">
                                                        <span class="text-xs border rounded px-1.5 py-0.5 {{ $healthPill }}">
                                                            {{ $container['health'] ?? 'none' }}
                                                        </span>
                                                    </td>
                                                    <td class="py-2 pr-4 text-gray-400 font-mono text-xs">
                                                        {{ ! empty($container['ports']) ? implode(', ', $container['ports']) : '—' }}
                                                    </td>
                                                    <td class="py-2 pr-4 text-right text-gray-300">{{ isset($container['cpu_percent']) ? round($container['cpu_percent'], 1) . '%' : '—' }}</td>
                                                    <td class="py-2 text-right text-gray-300">{{ isset($container['memory_mb']) ? round($container['memory_mb'], 0) . ' MB' : '—' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-xs text-gray-600 mt-3">no containers</p>
                            @endif
                        @endif
                    </div>

                @elseif ($snap && ! $online)
                    <div class="flex items-center gap-2 text-red-400 text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Host unreachable — last seen {{ $snap->created_at->diffForHumans() }}
                    </div>
                @endif

            </div>
        @empty
            <div class="text-center py-16 text-gray-600">
                <p class="text-lg">No servers configured.</p>
                <p class="text-sm mt-1">Run <code class="bg-gray-800 px-1.5 py-0.5 rounded text-gray-400">php artisan db:seed --class=ServerSeeder</code> to add example servers.</p>
            </div>
        @endforelse
    </div>

</div>
