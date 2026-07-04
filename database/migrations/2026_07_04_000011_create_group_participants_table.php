<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A user's membership in a group order. See spec 7.2 (Entity: group_participants).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_participants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('group_order_id')->constrained('group_orders')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('user');

            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();

            $table->enum('status', ['JOINED', 'LEFT'])->default('JOINED');

            // A user can only be enrolled in a given group order once (BR-007: no rejoin).
            $table->unique(['group_order_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_participants');
    }
};
