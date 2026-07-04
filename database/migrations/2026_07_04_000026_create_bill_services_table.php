<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additional services charged on a bill (e.g. packaging).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_id')->constrained('bill')->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->index();
            $table->decimal('service_sub_total', 10, 2)->default(0);
            $table->decimal('service_sub_total_commission', 10, 2)->default(0);
            $table->decimal('service_tax', 10, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_services');
    }
};
