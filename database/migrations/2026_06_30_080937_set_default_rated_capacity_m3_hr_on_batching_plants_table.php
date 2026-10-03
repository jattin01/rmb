<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batching_plants', function (Blueprint $table) {
            if (Schema::hasColumn('batching_plants', 'rated_capacity_m3_hr')) {
                $table->decimal('rated_capacity_m3_hr', 10, 2)
                      ->default(0.0)
                      ->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('batching_plants', function (Blueprint $table) {
            if (Schema::hasColumn('batching_plants', 'rated_capacity_m3_hr')) {
                $table->decimal('rated_capacity_m3_hr', 10, 2)
                      ->default(null)
                      ->change();
            }
        });
    }
};
