<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catalog of individual modifiers/options a dish can carry (belongs to a group).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dish_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dish_group_id')->constrained('dish_options_group');
            $table->string('en_name');
            $table->string('ar_name');
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('purchase_price', 10, 2)->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dish_options');
    }
};
