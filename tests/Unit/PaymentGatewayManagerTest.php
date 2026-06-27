<?php

namespace Tests\Unit;

use App\Services\Payment\Gateways\CreditCardGateway;
use App\Services\Payment\Gateways\PaypalGateway;
use App\Services\Payment\Gateways\StripeGateway;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\PaymentGatewayManager;
use InvalidArgumentException;
use Tests\TestCase;

class PaymentGatewayManagerTest extends TestCase
{
    private PaymentGatewayManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new PaymentGatewayManager();
    }

    public function test_default_gateways_are_registered(): void
    {
        $gateways = $this->manager->getAvailableGateways();

        $this->assertContains('credit_card', $gateways);
        $this->assertContains('paypal', $gateways);
        $this->assertContains('stripe', $gateways);
        $this->assertCount(3, $gateways);
    }

    public function test_can_resolve_credit_card_gateway(): void
    {
        $gateway = $this->manager->gateway('credit_card');

        $this->assertInstanceOf(PaymentGatewayInterface::class, $gateway);
        $this->assertInstanceOf(CreditCardGateway::class, $gateway);
    }

    public function test_can_resolve_paypal_gateway(): void
    {
        $gateway = $this->manager->gateway('paypal');

        $this->assertInstanceOf(PaymentGatewayInterface::class, $gateway);
        $this->assertInstanceOf(PaypalGateway::class, $gateway);
    }

    public function test_can_resolve_stripe_gateway(): void
    {
        $gateway = $this->manager->gateway('stripe');

        $this->assertInstanceOf(PaymentGatewayInterface::class, $gateway);
        $this->assertInstanceOf(StripeGateway::class, $gateway);
    }

    public function test_throws_exception_for_invalid_gateway(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment gateway [bitcoin] not found.');

        $this->manager->gateway('bitcoin');
    }

    public function test_can_register_custom_gateway(): void
    {
        $customGateway = new class implements PaymentGatewayInterface {
            public function process(array $paymentData): array
            {
                return ['success' => true, 'gateway' => 'custom'];
            }

            public function getName(): string
            {
                return 'custom';
            }
        };

        $this->manager->register($customGateway);

        $this->assertContains('custom', $this->manager->getAvailableGateways());
        $this->assertSame($customGateway, $this->manager->gateway('custom'));
    }

    public function test_custom_gateway_does_not_remove_defaults(): void
    {
        $customGateway = new class implements PaymentGatewayInterface {
            public function process(array $paymentData): array
            {
                return ['success' => true];
            }

            public function getName(): string
            {
                return 'custom';
            }
        };

        $this->manager->register($customGateway);

        $gateways = $this->manager->getAvailableGateways();
        $this->assertCount(4, $gateways);
        $this->assertContains('credit_card', $gateways);
        $this->assertContains('paypal', $gateways);
        $this->assertContains('stripe', $gateways);
        $this->assertContains('custom', $gateways);
    }
}
