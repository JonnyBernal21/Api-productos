<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'delivery_latitude' => (float) $this->delivery_latitude,
            'delivery_longitude' => (float) $this->delivery_longitude,
            'delivery_address' => $this->delivery_address,
            'delivery_notes' => $this->delivery_notes,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'driver_name' => $this->driver_name,
            'driver_latitude' => $this->driver_latitude !== null
                ? (float) $this->driver_latitude
                : null,
            'driver_longitude' => $this->driver_longitude !== null
                ? (float) $this->driver_longitude
                : null,
            'estimated_minutes' => $this->estimated_minutes,
            'dispatched_at' => $this->dispatched_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
        ];
    }
}
