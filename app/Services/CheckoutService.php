<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Payment\PaymentGatewayManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        protected CartService $cartService,
        protected PaymentGatewayManager $paymentGatewayManager,
    ) {}

    /**
     * @return array{order: Order, payment: array<string, mixed>}
     */
    public function checkout(User $user, ?string $notes = null, ?string $gateway = null): array
    {
        return DB::transaction(function () use ($user, $notes, $gateway) {
            $cart = $this->cartService->getCartWithItems($user);

            if ($cart->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'cart' => ['Tu carrito está vacío.'],
                ]);
            }

            foreach ($cart->items as $item) {
                $product = Product::query()->lockForUpdate()->find($item->product_id);

                if (! $product) {
                    throw ValidationException::withMessages([
                        'cart' => ["El producto {$item->product_id} ya no está disponible."],
                    ]);
                }

                if ($product->stock < $item->quantity) {
                    throw ValidationException::withMessages([
                        'cart' => ["Stock insuficiente para {$product->name}. Disponible: {$product->stock}."],
                    ]);
                }
            }

            $subtotal = $cart->subtotal;

            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => $this->generateOrderNumber(),
                'status' => OrderStatus::PendingPayment,
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'currency' => config('payment.currency', 'MXN'),
                'notes' => $notes,
            ]);

            foreach ($cart->items as $item) {
                $product = $item->product;

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_image' => $product->image,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->quantity * $item->unit_price,
                ]);
            }

            $payment = $this->paymentGatewayManager->createPaymentSession($order, $gateway);

            $order->update([
                'payment_gateway' => $payment['gateway'],
                'payment_reference' => $payment['payment_reference'],
                'payment_metadata' => $payment['metadata'],
            ]);

            $this->cartService->clearCart($cart);

            return [
                'order' => $order->load('items.product'),
                'payment' => $payment,
            ];
        });
    }

    public function confirmPayment(
        User $user,
        Order $order,
        string $paymentMethod,
        ?string $paymentReference = null,
        ?array $metadata = null,
    ): Order {
        return DB::transaction(function () use ($user, $order, $paymentMethod, $paymentReference, $metadata) {
            $this->ensureOrderBelongsToUser($user, $order);

            if (! $order->isPendingPayment()) {
                throw ValidationException::withMessages([
                    'order' => ['Esta orden ya fue procesada.'],
                ]);
            }

            $payload = [
                'payment_reference' => $paymentReference ?? $order->payment_reference,
                'metadata' => $metadata ?? [],
            ];

            if (! $this->paymentGatewayManager->verifyPayment($order, $payload)) {
                $order->update(['status' => OrderStatus::Failed]);

                throw ValidationException::withMessages([
                    'payment' => ['No se pudo verificar el pago.'],
                ]);
            }

            foreach ($order->items as $item) {
                $product = Product::query()->lockForUpdate()->find($item->product_id);

                if (! $product) {
                    throw ValidationException::withMessages([
                        'order' => ["El producto {$item->product_name} ya no está disponible."],
                    ]);
                }

                if ($product->stock < $item->quantity) {
                    throw ValidationException::withMessages([
                        'order' => ["Stock insuficiente para {$item->product_name}."],
                    ]);
                }

                $product->decrement('stock', $item->quantity);
            }

            $order->update([
                'status' => OrderStatus::Confirmed,
                'payment_method' => $paymentMethod,
                'payment_reference' => $payload['payment_reference'],
                'payment_metadata' => array_merge($order->payment_metadata ?? [], $metadata ?? []),
                'paid_at' => now(),
                'confirmed_at' => now(),
            ]);

            return $order->fresh(['items.product']);
        });
    }

    public function cancelOrder(User $user, Order $order): Order
    {
        $this->ensureOrderBelongsToUser($user, $order);

        if (! $order->isPendingPayment()) {
            throw ValidationException::withMessages([
                'order' => ['Solo puedes cancelar órdenes pendientes de pago.'],
            ]);
        }

        $order->update([
            'status' => OrderStatus::Cancelled,
            'cancelled_at' => now(),
        ]);

        return $order->fresh(['items.product']);
    }

    protected function ensureOrderBelongsToUser(User $user, Order $order): void
    {
        if ($order->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'order' => ['No tienes permiso para acceder a esta orden.'],
            ]);
        }
    }

    protected function generateOrderNumber(): string
    {
        do {
            $number = 'ORD-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }
}
