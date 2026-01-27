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

        $table->unsignedBigInteger('pump_trip')->nullable()->after('trip');
            //
        });
        Schema::table('selected_order_schedules', function (Blueprint $table) {

        $table->unsignedBigInteger('pump_trip')->nullable()->after('trip');
            //
        });
        Schema::table('order_schedules', function (Blueprint $table) {

        $table->unsignedBigInteger('pump_trip')->nullable()->after('trip');
            //
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('live_order_schedules', function (Blueprint $table) {

            $table->dropColumn('pump_trip');
                //
            });
            Schema::table('selected_order_schedules', function (Blueprint $table) {

            $table->dropColumn('pump_trip');
                //
            });
            Schema::table('order_schedules', function (Blueprint $table) {

            $table->dropColumn('pump_trip');
                //
            });
    }
};
