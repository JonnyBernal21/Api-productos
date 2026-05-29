<?php

namespace App\Services;

use App\Enums\DeliveryStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderDelivery;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeliveryService
{
    /**
     * @return array<string, mixed>
     */
    public function getDeliveryStatus(User $user, Order $order): array
    {
        $this->ensureOrderBelongsToUser($user, $order);

        if (! $order->isConfirmed()) {
            throw ValidationException::withMessages([
                'order' => ['La orden debe estar pagada para configurar la entrega.'],
            ]);
        }

        $store = [
            'name' => config('delivery.store_name'),
            'latitude' => config('delivery.store_latitude'),
            'longitude' => config('delivery.store_longitude'),
        ];

        $delivery = $order->delivery;

        if (! $delivery) {
            return [
                'delivery' => null,
                'store' => $store,
                'next_action' => 'confirm_delivery_point',
                'message' => 'Confirma tu punto de entrega para iniciar el envío.',
            ];
        }

        if ($delivery->status === DeliveryStatus::Delivered) {
            return [
                'delivery' => $delivery,
                'store' => $store,
                'next_action' => 'completed',
                'message' => 'Tu pedido fue entregado.',
            ];
        }

        return [
            'delivery' => $delivery,
            'store' => $store,
            'next_action' => 'track_delivery',
            'message' => 'Tu pedido está en camino.',
        ];
    }

    /**
     * @return array{delivery: OrderDelivery, store: array<string, mixed>, tracking: array<string, mixed>}
     */
    public function confirmDeliveryPoint(
        User $user,
        Order $order,
        float $latitude,
        float $longitude,
        ?string $address = null,
        ?string $notes = null,
    ): array {
        $this->ensureOrderBelongsToUser($user, $order);

        if (! $order->isConfirmed()) {
            throw ValidationException::withMessages([
                'order' => ['La orden debe estar pagada y confirmada para programar la entrega.'],
            ]);
        }

        return DB::transaction(function () use ($order, $latitude, $longitude, $address, $notes) {
            $storeLat = config('delivery.store_latitude');
            $storeLng = config('delivery.store_longitude');
            $routePoints = $this->buildRoutePoints($storeLat, $storeLng, $latitude, $longitude);
            $distanceKm = $this->haversineKm($storeLat, $storeLng, $latitude, $longitude);
            $estimatedMinutes = max(
                5,
                (int) ceil(($distanceKm / config('delivery.average_speed_kmh')) * 60)
            );

            $drivers = ['Carlos Méndez', 'Ana García', 'Luis Ramírez', 'María López'];
            $driverName = $drivers[array_rand($drivers)];

            $delivery = OrderDelivery::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'delivery_latitude' => $latitude,
                    'delivery_longitude' => $longitude,
                    'delivery_address' => $address,
                    'delivery_notes' => $notes,
                    'status' => DeliveryStatus::Assigned,
                    'driver_name' => $driverName,
                    'driver_latitude' => $storeLat,
                    'driver_longitude' => $storeLng,
                    'route_points' => $routePoints,
                    'estimated_minutes' => $estimatedMinutes,
                    'dispatched_at' => now(),
                    'delivered_at' => null,
                ]
            );

            $delivery->update(['status' => DeliveryStatus::InTransit]);
            $delivery->refresh();

            $store = [
                'name' => config('delivery.store_name'),
                'latitude' => $storeLat,
                'longitude' => $storeLng,
            ];

            $tracking = $this->buildTrackingPayload(
                $delivery,
                $storeLat,
                $storeLng,
                $routePoints,
                0.0,
                $estimatedMinutes,
            );

            return [
                'delivery' => $delivery,
                'store' => $store,
                'tracking' => $tracking,
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function getDeliverySetup(User $user, Order $order): array
    {
        $this->ensureOrderBelongsToUser($user, $order);

        if (! $order->isConfirmed()) {
            throw ValidationException::withMessages([
                'order' => ['La orden debe estar pagada para configurar la entrega.'],
            ]);
        }

        $storeLat = config('delivery.store_latitude');
        $storeLng = config('delivery.store_longitude');

        return [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'next_action' => $order->delivery ? 'track_delivery' : 'confirm_delivery_point',
            'delivery' => $order->delivery,
            'store' => [
                'name' => config('delivery.store_name'),
                'latitude' => $storeLat,
                'longitude' => $storeLng,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getTracking(User $user, Order $order): array
    {
        $this->ensureOrderBelongsToUser($user, $order);

        $delivery = $order->delivery;

        if (! $delivery) {
            throw ValidationException::withMessages([
                'delivery' => ['Aún no se ha configurado el punto de entrega.'],
            ]);
        }

        $storeLat = config('delivery.store_latitude');
        $storeLng = config('delivery.store_longitude');
        $routePoints = $delivery->route_points ?? [];

        if ($delivery->status === DeliveryStatus::Delivered) {
            return $this->buildTrackingPayload($delivery, $storeLat, $storeLng, $routePoints, 1.0, 0);
        }

        $dispatchedAt = $delivery->dispatched_at ?? $delivery->created_at;
        $totalSeconds = max(60, $delivery->estimated_minutes * 60);
        $elapsed = min($totalSeconds, max(0, $dispatchedAt->diffInSeconds(now())));
        $progress = max(0, min(1, $elapsed / $totalSeconds));
        $remainingMinutes = max(0, (int) ceil(($totalSeconds - $elapsed) / 60));

        if ($progress >= 1) {
            $delivery->update([
                'status' => DeliveryStatus::Delivered,
                'delivered_at' => now(),
                'driver_latitude' => $delivery->delivery_latitude,
                'driver_longitude' => $delivery->delivery_longitude,
            ]);
            $delivery->refresh();

            return $this->buildTrackingPayload($delivery, $storeLat, $storeLng, $routePoints, 1.0, 0);
        }

        $driverPosition = $this->positionAlongRoute($routePoints, $progress);
        $delivery->update([
            'status' => DeliveryStatus::InTransit,
            'driver_latitude' => $driverPosition[0],
            'driver_longitude' => $driverPosition[1],
        ]);
        $delivery->refresh();

        return $this->buildTrackingPayload(
            $delivery,
            $storeLat,
            $storeLng,
            $routePoints,
            $progress,
            $remainingMinutes
        );
    }

    /**
     * @param  array<int, array{0: float, 1: float}>  $routePoints
     * @return array{0: float, 1: float}
     */
    protected function positionAlongRoute(array $routePoints, float $progress): array
    {
        if (count($routePoints) < 2) {
            return $routePoints[0] ?? [0.0, 0.0];
        }

        $progress = max(0, min(1, $progress));
        $index = (int) floor($progress * (count($routePoints) - 1));
        $index = min($index, count($routePoints) - 1);

        return $routePoints[$index];
    }

    /**
     * @param  array<int, array{0: float, 1: float}>  $routePoints
     * @return array<string, mixed>
     */
    protected function buildTrackingPayload(
        OrderDelivery $delivery,
        float $storeLat,
        float $storeLng,
        array $routePoints,
        float $progress,
        int $remainingMinutes,
    ): array {
        return [
            'delivery' => $delivery,
            'store' => [
                'name' => config('delivery.store_name'),
                'latitude' => $storeLat,
                'longitude' => $storeLng,
            ],
            'progress' => round($progress, 4),
            'remaining_minutes' => $remainingMinutes,
            'estimated_arrival_at' => $remainingMinutes > 0
                ? now()->addMinutes($remainingMinutes)->toIso8601String()
                : now()->toIso8601String(),
            'eta_label' => $remainingMinutes > 0
                ? "Llegada estimada en {$remainingMinutes} min"
                : 'El repartidor ha llegado',
            'route_points' => array_map(
                fn (array $point) => ['latitude' => $point[0], 'longitude' => $point[1]],
                $routePoints
            ),
            'destination' => [
                'latitude' => (float) $delivery->delivery_latitude,
                'longitude' => (float) $delivery->delivery_longitude,
                'address' => $delivery->delivery_address,
            ],
        ];
    }

    /**
     * @return array<int, array{0: float, 1: float}>
     */
    protected function buildRoutePoints(
        float $fromLat,
        float $fromLng,
        float $toLat,
        float $toLng,
        int $segments = 24,
    ): array {
        $points = [];

        for ($i = 0; $i <= $segments; $i++) {
            $t = $i / $segments;
            $points[] = [
                $fromLat + ($toLat - $fromLat) * $t,
                $fromLng + ($toLng - $fromLng) * $t,
            ];
        }

        return $points;
    }

    protected function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    protected function ensureOrderBelongsToUser(User $user, Order $order): void
    {
        if ($order->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'order' => ['No tienes permiso para acceder a esta orden.'],
            ]);
        }
    }
}
