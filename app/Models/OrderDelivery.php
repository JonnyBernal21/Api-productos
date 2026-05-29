<?php

namespace App\Models;

use App\Enums\DeliveryStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderDelivery extends Model
{
    protected $fillable = [
        'order_id',
        'delivery_latitude',
        'delivery_longitude',
        'delivery_address',
        'delivery_notes',
        'status',
        'driver_name',
        'driver_latitude',
        'driver_longitude',
        'route_points',
        'estimated_minutes',
        'dispatched_at',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => DeliveryStatus::class,
            'delivery_latitude' => 'float',
            'delivery_longitude' => 'float',
            'driver_latitude' => 'float',
            'driver_longitude' => 'float',
            'route_points' => 'array',
            'dispatched_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
