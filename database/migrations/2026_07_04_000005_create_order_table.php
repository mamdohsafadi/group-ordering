<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reference table for a single line item on a bill.
 * Each participant sub-cart line becomes one order row at checkout.
 * Only the columns the Group Ordering feature writes are declared here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bill_id')->index();
            $table->foreignId('dish_id')->index();

            $table->integer('quantity')->default(1);
            $table->decimal('dish_price', 10, 2)->nullable();
            $table->decimal('total', 10, 2)->nullable();
            $table->decimal('discount', 10, 2)->default(0);
            $table->text('special_instructions')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order');
    }
};
