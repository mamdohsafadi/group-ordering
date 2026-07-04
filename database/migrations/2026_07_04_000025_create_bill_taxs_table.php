<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-bill tax breakdown.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_taxs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_id')->constrained('bill')->cascadeOnDelete();
            $table->decimal('consumption_tax', 10, 2)->default(0);
            $table->decimal('local_fees_tax', 10, 2)->default(0);
            $table->decimal('re_building_tax', 10, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_taxs');
    }
};
