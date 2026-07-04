<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reference table for the restaurant a group order is placed against.
 * Only the columns consumed by the Group Ordering feature are declared here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('arabic_name')->nullable();
            $table->tinyInteger('active')->default(1);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant');
    }
};
