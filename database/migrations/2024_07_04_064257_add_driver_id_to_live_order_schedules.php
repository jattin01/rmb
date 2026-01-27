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
        Schema::table('live_order_schedules', function (Blueprint $table) {
            $table->unsignedBigInteger('driver_id')->nullable()->default(NULL)->after('transit_mixer_id');
            $table->foreign('driver_id')->references('id')->on('drivers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('live_order_schedules', function (Blueprint $table) {
            //
        });
    }
};
