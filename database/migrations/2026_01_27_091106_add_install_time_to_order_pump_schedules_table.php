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
        Schema::table('order_pump_schedules', function (Blueprint $table) {
            // Add install_time after batching_qty
            $table->integer('install_time')->nullable()->after('batching_qty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_pump_schedules', function (Blueprint $table) {
            $table->dropColumn('install_time');
        });
    }
};
