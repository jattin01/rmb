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
        Schema::table('transit_mixers', function (Blueprint $table) {
            $table->string('plate_no', 100)->nullable()->after('truck_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transit_mixers', function (Blueprint $table) {
            //
        });
    }
};
