<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A collaborative ordering session. See spec 7.1 (Entity: group_orders).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('leader_id')->constrained('user');
            $table->foreignId('restaurant_id')->constrained('restaurant');
            $table->foreignId('delivery_address_id')->nullable()->constrained('user_address');

            // The unified bill this group order becomes at checkout (null until submitted).
            $table->foreignId('bill_id')->nullable()->constrained('bill');

            $table->enum('status', ['CREATED', 'ACTIVE', 'SUBMITTED', 'CANCELLED', 'EXPIRED'])
                ->default('CREATED');

            $table->string('shareable_link', 255)->unique();
            $table->string('promo_code', 50)->nullable();

            $table->enum('delivery_time_type', ['ASAP', 'SCHEDULED'])->default('ASAP');
            $table->timestamp('scheduled_time')->nullable();

            $table->timestamp('expires_at')->nullable();   // 5-minute participation deadline
            $table->timestamp('submitted_at')->nullable();  // final submission time

            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_orders');
    }
};
