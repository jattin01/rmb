<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('selected_order_pump_schedules', function (Blueprint $table) {
            if (!Schema::hasColumn('selected_order_pump_schedules', 'is_standby')) {
                $table->boolean('is_standby')->default(false)->after('pump');
            }
        });
    }

    public function down(): void
    {
        Schema::table('selected_order_pump_schedules', function (Blueprint $table) {
            if (Schema::hasColumn('selected_order_pump_schedules', 'is_standby')) {
                $table->dropColumn('is_standby');
            }
        });
    }
};
