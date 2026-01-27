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
        Schema::table('customer_project_sites', function (Blueprint $table) {
            $table->unsignedBigInteger('company_location_id')->after('longitude')->nullable();
            $table->foreign('company_location_id')->references('id')->on('company_locations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_project_sites', function (Blueprint $table) {
            //
        });
    }
};
