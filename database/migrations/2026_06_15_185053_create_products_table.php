<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('product_code', 50)->unique();
            $table->string('product_name', 200);
            $table->decimal('base_price', 15, 2)->default(0);
            $table->enum('uom', ['BOX', 'SACK'])->default('BOX');
            $table->string('net_weight')->nullable();
            $table->string('segment', 150)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('product_name');
            $table->index('segment');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
