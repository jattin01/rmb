<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Helpers\ConstantHelper;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Schema::table('selected_orders', function (Blueprint $table) {
        //     $table->unsignedBigInteger('site_id')->after('site')->nullable();


        // });

        if (Schema::hasColumn('selected_orders', 'site_id')) {
            // Column exists, you can perform actions here
            Schema::table('selected_orders', function (Blueprint $table) {
                // For example, drop the column
                $table->dropColumn('site_id');
            });
        } else {
            // Column does not exist
            Schema::table('selected_orders', function (Blueprint $table) {
                // For example, add the column
                $table->unsignedBigInteger('site_id')->after('site')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('selected_orders', function (Blueprint $table) {
            $table->dropColumn('site_id');
        });
    }
};
