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
        Schema::table('product_types', function (Blueprint $table) {
            $table->integer('batching_creation_time')->after('type')->nullable();
            $table->integer('temperature_creation_time')->after('batching_creation_time')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_types', function (Blueprint $table) {
            //
        });
    }
};
