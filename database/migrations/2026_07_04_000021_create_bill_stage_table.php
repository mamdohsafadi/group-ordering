<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lookup table for bill stages (e.g. PENDING / PAID / CANCELLED). Referenced by bill.stage.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_stage', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_stage');
    }
};
