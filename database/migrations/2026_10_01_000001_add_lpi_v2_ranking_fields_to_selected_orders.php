<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * LPI v2: the scheduling sequence now comes from the LPI battle rules
 * (LpiRankingService), and the LPI score is kept for audit only.
 *
 * Adds the ranking result to selected_orders and makes every order start
 * FLEXIBLE (doc section 3); the dispatcher marks non-flexible orders in step 2.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('selected_orders', function (Blueprint $table) {
            $table->unsignedInteger('lpi_sequence')->nullable()->after('lpi_c');
            $table->unsignedInteger('lpi_initial_position')->nullable()->after('lpi_sequence');
            $table->string('lpi_zone', 10)->nullable()->after('lpi_initial_position');
            $table->boolean('lpi_promoted')->default(false)->after('lpi_zone');
            $table->decimal('lpi_speed', 8, 2)->nullable()->after('lpi_promoted');
            $table->string('lpi_reason', 255)->nullable()->after('lpi_speed');

            $table->index('lpi_sequence');
        });

        if (Schema::hasColumn('selected_orders', 'flexibility')) {
            DB::statement('ALTER TABLE selected_orders ALTER COLUMN flexibility SET DEFAULT 1');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('selected_orders', 'flexibility')) {
            DB::statement('ALTER TABLE selected_orders ALTER COLUMN flexibility SET DEFAULT 0');
        }

        Schema::table('selected_orders', function (Blueprint $table) {
            $table->dropIndex(['lpi_sequence']);
            $table->dropColumn([
                'lpi_sequence',
                'lpi_initial_position',
                'lpi_zone',
                'lpi_promoted',
                'lpi_speed',
                'lpi_reason',
            ]);
        });
    }
};
