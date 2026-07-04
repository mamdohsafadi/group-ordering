<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reference table for the delivery address chosen by the group leader.
 * Only the columns consumed by the Group Ordering feature are declared here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_address', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index();
            $table->string('name')->nullable();
            $table->string('street')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_address');
    }
};
