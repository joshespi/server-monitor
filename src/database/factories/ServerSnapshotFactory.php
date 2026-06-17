<?php

namespace Database\Factories;

use App\Models\Server;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServerSnapshotFactory extends Factory
{
    public function definition(): array
    {
        return [
            'server_id'       => Server::factory(),
            'online'          => true,
            'cpu_percent'     => $this->faker->randomFloat(1, 0, 100),
            'memory_percent'  => $this->faker->randomFloat(1, 0, 100),
            'memory_used_mb'  => 4096,
            'memory_total_mb' => 16384,
            'disks'           => [['mountpoint' => '/', 'percent' => 50.0, 'used_gb' => 100.0, 'total_gb' => 200.0]],
            'load_avg'        => [0.1, 0.2, 0.3],
            'uptime_seconds'  => 86400,
            'containers'      => [],
        ];
    }

    public function offline(): static
    {
        return $this->state(['online' => false]);
    }
}
