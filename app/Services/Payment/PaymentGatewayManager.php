<?php

namespace App\Services\Payment;

use App\Models\Order;
use InvalidArgumentException;

class PaymentGatewayManager
{
    public function driver(?string $gateway = null): PaymentGatewayInterface
    {
        $gateway ??= config('payment.gateway', 'manual');

        return match ($gateway) {
            'manual' => app(ManualPaymentGateway::class),
            default => throw new InvalidArgumentException("Pasarela de pago no configurada: {$gateway}"),
        };
    }

    public function createPaymentSession(Order $order, ?string $gateway = null): array
    {
        return $this->driver($gateway)->createPaymentSession($order);
    }

    public function verifyPayment(Order $order, array $payload, ?string $gateway = null): bool
    {
        $gateway ??= $order->payment_gateway ?? config('payment.gateway', 'manual');

        return $this->driver($gateway)->verifyPayment($order, $payload);
    }
}
