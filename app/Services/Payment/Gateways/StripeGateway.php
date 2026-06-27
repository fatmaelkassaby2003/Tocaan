<?php

namespace App\Services\Payment\Gateways;

use App\Services\Payment\PaymentGatewayInterface;

class StripeGateway implements PaymentGatewayInterface
{
    protected string $apiKey;
    protected string $secret;

    public function __construct()
    {
        $this->apiKey = config('services.stripe.key', '');
        $this->secret = config('services.stripe.secret', '');
    }

    public function process(array $paymentData): array
    {
        // Simulate Stripe processing using configured credentials
        return [
            'success'        => true,
            'transaction_id' => 'ST-' . uniqid(),
            'message'        => 'Stripe payment processed successfully',
            'gateway'        => $this->getName(),
            'amount'         => $paymentData['amount'],
        ];
    }

    public function getName(): string
    {
        return 'stripe';
    }
}