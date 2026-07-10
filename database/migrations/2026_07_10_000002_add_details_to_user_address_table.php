<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Free-text address line (matches the legacy `user_address.details` column);
 * the start-group-order modal renders it next to the address name.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_address', function (Blueprint $table) {
            $table->string('details')->nullable()->after('street');
        });
    }

    public function down(): void
    {
        Schema::table('user_address', function (Blueprint $table) {
            $table->dropColumn('details');
        });
    }
};
