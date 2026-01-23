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
        Schema::create('batching_plant_availability', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_company_id')->constrained("group_companies");
            $table->foreignId("user_id")->constrained()->index("bp_avl_user_index");
            $table->string("plant_name");
            $table->double("plant_capacity");
            $table->dateTime("free_from")->default(NULL);
            $table->dateTime("free_upto")->default(NULL);
            $table->string("location");
            $table->string("reason");
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
        Schema::dropIfExists('batching_plant_availability');
    }
};
