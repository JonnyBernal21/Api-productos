<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Http\Requests\ConfirmPaymentRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __construct(
        protected CheckoutService $checkoutService,
    ) {}

    public function store(CheckoutRequest $request): JsonResponse
    {
        $result = $this->checkoutService->checkout(
            user: $request->user(),
            notes: $request->input('notes'),
            gateway: $request->input('payment_gateway'),
        );

        return response()->json([
            'success' => true,
            'message' => 'Orden creada. Procede con el pago.',
            'data' => [
                'order' => new OrderResource($result['order']),
                'payment' => $result['payment'],
            ],
        ], 201);
    }

    public function confirmPayment(ConfirmPaymentRequest $request, Order $order): JsonResponse
    {
        $order = $this->checkoutService->confirmPayment(
            user: $request->user(),
            order: $order,
            paymentMethod: $request->string('payment_method')->toString(),
            paymentReference: $request->input('payment_reference'),
            metadata: $request->input('metadata'),
        );

        return response()->json([
            'success' => true,
            'message' => 'Pago confirmado. Orden completada.',
            'data' => [
                'order' => new OrderResource($order),
            ],
        ]);
    }

    public function cancel(Request $request, Order $order): JsonResponse
    {
        $order = $this->checkoutService->cancelOrder($request->user(), $order);

        return response()->json([
            'success' => true,
            'message' => 'Orden cancelada.',
            'data' => [
                'order' => new OrderResource($order),
            ],
        ]);
    }
}
