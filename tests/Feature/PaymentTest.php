<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;
    private Order $confirmedOrder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user  = User::factory()->create();
        $this->token = auth()->login($this->user);

        $this->confirmedOrder = Order::factory()->create([
            'user_id' => $this->user->id,
            'status'  => 'confirmed',
            'total'   => 100.00,
        ]);
    }

    private function authHeader(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    public function test_can_process_credit_card_payment(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/payments/process', [
                'order_id'       => $this->confirmedOrder->id,
                'payment_method' => 'credit_card',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'status'         => 'successful',
                    'payment_method' => 'credit_card',
                ],
            ]);
    }

    public function test_can_process_paypal_payment(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/payments/process', [
                'order_id'       => $this->confirmedOrder->id,
                'payment_method' => 'paypal',
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);
    }

    public function test_can_process_stripe_payment(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/payments/process', [
                'order_id'       => $this->confirmedOrder->id,
                'payment_method' => 'stripe',
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);
    }

    public function test_cannot_process_payment_for_pending_order(): void
    {
        $pendingOrder = Order::factory()->create([
            'user_id' => $this->user->id,
            'status'  => 'pending',
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/payments/process', [
                'order_id'       => $pendingOrder->id,
                'payment_method' => 'credit_card',
            ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_cannot_process_payment_for_cancelled_order(): void
    {
        $cancelledOrder = Order::factory()->create([
            'user_id' => $this->user->id,
            'status'  => 'cancelled',
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/payments/process', [
                'order_id'       => $cancelledOrder->id,
                'payment_method' => 'credit_card',
            ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_cannot_use_invalid_payment_method(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/payments/process', [
                'order_id'       => $this->confirmedOrder->id,
                'payment_method' => 'bitcoin',
            ]);

        $response->assertStatus(422);
    }

    public function test_can_list_payments(): void
    {
        Payment::factory()->count(3)->create([
            'order_id' => $this->confirmedOrder->id,
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/payments');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['data', 'total'],
            ]);
    }
}