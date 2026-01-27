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
        Schema::table('approval_setup', function (Blueprint $table) {
            $table->unsignedBigInteger('location_id') -> nullable() -> after('group_company_id');
            $table->foreign('location_id') -> references('id') -> on('company_locations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approval_setup', function (Blueprint $table) {
            //
        });
    }
};
