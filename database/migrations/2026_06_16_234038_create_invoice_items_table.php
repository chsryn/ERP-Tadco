<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invoice_id')
                ->constrained('invoices')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete();

            $table->string('tier_code', 10)->nullable();
            $table->decimal('qty', 15, 2);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('discount_rate', 8, 5)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);

            $table->timestamps();

            $table->index(['invoice_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
