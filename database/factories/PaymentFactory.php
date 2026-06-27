<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'payment_id'       => Str::uuid(),
            'order_id'         => Order::factory(),
            'status'           => $this->faker->randomElement(['pending', 'successful', 'failed']),
            'payment_method'   => $this->faker->randomElement(['credit_card', 'paypal', 'stripe']),
            'amount'           => $this->faker->randomFloat(2, 10, 500),
            'gateway_response' => ['message' => 'Test payment'],
        ];
    }
}