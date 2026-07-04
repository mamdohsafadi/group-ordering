<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A single line in a participant's sub-cart. See spec 7.3 (Entity: group_cart_items).
 *
 * Note: the spec's `menu_item_id` maps to `dish_id` here to match the reference
 * menu-item table (`dish`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_cart_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('group_order_id')->constrained('group_orders')->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained('group_participants')->cascadeOnDelete();
            $table->foreignId('dish_id')->constrained('dish');

            // The order row this sub-cart line becomes at checkout (null until submitted).
            $table->foreignId('order_id')->nullable()->constrained('order');

            $table->unsignedInteger('quantity')->default(1);
            $table->json('modifiers')->nullable();            // selected options / modifiers
            $table->text('special_instructions')->nullable();

            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2)->default(0); // quantity x unit_price

            // NFR-008: optimistic locking for concurrent sub-cart edits.
            $table->unsignedInteger('version')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_cart_items');
    }
};
