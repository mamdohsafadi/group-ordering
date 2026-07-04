<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reference table for a menu item that can be added to a sub-cart.
 * Only the columns consumed by the Group Ordering feature are declared here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dish', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->index();
            $table->string('name');
            $table->string('eng_name')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->tinyInteger('active')->default(1);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dish');
    }
};
