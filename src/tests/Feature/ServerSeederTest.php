<?php

namespace Tests\Feature;

use App\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServerSeederTest extends TestCase
{
    use RefreshDatabase;

    private string $jsonPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Write a temp servers.json for the seeder to read
        $this->jsonPath = sys_get_temp_dir() . '/servers_test.json';
        file_put_contents($this->jsonPath, json_encode([
            ['name' => 'host-a', 'host' => '10.0.0.1', 'port' => 8888],
            ['name' => 'host-b', 'host' => '10.0.0.2', 'port' => 8888],
        ]));
    }

    protected function tearDown(): void
    {
        @unlink($this->jsonPath);
        parent::tearDown();
    }

    public function test_seeder_creates_servers_from_json(): void
    {
        // Point the seeder at our temp file by overriding the path constant via env
        // The seeder reads /config/servers.json — symlink temp file there for the test
        $configPath = '/config/servers.json';
        $linked = false;

        if (! file_exists(dirname($configPath))) {
            @mkdir(dirname($configPath), 0755, true);
        }

        if (! file_exists($configPath)) {
            symlink($this->jsonPath, $configPath);
            $linked = true;
        }

        try {
            $this->seed(\Database\Seeders\ServerSeeder::class);

            $this->assertDatabaseCount('servers', 2);
            $this->assertDatabaseHas('servers', ['name' => 'host-a', 'host' => '10.0.0.1']);
            $this->assertDatabaseHas('servers', ['name' => 'host-b', 'host' => '10.0.0.2']);
        } finally {
            if ($linked) {
                @unlink($configPath);
            }
        }
    }

    public function test_seeder_is_idempotent(): void
    {
        $configPath = '/config/servers.json';
        $linked = false;

        if (! file_exists(dirname($configPath))) {
            @mkdir(dirname($configPath), 0755, true);
        }

        if (! file_exists($configPath)) {
            symlink($this->jsonPath, $configPath);
            $linked = true;
        }

        try {
            $this->seed(\Database\Seeders\ServerSeeder::class);
            $this->seed(\Database\Seeders\ServerSeeder::class);

            // Running twice should not create duplicates
            $this->assertDatabaseCount('servers', 2);
        } finally {
            if ($linked) {
                @unlink($configPath);
            }
        }
    }
}
