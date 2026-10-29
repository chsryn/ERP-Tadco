<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('purchase_order_id')
                ->constrained('purchase_orders')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete();

            // snapshot produk saat PO dibuat
            $table->string('dynamics_code', 50)->nullable();
            $table->decimal('weight_kg', 10, 2);

            $table->decimal('qty_plan', 15, 2);
            $table->decimal('qty_ton', 15, 4)->default(0);

            $table->decimal('week_1', 15, 2)->default(0);
            $table->decimal('week_2', 15, 2)->default(0);
            $table->decimal('week_3', 15, 2)->default(0);
            $table->decimal('week_4', 15, 2)->default(0);

            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('special_discount', 15, 2)->default(0);
            $table->decimal('price_nett', 15, 2)->default(0);
            $table->decimal('total_price', 15, 2)->default(0);

            $table->decimal('qty_received', 15, 2)->default(0);

            $table->timestamps();

            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
