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
        Schema::table('selected_orders', function (Blueprint $table) {
            $table->integer('loading_time')->nullable()->after('pouring_time');
            $table->integer("min_interval")->nullable()->after('interval');
            $table->integer("max_interval")->nullable()->after('min_interval');
            $table->integer("max_delay")->nullable()->after('max_interval');
            $table->integer("tolerance")->nullable()->after('max_delay');
            $table->string("item_type")->nullable()->after('quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('selected_orders', function (Blueprint $table) {
            $table->dropColumn('loading_time');
            $table->dropColumn('min_interval');
            $table->dropColumn('max_interval');
            $table->dropColumn('max_delay');
            $table->dropColumn('tolerance');
                $table->dropColumn('item_type');
        });
    }
};
