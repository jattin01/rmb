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
            $table->string('contact_person', 250)->nullable()->after('site_name');
            $table->string('email', 100)->nullable()->after('contact_person');
            $table->string('phone', 12)->nullable()->after('email');
            $table->string('country', 100)->nullable()->after('phone');
            $table->string('province', 100)->nullable()->after('country');
            $table->double('latitude')->nullable()->after('province');
            $table->double('longitude')->nullable()->after('latitude');
            $table->longText('address')->nullable()->after('longitude');
            $table->enum('status',ConstantHelper::ROW_STATUSES)->default(ConstantHelper::ACTIVE)->index();
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
