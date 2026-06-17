<?php

namespace Tests\Feature;

use App\Models\Server;
use App\Models\ServerSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MonitorPollTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_response_stores_online_snapshot(): void
    {
        $server = Server::factory()->create(['host' => '10.0.0.1', 'port' => 8888, 'token' => 'abc']);

        Http::fake([
            'http://10.0.0.1:8888/stats' => Http::response([
                'cpu_percent' => 12.5,
                'memory'      => ['percent' => 55.0, 'used_mb' => 4096, 'total_mb' => 8192],
                'disks'       => [['mountpoint' => '/', 'percent' => 40.0, 'used_gb' => 40.0, 'total_gb' => 100.0]],
                'load_avg'    => [0.1, 0.2, 0.3],
                'uptime_seconds' => 3600,
                'containers'  => [],
            ], 200),
        ]);

        $this->artisan('monitor:poll')->assertSuccessful();

        $snapshot = ServerSnapshot::where('server_id', $server->id)->latest()->first();

        $this->assertNotNull($snapshot);
        $this->assertTrue($snapshot->online);
        $this->assertEquals(12.5, $snapshot->cpu_percent);
        $this->assertEquals(55.0, $snapshot->memory_percent);
    }

    public function test_failed_response_records_server_offline(): void
    {
        $server = Server::factory()->create(['host' => '10.0.0.2', 'port' => 8888]);

        Http::fake([
            'http://10.0.0.2:8888/stats' => Http::response(null, 500),
        ]);

        $this->artisan('monitor:poll')->assertSuccessful();

        $snapshot = ServerSnapshot::where('server_id', $server->id)->latest()->first();

        $this->assertNotNull($snapshot);
        $this->assertFalse($snapshot->online);
    }

    public function test_connection_error_records_server_offline(): void
    {
        $server = Server::factory()->create(['host' => '10.0.0.3', 'port' => 8888]);

        Http::fake([
            'http://10.0.0.3:8888/stats' => fn () => throw new \Illuminate\Http\Client\ConnectionException('timeout'),
        ]);

        $this->artisan('monitor:poll')->assertSuccessful();

        $snapshot = ServerSnapshot::where('server_id', $server->id)->latest()->first();

        $this->assertNotNull($snapshot);
        $this->assertFalse($snapshot->online);
    }

    public function test_old_snapshots_are_pruned(): void
    {
        $server = Server::factory()->create(['host' => '10.0.0.4', 'port' => 8888]);

        // Create a snapshot older than 24h
        ServerSnapshot::factory()->for($server)->create([
            'created_at' => now()->subDays(2),
        ]);

        Http::fake(['*' => Http::response(['cpu_percent' => 0, 'memory' => ['percent' => 0, 'used_mb' => 0, 'total_mb' => 0], 'disks' => [], 'load_avg' => [0,0,0], 'uptime_seconds' => 0, 'containers' => []], 200)]);

        $this->artisan('monitor:poll')->assertSuccessful();

        $this->assertEquals(1, ServerSnapshot::where('server_id', $server->id)->count());
    }
}
