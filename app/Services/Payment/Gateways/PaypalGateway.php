<?php

namespace App\Services\Payment\Gateways;

use App\Services\Payment\PaymentGatewayInterface;

class PaypalGateway implements PaymentGatewayInterface
{
    protected string $clientId;
    protected string $secret;

    public function __construct()
    {
        $this->clientId = config('services.paypal.client_id', '');
        $this->secret   = config('services.paypal.secret', '');
    }

    public function process(array $paymentData): array
    {
        // Simulate PayPal processing using configured credentials
        return [
            'success'        => true,
            'transaction_id' => 'PP-' . uniqid(),
            'message'        => 'PayPal payment processed successfully',
            'gateway'        => $this->getName(),
            'amount'         => $paymentData['amount'],
        ];
    }

    public function getName(): string
    {
        return 'paypal';
    }
}