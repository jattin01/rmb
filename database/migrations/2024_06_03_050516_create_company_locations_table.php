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
        Schema::create('company_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_company_id') -> constrained("group_companies");
            $table->index('group_company_id');
            $table->string('location');
            $table->string('site_name');
            $table->double('distance')->default(ConstantHelper::DZERO);
            $table->string('time_1to6');
            $table->string('time_6to9');
            $table->string('time_9to12');
            $table->string('time_12to15');
            $table->string('time_15to18');
            $table->string('time_18to21');
            $table->string('time_21to1');
            $table->enum('status', ConstantHelper::ROW_STATUSES)->default(ConstantHelper::ACTIVE)->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('NO ACTION');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('NO ACTION');
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('NO ACTION');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_locations');
    }
};
