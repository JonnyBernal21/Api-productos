<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmDeliveryRequest;
use App\Http\Resources\DeliveryResource;
use App\Http\Resources\DeliveryStatusResource;
use App\Http\Resources\DeliveryTrackingResource;
use App\Models\Order;
use App\Services\DeliveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    public function __construct(
        protected DeliveryService $deliveryService,
    ) {}

    public function show(Request $request, Order $order): JsonResponse
    {
        $status = $this->deliveryService->getDeliveryStatus($request->user(), $order);

        return response()->json([
            'success' => true,
            'data' => new DeliveryStatusResource($status),
        ]);
    }

    public function store(ConfirmDeliveryRequest $request, Order $order): JsonResponse
    {
        $result = $this->deliveryService->confirmDeliveryPoint(
            user: $request->user(),
            order: $order,
            latitude: $request->float('latitude'),
            longitude: $request->float('longitude'),
            address: $request->input('address'),
            notes: $request->input('notes'),
        );

        return response()->json([
            'success' => true,
            'message' => 'Punto de entrega confirmado. Tu pedido está en camino.',
            'data' => [
                'delivery' => new DeliveryResource($result['delivery']),
                'store' => $result['store'],
                'tracking' => new DeliveryTrackingResource($result['tracking']),
                'next_action' => 'track_delivery',
            ],
        ]);
    }

    public function tracking(Request $request, Order $order): JsonResponse
    {
        $tracking = $this->deliveryService->getTracking($request->user(), $order);

        return response()->json([
            'success' => true,
            'data' => new DeliveryTrackingResource($tracking),
        ]);
    }
}
