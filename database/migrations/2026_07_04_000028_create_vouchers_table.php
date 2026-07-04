<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Discount/promo code applied to an order
 * (only the columns needed to represent a discount applied to a bill/dish).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('voucher');            // the code
            $table->integer('category')->nullable();
            $table->integer('type')->default(0);
            $table->integer('apply_on')->default(0);
            $table->decimal('value', 10, 2)->default(0);
            $table->foreignId('bill_id')->nullable()->index();  // legacy: not FK-constrained
            $table->foreignId('dish_id')->nullable()->index();
            $table->decimal('total', 10, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
