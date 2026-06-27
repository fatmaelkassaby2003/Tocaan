<?php

namespace App\Services\Payment;

use App\Services\Payment\Gateways\CreditCardGateway;
use App\Services\Payment\Gateways\PaypalGateway;
use App\Services\Payment\Gateways\StripeGateway;
use InvalidArgumentException;

class PaymentGatewayManager
{
    protected array $gateways = [];

    public function __construct()
    {
        $this->registerDefaultGateways();
    }

    private function registerDefaultGateways(): void
    {
        $this->register(new CreditCardGateway());
        $this->register(new PaypalGateway());
        $this->register(new StripeGateway());
    }

    public function register(PaymentGatewayInterface $gateway): void
    {
        $this->gateways[$gateway->getName()] = $gateway;
    }

    public function gateway(string $name): PaymentGatewayInterface
    {
        if (!isset($this->gateways[$name])) {
            throw new InvalidArgumentException("Payment gateway [{$name}] not found.");
        }

        return $this->gateways[$name];
    }

    public function getAvailableGateways(): array
    {
        return array_keys($this->gateways);
    }
}