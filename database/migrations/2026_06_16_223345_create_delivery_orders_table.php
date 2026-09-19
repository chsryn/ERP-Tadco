<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->string('do_number', 50)->unique();

            $table->foreignId('customer_id')
                ->constrained('customers')
                ->restrictOnDelete();

            $table->foreignId('sales_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->date('do_date');
            $table->date('planned_delivery_date')->nullable();

            $table->decimal('total_amount', 15, 2)->default(0);

            $table->string('status', 30)->default('validated');
            // validated, shipped, cancelled

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('do_number');
            $table->index(['customer_id', 'status']);
            $table->index('do_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_orders');
    }
};
