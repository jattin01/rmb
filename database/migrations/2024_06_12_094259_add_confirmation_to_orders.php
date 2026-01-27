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
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('has_customer_confirmed') -> default(0) -> after('order_status');
            $table->boolean('approval_status') -> default(0) -> after('has_customer_confirmed');
            $table->string('customer_confirm_remarks') -> after('has_customer_confirmed') -> nullable() -> default(null);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            //
        });
    }
};
