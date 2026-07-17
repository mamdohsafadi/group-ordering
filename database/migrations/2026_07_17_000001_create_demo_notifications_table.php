<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Demo stand-in for the live app's push-notification inbox, so in-app
 * notifications (US-003, FR-012, FR-029, BR-012) are visible in the showcase.
 * Owned by this service; not part of the production integration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('user')->cascadeOnDelete();
            $table->string('type', 50);          // e.g. group.invited, participant.left
            $table->json('payload')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_notifications');
    }
};
