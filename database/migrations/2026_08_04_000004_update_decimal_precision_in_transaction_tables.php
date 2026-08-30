<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_order_items', function (Blueprint $table) {
            $table->integer('qty')->change();
            $table->decimal('line_total', 15, 2)->default(0)->change();
        });

        Schema::table('stock_balances', function (Blueprint $table) {
            $table->decimal('qty_available', 12, 4)->default(0)->change();
            $table->decimal('qty_reserved', 12, 4)->default(0)->change();
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->decimal('qty', 12, 4)->change();
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->integer('qty')->change();
            $table->decimal('line_total', 15, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('delivery_order_items', function (Blueprint $table) {
            $table->decimal('qty', 15, 2)->change();
        });

        Schema::table('stock_balances', function (Blueprint $table) {
            $table->decimal('qty_available', 15, 2)->default(0)->change();
            $table->decimal('qty_reserved', 15, 2)->default(0)->change();
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->decimal('qty', 15, 2)->change();
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->decimal('qty', 15, 2)->change();
        });
    }
};
