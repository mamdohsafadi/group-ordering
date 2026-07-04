<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links a dish to the modifiers selected for it — the applied modifiers of an order line.
 * dish_id references `dish` (not the order row).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applied_dish_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dish_id')->constrained('dish');
            $table->foreignId('dish_option_id')->constrained('dish_options');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applied_dish_options');
    }
};
