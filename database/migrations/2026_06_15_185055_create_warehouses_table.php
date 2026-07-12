<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('warehouse_code', 50)->unique();
            $table->string('warehouse_name', 100);
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('warehouse_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
