<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * used_pump    : pumps actually usable for the order given plant availability
     *                (<= pump_qty). Drives the parallel-pump expected_duration.
     * standby_pump : the remainder (pump_qty - used_pump) that can't be fed by
     *                the available plants and sit on standby.
     * Both set by OrderController::calculatePumpUsage().
     */
    public function up(): void
    {
        Schema::table('selected_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('selected_orders', 'used_pump')) {
                $table->unsignedSmallInteger('used_pump')
                    ->nullable()
                    ->after('pump_qty')
                    ->comment('Pumps usable given plant availability (<= pump_qty)');
            }
            if (!Schema::hasColumn('selected_orders', 'standby_pump')) {
                $table->unsignedSmallInteger('standby_pump')
                    ->nullable()
                    ->after('used_pump')
                    ->comment('Pumps that cannot be fed by available plants (pump_qty - used_pump)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('selected_orders', function (Blueprint $table) {
            foreach (['standby_pump', 'used_pump'] as $col) {
                if (Schema::hasColumn('selected_orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};