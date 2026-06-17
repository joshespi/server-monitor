<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Infrastructure Tools</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-950 text-gray-100 min-h-screen flex items-center justify-center p-6">

    <div class="w-full max-w-md">
        <div class="flex items-center gap-3 mb-10">
            <svg class="w-7 h-7 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M5 12H3m18 0h-2M12 5V3m0 18v-2M6.343 6.343 4.929 4.929m14.142 14.142-1.414-1.414M17.657 6.343l1.414-1.414M4.929 19.071l1.414-1.414M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z"/>
            </svg>
            <h1 class="text-2xl font-semibold tracking-wide text-white">infrastructure tools</h1>
        </div>

        <nav class="space-y-3">
            <a href="/monitor"
               class="flex items-center justify-between bg-gray-900 border border-gray-800 rounded-xl px-5 py-4 hover:border-indigo-600 hover:bg-gray-800/60 transition-all group">
                <div>
                    <p class="font-medium text-white group-hover:text-indigo-300 transition-colors">Server Monitor</p>
                    <p class="text-sm text-gray-500 mt-0.5">Live stats, containers, and disk usage across all hosts</p>
                </div>
                <svg class="w-4 h-4 text-gray-600 group-hover:text-indigo-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>

            <a href="/monitor/ports"
               class="flex items-center justify-between bg-gray-900 border border-gray-800 rounded-xl px-5 py-4 hover:border-indigo-600 hover:bg-gray-800/60 transition-all group">
                <div>
                    <p class="font-medium text-white group-hover:text-indigo-300 transition-colors">Port Lookup</p>
                    <p class="text-sm text-gray-500 mt-0.5">Search which container is using a port across all servers</p>
                </div>
                <svg class="w-4 h-4 text-gray-600 group-hover:text-indigo-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </nav>
    </div>

</body>
</html>
