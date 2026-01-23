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
            $table->unsignedBigInteger('og_order_id') -> after('id');
            $table->foreign('og_order_id') -> references('id') -> on('orders');
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
