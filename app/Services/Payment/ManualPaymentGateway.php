<?php

namespace App\Services\Payment;

use App\Models\Order;
use Illuminate\Support\Str;

class ManualPaymentGateway implements PaymentGatewayInterface
{
    public function createPaymentSession(Order $order): array
    {
        $reference = 'MAN-'.strtoupper(Str::random(12));

        return [
            'gateway' => 'manual',
            'payment_reference' => $reference,
            'checkout_url' => null,
            'client_secret' => null,
            'public_key' => null,
            'metadata' => [
                'message' => 'Integra tu pasarela de pago. Usa confirm-payment al completar el cobro.',
                'order_number' => $order->order_number,
                'amount' => (float) $order->total,
                'currency' => $order->currency,
            ],
        ];
    }

    public function verifyPayment(Order $order, array $payload): bool
    {
        return ! empty($payload['payment_reference']);
    }
}
