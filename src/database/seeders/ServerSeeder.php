<?php

namespace Database\Seeders;

use App\Models\Server;
use Illuminate\Database\Seeder;

class ServerSeeder extends Seeder
{
    public function run(): void
    {
        $token = env('MONITOR_TOKEN');
        $path  = '/config/servers.json';

        if (! file_exists($path)) {
            $this->command->error("servers.json not found at {$path}");
            return;
        }

        $servers = json_decode(file_get_contents($path), true);

        foreach ($servers as $server) {
            Server::firstOrCreate(
                ['host' => $server['host'], 'port' => $server['port']],
                [
                    'name'      => $server['name'],
                    'host'      => $server['host'],
                    'port'      => $server['port'],
                    'token'     => $token,
                    'is_active' => true,
                ]
            );
        }
    }
}
