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
        Schema::table('pump_types', function (Blueprint $table) {
            $table->unsignedBigInteger('group_company_id') -> after('id');
            $table->foreign('group_company_id')->references('id') -> on('group_companies');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pump_types', function (Blueprint $table) {
            //
        });
    }
};
