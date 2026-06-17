<?php

namespace Tests\Feature;

use App\Models\Server;
use App\Models\ServerSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_ports_are_not_shown(): void
    {
        $server = Server::factory()->create();

        ServerSnapshot::factory()->for($server)->create([
            'online' => true,
            'containers' => [
                [
                    'name'   => 'my-app',
                    'image'  => 'myapp:latest',
                    'status' => 'running',
                    'health' => 'none',
                    'ports'  => ['8080:80', '8080:80'], // duplicate
                ],
            ],
        ]);

        $response = $this->get('/monitor/ports');

        $response->assertStatus(200);
        $response->assertSeeText('my-app');

        // Only one 8080:80 row should appear — Livewire renders one <tr> per port entry
        $this->assertEquals(1, substr_count($response->getContent(), '8080:80'));
    }

    public function test_search_filters_by_port(): void
    {
        $server = Server::factory()->create(['name' => 'web-server']);

        ServerSnapshot::factory()->for($server)->create([
            'online' => true,
            'containers' => [
                ['name' => 'app',  'image' => 'app:latest',  'status' => 'running', 'health' => 'none', 'ports' => ['8080:80']],
                ['name' => 'db',   'image' => 'mariadb:11',  'status' => 'running', 'health' => 'none', 'ports' => ['3306:3306']],
            ],
        ]);

        $response = $this->get('/monitor/ports?query=3306');

        // Livewire doesn't filter via GET params — test the component directly
        $component = \Livewire\Livewire::test(\App\Livewire\PortLookup::class)
            ->set('query', '3306')
            ->viewData('rows');

        $this->assertCount(1, $component);
        $this->assertEquals('3306:3306', $component[0]['port']);
    }

    public function test_offline_servers_are_excluded(): void
    {
        $server = Server::factory()->create();

        ServerSnapshot::factory()->for($server)->offline()->create([
            'containers' => [
                ['name' => 'ghost', 'image' => 'ghost:latest', 'status' => 'running', 'health' => 'none', 'ports' => ['9999:80']],
            ],
        ]);

        $component = \Livewire\Livewire::test(\App\Livewire\PortLookup::class)
            ->viewData('rows');

        $this->assertEmpty($component);
    }
}
