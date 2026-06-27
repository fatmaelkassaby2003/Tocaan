<?php

namespace App\Services\Payment\Gateways;

use App\Services\Payment\PaymentGatewayInterface;

class CreditCardGateway implements PaymentGatewayInterface
{
    protected string $merchantId;
    protected string $apiKey;

    public function __construct()
    {
        $this->merchantId = config('services.credit_card.merchant_id', '');
        $this->apiKey     = config('services.credit_card.api_key', '');
    }

    public function process(array $paymentData): array
    {
        // Simulate credit card processing using configured credentials
        return [
            'success'        => true,
            'transaction_id' => 'CC-' . uniqid(),
            'message'        => 'Credit card payment processed successfully',
            'gateway'        => $this->getName(),
            'amount'         => $paymentData['amount'],
        ];
    }

    public function getName(): string
    {
        return 'credit_card';
    }
}