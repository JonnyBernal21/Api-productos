<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryStatusResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->resource;

        return [
            'delivery' => isset($data['delivery']) && $data['delivery']
                ? new DeliveryResource($data['delivery'])
                : null,
            'store' => $data['store'],
            'next_action' => $data['next_action'],
            'message' => $data['message'],
        ];
    }
}
