<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stock_balances', function (Blueprint $table) {
            if (Schema::hasColumn('stock_balances', 'warehouse_id')) {
                try {
                    $table->dropUnique(['warehouse_id', 'product_id']);
                } catch (\Throwable $e) {
                    // ignore
                }
                try {
                    $table->dropForeign(['warehouse_id']);
                } catch (\Throwable $e) {
                    // ignore
                }
                $table->dropColumn('warehouse_id');
            }
            // product_id should be unique in new schema; if index cannot be modified, skip.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_balances', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_balances', 'warehouse_id')) {
                $table->foreignId('warehouse_id')->nullable()->after('id')->constrained('warehouses')->nullOnDelete();
                try {
                    $table->dropUnique(['product_id']);
                } catch (\Throwable $e) {
                    // ignore
                }
                try {
                    $table->unique(['warehouse_id', 'product_id']);
                } catch (\Throwable $e) {
                    // ignore
                }
            }
        });
    }
};
