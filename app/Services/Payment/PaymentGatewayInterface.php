<?php

namespace App\Services\Payment;

interface PaymentGatewayInterface
{
    public function process(array $paymentData): array;
    public function getName(): string;
}