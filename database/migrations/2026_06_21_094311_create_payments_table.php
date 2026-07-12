<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->string('payment_number', 50)->unique();

            $table->foreignId('invoice_id')
                ->constrained('invoices')
                ->restrictOnDelete();

            $table->date('payment_date');

            $table->string('payment_method', 30);
            // cash, transfer

            $table->decimal('amount', 15, 2);

            $table->string('bank_name', 100)->nullable();
            $table->string('reference_no', 100)->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('payment_number');
            $table->index('payment_date');
            $table->index('payment_method');
            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
