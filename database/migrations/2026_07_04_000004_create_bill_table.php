<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reference table for the real order/checkout record submitted to the restaurant.
 * A group order produces exactly one bill at checkout (BR-005: single payer).
 * Only the columns the Group Ordering feature writes are declared here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->index();        // payer (the group leader)
            $table->foreignId('restaurant_id')->index();
            $table->foreignId('address_id')->nullable()->index();

            $table->integer('bill_type')->default(0);
            $table->string('time_type')->default('ASAP'); // ASAP | SCHEDULED
            $table->integer('stage')->default(0);
            $table->integer('state')->nullable();

            $table->decimal('sub_total', 10, 2)->nullable();
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('delivery', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->nullable();
            $table->decimal('net_total', 10, 2)->nullable();

            // Applied discount (legacy: not FK-constrained).
            $table->foreignId('voucher_id')->nullable()->index();

            $table->dateTime('open_time')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill');
    }
};
