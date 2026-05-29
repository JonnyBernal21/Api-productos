<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliverySetupResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->resource;

        return [
            'order_id' => $data['order_id'],
            'order_number' => $data['order_number'],
            'next_action' => $data['next_action'],
            'delivery' => $data['delivery']
                ? new DeliveryResource($data['delivery'])
                : null,
            'store' => $data['store'],
        ];
    }
}
