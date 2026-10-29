<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('dynamics_code', 50)->nullable()->unique()->after('product_code');
            $table->decimal('purchase_price', 15, 2)->nullable()->after('base_price');
            $table->decimal('weight_kg', 10, 2)->nullable()->after('net_weight');
            $table->decimal('target_stock', 15, 2)->nullable()->after('weight_kg');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['dynamics_code']);
            $table->dropColumn(['dynamics_code', 'purchase_price', 'weight_kg', 'target_stock']);
        });
    }
};
