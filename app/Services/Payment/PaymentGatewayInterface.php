<?php

namespace App\Services\Payment;

use App\Models\Order;

interface PaymentGatewayInterface
{
    /**
     * @return array{
     *     gateway: string,
     *     payment_reference: string|null,
     *     checkout_url: string|null,
     *     client_secret: string|null,
     *     public_key: string|null,
     *     metadata: array<string, mixed>
     * }
     */
    public function createPaymentSession(Order $order): array;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function verifyPayment(Order $order, array $payload): bool;
}
