<?php

use App\Helpers\ConstantHelper;
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
        Schema::table('company_locations', function (Blueprint $table) {
            $table->enum('status', ConstantHelper::ROW_STATUSES)->after('address') ->default(ConstantHelper::ACTIVE)->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_locations', function (Blueprint $table) {
            //
        });
    }
};
