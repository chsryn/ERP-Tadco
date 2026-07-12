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
            $table->string('uom', 30)->nullable();
            $table->string('uom_secondary', 30)->nullable();
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
