<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code', 30)->unique();
            $table->string('customer_name', 150);
            $table->string('province', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('sub_district', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('type_of_business', 100)->nullable();
            $table->string('market', 100)->nullable();
            $table->string('customer_type', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('customer_name');
            $table->index('city');
            $table->index('customer_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
