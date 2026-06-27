<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status'  => $this->faker->randomElement(['pending', 'confirmed', 'cancelled']),
            'total'   => $this->faker->randomFloat(2, 10, 500),
            'notes'   => $this->faker->sentence(),
        ];
    }
}