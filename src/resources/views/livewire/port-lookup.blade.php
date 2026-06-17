<div class="min-h-screen bg-gray-950 text-gray-100 p-6">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="/monitor" class="text-gray-500 hover:text-gray-300 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h1 class="text-xl font-semibold tracking-wide text-white">port lookup</h1>
        </div>
        <span class="text-xs text-gray-500">{{ count($rows) }} {{ count($rows) === 1 ? 'result' : 'results' }}</span>
    </div>

    {{-- Search --}}
    <div class="mb-6">
        <input
            type="text"
            wire:model.live="query"
            placeholder="Search by port, container, server, or image…"
            autofocus
            class="w-full bg-gray-900 border border-gray-700 rounded-xl px-4 py-3 text-white placeholder-gray-600 focus:outline-none focus:border-indigo-500 transition-colors"
        />
    </div>

    {{-- Results --}}
    @if (count($rows) > 0)
        <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs text-gray-500 uppercase border-b border-gray-800">
                        <th class="text-left px-4 py-3 font-medium">Port</th>
                        <th class="text-left px-4 py-3 font-medium">Server</th>
                        <th class="text-left px-4 py-3 font-medium">Container</th>
                        <th class="text-left px-4 py-3 font-medium">Image</th>
                        <th class="text-left px-4 py-3 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800/60">
                    @foreach ($rows as $row)
                        @php
                            $statusPill = match($row['status']) {
                                'running' => 'bg-green-900/50 text-green-300 border-green-800/50',
                                'exited'  => 'bg-red-900/50 text-red-300 border-red-800/50',
                                'paused'  => 'bg-amber-900/50 text-amber-300 border-amber-800/50',
                                default   => 'bg-gray-800 text-gray-400 border-gray-700',
                            };
                        @endphp
                        <tr class="hover:bg-gray-800/40 transition-colors">
                            <td class="px-4 py-3 font-mono text-indigo-300 font-semibold">{{ $row['port'] }}</td>
                            <td class="px-4 py-3 text-gray-300">
                                {{ $row['server'] }}
                                <span class="text-gray-600 text-xs ml-1">{{ $row['host'] }}</span>
                            </td>
                            <td class="px-4 py-3 text-white font-medium">{{ $row['container'] }}</td>
                            <td class="px-4 py-3 text-gray-400 font-mono text-xs">{{ $row['image'] }}</td>
                            <td class="px-4 py-3">
                                <span class="text-xs border rounded px-1.5 py-0.5 {{ $statusPill }}">
                                    {{ $row['status'] }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @elseif (trim($query) !== '')
        <div class="text-center py-16 text-gray-600">
            <p class="text-lg">No results for "{{ $query }}"</p>
        </div>
    @else
        <div class="text-center py-16 text-gray-600">
            <p class="text-lg">Type a port number to search.</p>
        </div>
    @endif

</div>
