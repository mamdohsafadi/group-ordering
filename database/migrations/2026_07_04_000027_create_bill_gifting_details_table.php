<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional gifting metadata for a bill.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_gifting_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_id')->constrained('bill')->cascadeOnDelete();
            $table->string('recipient_name')->nullable();
            $table->string('recipient_phone')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_gifting_details');
    }
};
