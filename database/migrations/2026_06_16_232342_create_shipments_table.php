<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();

            $table->string('shipment_number', 50)->unique();

            $table->foreignId('delivery_order_id')
                ->unique()
                ->constrained('delivery_orders')
                ->restrictOnDelete();

            $table->date('shipment_date');

            $table->string('driver_name', 100)->nullable();
            $table->string('vehicle_no', 50)->nullable();

            $table->string('status', 30)->default('shipped');
            // shipped, received, cancelled

            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('shipment_number');
            $table->index('shipment_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
