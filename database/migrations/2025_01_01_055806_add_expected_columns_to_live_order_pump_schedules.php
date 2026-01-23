<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExpectedColumnsToLiveOrderPumpSchedules extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('live_order_pump_schedules', function (Blueprint $table) {
            // Add expected loading columns

            // Add expected QC columns
            $table->integer('expected_qc_time')->nullable()->after('planned_qc_end');
            $table->dateTime('expected_qc_start')->nullable()->after('expected_qc_time');
            $table->dateTime('expected_qc_end')->nullable()->after('expected_qc_start');

            // Add expected travel columns
            $table->integer('expected_travel_time')->nullable()->after('planned_travel_end');
            $table->dateTime('expected_travel_start')->nullable()->after('expected_travel_time');
            $table->dateTime('expected_travel_end')->nullable()->after('expected_travel_start');

            // Add expected inspection columns
            $table->integer('expected_insp_time')->nullable()->after('planned_insp_end');
            $table->dateTime('expected_insp_start')->nullable()->after('expected_insp_time');
            $table->dateTime('expected_insp_end')->nullable()->after('expected_insp_start');

            // Add expected pouring columns
            $table->integer('expected_pouring_time')->nullable()->after('planned_pouring_end');
            $table->dateTime('expected_pouring_start')->nullable()->after('expected_pouring_time');
            $table->dateTime('expected_pouring_end')->nullable()->after('expected_pouring_start');

            // Add expected cleaning columns
            $table->integer('expected_cleaning_time')->nullable()->after('planned_cleaning_end');
            $table->dateTime('expected_cleaning_start')->nullable()->after('expected_cleaning_time');
            $table->dateTime('expected_cleaning_end')->nullable()->after('expected_cleaning_start');

            // Add expected return columns
            $table->integer('expected_return_time')->nullable()->after('planned_return_end');
            $table->dateTime('expected_return_start')->nullable()->after('expected_return_time');
            $table->dateTime('expected_return_end')->nullable()->after('expected_return_start');

            // Add expected delivery start column
            $table->dateTime('expected_delivery_start')->nullable()->after('planned_delivery_start');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('live_order_pump_schedules', function (Blueprint $table) {
            // Drop expected loading columns
            $table->dropColumn(['expected_loading_time', 'expected_loading_start', 'expected_loading_end']);

            // Drop expected QC columns
            $table->dropColumn(['expected_qc_time', 'expected_qc_start', 'expected_qc_end']);

            // Drop expected travel columns
            $table->dropColumn(['expected_travel_time', 'expected_travel_start', 'expected_travel_end']);

            // Drop expected inspection columns
            $table->dropColumn(['expected_insp_time', 'expected_insp_start', 'expected_insp_end']);

            // Drop expected pouring columns
            $table->dropColumn(['expected_pouring_time', 'expected_pouring_start', 'expected_pouring_end']);

            // Drop expected cleaning columns
            $table->dropColumn(['expected_cleaning_time', 'expected_cleaning_start', 'expected_cleaning_end']);

            // Drop expected return columns
            $table->dropColumn(['expected_return_time', 'expected_return_start', 'expected_return_end']);

            // Drop expected delivery start column
            $table->dropColumn('expected_delivery_start');
        });
    }
}
