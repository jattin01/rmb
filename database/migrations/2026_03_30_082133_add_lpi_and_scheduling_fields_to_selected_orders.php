<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('selected_orders', function (Blueprint $table) {
            // LPI scores
            $table->decimal('lpi_score', 6, 2)->nullable()->after('priority');
            $table->decimal('lpi_v', 5, 2)->nullable()->after('lpi_score');
            $table->decimal('lpi_p', 5, 2)->nullable()->after('lpi_v');
            $table->decimal('lpi_c', 5, 2)->nullable()->after('lpi_p');

            // Delay & PM approval
            $table->boolean('requires_pm_approval')->default(false)->after('failure_reason');
            $table->integer('delay_minutes')->nullable()->after('requires_pm_approval');
            $table->decimal('delay_percentage', 5, 2)->nullable()->after('delay_minutes');
            $table->timestamp('pm_approved_at')->nullable()->after('delay_percentage');
            $table->foreignId('pm_approved_by')->nullable()->constrained('users')->after('pm_approved_at');

            // Casting structure
            $table->string('casting_type', 50)->nullable()->after('mix_code');

            // Multi-plant split
            $table->foreignId('secondary_plant_id')->nullable()->after('location');
            $table->decimal('secondary_plant_qty', 8, 2)->nullable()->after('secondary_plant_id');

            // Index for the new sort column
            $table->index('lpi_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('selected_orders', function (Blueprint $table) {
            //
        });
    }
};
