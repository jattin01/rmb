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
        Schema::table('structural_references', function (Blueprint $table) {
            $table->double('pouring_wo_pump_time')->default(0)->after('group_company_id');
            $table->double('pouring_w_pump_time')->default(0)->after('pouring_wo_pump_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('structural_references', function (Blueprint $table) {
            $table->dropColumn(['pouring_wo_pump_time', 'pouring_w_pump_time']);
        });
    }
};
