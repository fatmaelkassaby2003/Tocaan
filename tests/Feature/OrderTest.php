<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user  = User::factory()->create();
        $this->token = auth()->login($this->user);
    }

    private function authHeader(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    private function orderPayload(): array
    {
        return [
            'notes' => 'Test order',
            'items' => [
                [
                    'product_name' => 'Product A',
                    'quantity'     => 2,
                    'price'        => 50.00,
                ],
                [
                    'product_name' => 'Product B',
                    'quantity'     => 1,
                    'price'        => 30.00,
                ],
            ],
        ];
    }

    public function test_can_create_order(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/orders', $this->orderPayload());

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data'    => ['total' => 130.00],
            ]);

        $this->assertDatabaseHas('orders', [
            'user_id' => $this->user->id,
            'total'   => 130.00,
        ]);
    }

    public function test_can_list_orders(): void
    {
        Order::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['data', 'total', 'per_page'],
            ]);
    }

    public function test_can_filter_orders_by_status(): void
    {
        Order::factory()->create(['user_id' => $this->user->id, 'status' => 'confirmed']);
        Order::factory()->create(['user_id' => $this->user->id, 'status' => 'pending']);

        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/orders?status=confirmed');

        $response->assertStatus(200);

        $orders = $response->json('data.data');
        $this->assertTrue(collect($orders)->every(fn($o) => $o['status'] === 'confirmed'));
    }

    public function test_can_update_order(): void
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeader())
            ->putJson("/api/orders/{$order->id}", ['status' => 'confirmed']);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('orders', [
            'id'     => $order->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_can_delete_order_without_payments(): void
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders($this->authHeader())
            ->deleteJson("/api/orders/{$order->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('orders', ['id' => $order->id]);
    }

    public function test_cannot_delete_order_with_payments(): void
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);
        $order->payments()->create([
            'payment_id'     => 'PAY-123',
            'status'         => 'successful',
            'payment_method' => 'credit_card',
            'amount'         => 100,
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->deleteJson("/api/orders/{$order->id}");

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_cannot_access_other_user_order(): void
    {
        $otherUser  = User::factory()->create();
        $otherOrder = Order::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeaders($this->authHeader())
            ->getJson("/api/orders/{$otherOrder->id}");

        $response->assertStatus(403);
    }
}