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
        Schema::table('batching_plants', function (Blueprint $table) {
            $table->decimal('rated_capacity_m3_hr', 8, 2)->after('capacity');
            $table->boolean('is_primary')->default(true)->after('rated_capacity_m3_hr');
            $table->unsignedTinyInteger('activation_order')->default(1)->after('is_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('batching_plants', function (Blueprint $table) {
            //
        });
    }
};
