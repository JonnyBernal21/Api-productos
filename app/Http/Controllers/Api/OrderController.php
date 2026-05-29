<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->where('user_id', $request->user()->id)
            ->with(['items.product', 'delivery'])
            ->latest()
            ->paginate(min($request->integer('per_page', 15), 50));

        return response()->json([
            'success' => true,
            'data' => OrderResource::collection($orders)->response()->getData(true),
        ]);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para ver esta orden.',
            ], 403);
        }

        $order->load(['items.product', 'delivery']);

        return response()->json([
            'success' => true,
            'data' => [
                'order' => new OrderResource($order),
            ],
        ]);
    }
}
