<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-participant cost breakdown, plus the leader's master invoice.
 * See spec 7.4 (Entity: group_invoices).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_invoices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('group_order_id')->constrained('group_orders')->cascadeOnDelete();
            // Nullable: the master invoice (is_master = true) has no single participant.
            $table->foreignId('participant_id')->nullable()->constrained('group_participants')->cascadeOnDelete();

            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('delivery_fee_share', 10, 2)->default(0);
            $table->decimal('tax_share', 10, 2)->default(0);
            $table->decimal('discount_share', 10, 2)->default(0); // stored as a negative amount
            $table->decimal('total', 10, 2)->default(0);

            $table->boolean('is_master')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_invoices');
    }
};
