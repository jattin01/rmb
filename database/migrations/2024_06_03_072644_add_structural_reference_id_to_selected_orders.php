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
            $table->foreignId('structural_reference_id')->nullable()->constrained("structural_references")->index("order_sel_struc_ref_index")->after('quantity');
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
