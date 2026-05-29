<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('description');
            $table->string('barcode')->nullable()->after('name');
            $table->foreignId('category_id')->nullable()->after('barcode')->constrained()->nullOnDelete();
            $table->unsignedBigInteger('unidad_medida_id')->nullable()->after('category_id');
            $table->string('image')->nullable()->after('stock');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn(['barcode', 'category_id', 'unidad_medida_id', 'image']);
            $table->text('description')->nullable()->after('name');
        });
    }
};
