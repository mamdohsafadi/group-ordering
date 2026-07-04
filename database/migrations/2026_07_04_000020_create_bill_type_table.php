<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lookup table for bill types (e.g. PICKUP / DELIVERY). Referenced by bill.bill_type.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_type', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('arabic_name')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_type');
    }
};
