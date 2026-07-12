<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('tier_code', 10);
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('discount_rate', 8, 5)->default(0);
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'tier_code', 'valid_from']);
            $table->index('tier_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_prices');
    }
};
