<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_receipts', function (Blueprint $table) {
            $table->id();

            $table->string('receipt_number', 30)->unique();

            $table->foreignId('purchase_order_id')
                ->constrained('purchase_orders')
                ->restrictOnDelete();

            $table->date('receipt_date');

            // Receipt langsung final; belum ada workflow approval (di luar scope SCM-06).
            $table->string('status', 20)->default('posted');

            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('receipt_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_receipts');
    }
};
