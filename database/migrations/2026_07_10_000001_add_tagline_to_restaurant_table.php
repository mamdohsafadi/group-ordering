<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Demo-only display copy for the restaurant header (the menu screen renders
 * a tagline under the restaurant name). Not part of the legacy schema.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurant', function (Blueprint $table) {
            $table->string('tagline')->nullable()->after('arabic_name');
        });
    }

    public function down(): void
    {
        Schema::table('restaurant', function (Blueprint $table) {
            $table->dropColumn('tagline');
        });
    }
};
