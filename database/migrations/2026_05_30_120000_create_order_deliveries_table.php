<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('delivery_latitude', 10, 7);
            $table->decimal('delivery_longitude', 10, 7);
            $table->string('delivery_address')->nullable();
            $table->text('delivery_notes')->nullable();
            $table->string('status')->default('pending');
            $table->string('driver_name')->nullable();
            $table->decimal('driver_latitude', 10, 7)->nullable();
            $table->decimal('driver_longitude', 10, 7)->nullable();
            $table->json('route_points')->nullable();
            $table->unsignedSmallInteger('estimated_minutes')->default(15);
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_deliveries');
    }
};
