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
        Schema::create('customer_team_member_access_rights', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_team_member_id');
            $table->foreign('customer_team_member_id', 'team_member_id')->references('id')->on('customer_team_members');
            $table->unsignedBigInteger('customer_project_id');
            $table->foreign('customer_project_id')->references('id')->on('customer_projects');
            $table->boolean('order_view');
            $table->boolean('order_create');
            $table->boolean('order_edit');
            $table->boolean('order_cancel');
            $table->boolean('chat');
            $table->enum('status', ConstantHelper::ROW_STATUSES)->default(ConstantHelper::ACTIVE)->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('NO ACTION');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('NO ACTION');
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('NO ACTION');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_team_member_access_rights');
    }
};
