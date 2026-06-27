<?php

namespace Tests\Unit;

use App\Services\Payment\Gateways\CreditCardGateway;
use App\Services\Payment\PaymentGatewayInterface;
use Tests\TestCase;

class CreditCardGatewayTest extends TestCase
{
    private CreditCardGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = new CreditCardGateway();
    }

    public function test_implements_payment_gateway_interface(): void
    {
        $this->assertInstanceOf(PaymentGatewayInterface::class, $this->gateway);
    }

    public function test_gateway_name_is_credit_card(): void
    {
        $this->assertEquals('credit_card', $this->gateway->getName());
    }

    public function test_process_returns_successful_response(): void
    {
        $result = $this->gateway->process(['amount' => 100.00, 'order_id' => 1]);

        $this->assertTrue($result['success']);
        $this->assertStringStartsWith('CC-', $result['transaction_id']);
        $this->assertEquals('credit_card', $result['gateway']);
        $this->assertEquals(100.00, $result['amount']);
        $this->assertArrayHasKey('message', $result);
    }

    public function test_process_returns_correct_amount(): void
    {
        $result = $this->gateway->process(['amount' => 250.75, 'order_id' => 5]);

        $this->assertEquals(250.75, $result['amount']);
    }

    public function test_each_transaction_has_unique_id(): void
    {
        $result1 = $this->gateway->process(['amount' => 50, 'order_id' => 1]);
        $result2 = $this->gateway->process(['amount' => 50, 'order_id' => 1]);

        $this->assertNotEquals($result1['transaction_id'], $result2['transaction_id']);
    }
}
