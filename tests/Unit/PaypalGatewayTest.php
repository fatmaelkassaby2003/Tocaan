<?php

namespace Tests\Unit;

use App\Services\Payment\Gateways\PaypalGateway;
use App\Services\Payment\PaymentGatewayInterface;
use Tests\TestCase;

class PaypalGatewayTest extends TestCase
{
    private PaypalGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = new PaypalGateway();
    }

    public function test_implements_payment_gateway_interface(): void
    {
        $this->assertInstanceOf(PaymentGatewayInterface::class, $this->gateway);
    }

    public function test_gateway_name_is_paypal(): void
    {
        $this->assertEquals('paypal', $this->gateway->getName());
    }

    public function test_process_returns_successful_response(): void
    {
        $result = $this->gateway->process(['amount' => 75.50, 'order_id' => 2]);

        $this->assertTrue($result['success']);
        $this->assertStringStartsWith('PP-', $result['transaction_id']);
        $this->assertEquals('paypal', $result['gateway']);
        $this->assertEquals(75.50, $result['amount']);
        $this->assertArrayHasKey('message', $result);
    }

    public function test_process_returns_correct_amount(): void
    {
        $result = $this->gateway->process(['amount' => 320.00, 'order_id' => 3]);

        $this->assertEquals(320.00, $result['amount']);
    }

    public function test_each_transaction_has_unique_id(): void
    {
        $result1 = $this->gateway->process(['amount' => 50, 'order_id' => 1]);
        $result2 = $this->gateway->process(['amount' => 50, 'order_id' => 1]);

        $this->assertNotEquals($result1['transaction_id'], $result2['transaction_id']);
    }
}
