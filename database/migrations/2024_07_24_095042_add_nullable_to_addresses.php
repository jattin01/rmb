<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->unsignedBigInteger('country_id') -> nullable() -> change();
            $table->unsignedBigInteger('province_id') -> nullable() -> change();
            $table->unsignedBigInteger('district_id') -> nullable() -> change();
            $table->string('postal_code') -> nullable() -> change();
            $table->string('latitude') -> nullable() -> change();
            $table->string('longitude') -> nullable() -> change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            //
        });
    }
};
