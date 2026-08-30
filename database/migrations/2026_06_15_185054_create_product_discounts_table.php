<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('strata_level', 10);
            $table->integer('min_qty')->default(0);
            $table->integer('max_qty')->nullable();
            $table->decimal('discount_percentage', 5, 2)->default(0.00);
            $table->timestamps();

            $table->index(['product_id', 'strata_level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_discounts');
    }
};
