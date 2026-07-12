<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            $table->string('invoice_number', 50)->unique();

            $table->foreignId('delivery_order_id')
                ->unique()
                ->constrained('delivery_orders')
                ->restrictOnDelete();

            $table->foreignId('customer_id')
                ->constrained('customers')
                ->restrictOnDelete();

            $table->date('invoice_date');
            $table->integer('payment_term_days')->nullable();
            $table->date('due_date')->nullable();

            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_total', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->decimal('paid_total', 15, 2)->default(0);
            $table->decimal('receivable_amount', 15, 2)->default(0);

            $table->string('status', 30)->default('unpaid');
            // unpaid, partial_paid, paid, overdue, cancelled

            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('invoice_number');
            $table->index('invoice_date');
            $table->index('due_date');
            $table->index('status');
            $table->index(['customer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
