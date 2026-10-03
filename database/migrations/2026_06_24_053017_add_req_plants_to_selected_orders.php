<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds req_plants: the minimum number of plants an order needs to be
     * delivered on time, computed by ScheduleService::calculateAndStoreReqPlants().
     * Nullable because it's only populated once a schedule run computes it;
     * unsigned because it is always >= 1 when set.
     */
    public function up(): void
    {
        Schema::table('selected_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('selected_orders', 'req_plants')) {
                $table->unsignedSmallInteger('req_plants')
                    ->nullable()
                    ->after('base_interval')
                    ->comment('Min plants required for on-time delivery (set by scheduler)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('selected_orders', function (Blueprint $table) {
            if (Schema::hasColumn('selected_orders', 'req_plants')) {
                $table->dropColumn('req_plants');
            }
        });
    }
};