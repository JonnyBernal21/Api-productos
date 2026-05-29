<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryTrackingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->resource;
        $delivery = $data['delivery'];

        return [
            'delivery' => new DeliveryResource($delivery),
            'store' => $data['store'],
            'destination' => $data['destination'] ?? null,
            'progress' => $data['progress'],
            'remaining_minutes' => $data['remaining_minutes'],
            'estimated_arrival_at' => $data['estimated_arrival_at'] ?? null,
            'eta_label' => $data['eta_label'],
            'route_points' => $data['route_points'],
        ];
    }
}
