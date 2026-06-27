<?php

namespace Tests\Unit;

use App\Services\Payment\Gateways\StripeGateway;
use App\Services\Payment\PaymentGatewayInterface;
use Tests\TestCase;

class StripeGatewayTest extends TestCase
{
    private StripeGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = new StripeGateway();
    }

    public function test_implements_payment_gateway_interface(): void
    {
        $this->assertInstanceOf(PaymentGatewayInterface::class, $this->gateway);
    }

    public function test_gateway_name_is_stripe(): void
    {
        $this->assertEquals('stripe', $this->gateway->getName());
    }

    public function test_process_returns_successful_response(): void
    {
        $result = $this->gateway->process(['amount' => 199.99, 'order_id' => 4]);

        $this->assertTrue($result['success']);
        $this->assertStringStartsWith('ST-', $result['transaction_id']);
        $this->assertEquals('stripe', $result['gateway']);
        $this->assertEquals(199.99, $result['amount']);
        $this->assertArrayHasKey('message', $result);
    }

    public function test_process_returns_correct_amount(): void
    {
        $result = $this->gateway->process(['amount' => 450.25, 'order_id' => 6]);

        $this->assertEquals(450.25, $result['amount']);
    }

    public function test_each_transaction_has_unique_id(): void
    {
        $result1 = $this->gateway->process(['amount' => 50, 'order_id' => 1]);
        $result2 = $this->gateway->process(['amount' => 50, 'order_id' => 1]);

        $this->assertNotEquals($result1['transaction_id'], $result2['transaction_id']);
    }
}
