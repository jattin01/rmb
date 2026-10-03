<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * max_delay    – already exists; meaning changed from "postpone time" to
     *                "max acceptable duration-based delay" (no DDL needed).
     *
     * expected_duration – ideal duration in minutes (first pouring_start → last
     *                     pouring_end) as calculated during order preparation,
     *                     before scheduling with resource contention.
     *
     * actual_delay – computed after scheduling:
     *                actual_duration − expected_duration (in minutes).
     *                If actual_delay > max_delay, order is rejected.
     */
    public function up(): void
    {
        Schema::table('selected_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('selected_orders', 'expected_duration')) {
                $table->integer('expected_duration')->nullable()->after('max_delay')
                    ->comment('Expected ideal duration in minutes (pre-scheduling)');
            }
            if (!Schema::hasColumn('selected_orders', 'actual_delay')) {
                $table->integer('actual_delay')->nullable()->after('expected_duration')
                    ->comment('Actual delay = scheduled_duration - expected_duration (minutes)');
            }
            if (!Schema::hasColumn('selected_orders', 'tolerance_minutes')) {
                $table->integer('tolerance_minutes')->nullable()->after('actual_delay');
            }
            if (!Schema::hasColumn('selected_orders', 'base_interval')) {
                $table->integer('base_interval')->nullable()->after('min_interval');
            }
        });

        // Also add to orders table so values survive publishing
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'max_delay')) {
                $table->integer('max_delay')->nullable()->after('interval')
                    ->comment('Max Acceptable delay in minutes');
            }
            if (!Schema::hasColumn('orders', 'expected_duration')) {
                $table->integer('expected_duration')->nullable()->after('max_delay')
                    ->comment('Expected ideal duration in minutes');
            }
            if (!Schema::hasColumn('orders', 'actual_delay')) {
                $table->integer('actual_delay')->nullable()->after('expected_duration')
                    ->comment('Actual delay = scheduled_duration - expected_duration (minutes)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('selected_orders', function (Blueprint $table) {
            foreach (['expected_duration', 'actual_delay', 'tolerance_minutes', 'base_interval'] as $col) {
                if (Schema::hasColumn('selected_orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            foreach (['expected_duration', 'actual_delay', 'max_delay'] as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
