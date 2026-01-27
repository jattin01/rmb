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
        Schema::table('pump_occupancies', function (Blueprint $table) {
            $table->integer('pump_id')->after('id')->nullable();
            $table->dateTime('creation_date')->after('pump_id')->nullable();
            $table->dateTime('start_time')->after('creation_date')->nullable();
            $table->dateTime('end_time')->after('start_time')->nullable();
            $table->integer('occupied')->after('end_time')->nullable();
            $table->string('current_status')->after('occupied')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pump_occupancies', function (Blueprint $table) {
            //
        });
    }
};
