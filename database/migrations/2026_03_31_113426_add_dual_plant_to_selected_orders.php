<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration 3 of 3
 *
 * Adds dual-plant tracking columns to selected_orders.
 *
 * These are written by determineDualPlantMode() and assignBatchingPlantDual()
 * inside ScheduleService so the UI and reports can identify which orders used
 * two batching plants simultaneously and why.
 *
 * Columns added to selected_orders:
 *
 *   dual_plant_mode       boolean       — true = this order was scheduled across two plants
 *   dual_plant_primary    varchar(100)  — name of the plant used for odd trips  (1,3,5…)
 *   dual_plant_secondary  varchar(100)  — name of the plant used for even trips (2,4,6…)
 *   dual_plant_reason     varchar(20)   — why dual-plant was triggered:
 *                                          'rate'      → required m³/hr > single plant capacity
 *                                          'structure' → casting structure is_critical = true
 *                                          'both'      → both triggers fired
 *                                          NULL        → single-plant order
 *
 * How to query all dual-plant orders for a shift:
 *   SelectedOrder::where('dual_plant_mode', true)
 *       ->whereBetween('delivery_date', [$shiftStart, $shiftEnd])
 *       ->get(['order_no','dual_plant_reason','dual_plant_primary','dual_plant_secondary']);
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('selected_orders', function (Blueprint $table) {

        $table->boolean('is_critical')
                ->default(false)
                ->after('delay_minutes');

        $table->boolean('dual_plant_mode')
                ->default(false)
                ->after('is_critical')
                ->comment('True when this order was scheduled across two batching plants');

            $table->string('dual_plant_primary', 100)
                ->nullable()
                ->after('dual_plant_mode')
                ->comment('Plant name used for odd-numbered trips when dual_plant_mode = true');

            $table->string('dual_plant_secondary', 100)
                ->nullable()
                ->after('dual_plant_primary')
                ->comment('Plant name used for even-numbered trips when dual_plant_mode = true');

            // Enum-like: only four allowed values, kept as varchar for flexibility
            $table->string('dual_plant_reason', 20)
                ->nullable()
                ->after('dual_plant_secondary')
                ->comment('rate | structure | both — why dual-plant was triggered');
        });
    }

    public function down(): void
    {
        Schema::table('selected_orders', function (Blueprint $table) {
            $table->dropColumn([
                'dual_plant_mode',
                'dual_plant_primary',
                'dual_plant_secondary',
                'dual_plant_reason',
            ]);
        });
    }
};