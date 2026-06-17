<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ServerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'      => $this->faker->word(),
            'host'      => $this->faker->ipv4(),
            'port'      => 8888,
            'token'     => 'test-token',
            'is_active' => true,
        ];
    }
}
