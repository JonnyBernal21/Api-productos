<?php

return [

    'store_latitude' => (float) env('DELIVERY_STORE_LAT', 19.432608),
    'store_longitude' => (float) env('DELIVERY_STORE_LNG', -99.133209),
    'store_name' => env('DELIVERY_STORE_NAME', 'Tienda Axium'),
    'average_speed_kmh' => (float) env('DELIVERY_AVERAGE_SPEED_KMH', 28),

];
