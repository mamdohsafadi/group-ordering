<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catalog of modifier groups for a restaurant's menu (e.g. "Size", "Add-ons").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dish_options_group', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->index();
            $table->string('en_name');
            $table->string('ar_name');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dish_options_group');
    }
};
