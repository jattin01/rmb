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
        Schema::create('live_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_company_id')->constrained("group_companies")->index("live_order_user_index");
            $table->boolean('is_technician_required')->default(0);
            $table->string('order_no');
            $table->string('customer');
            $table->foreignId('customer_id')->nullable()->constrained("customers")->index("live_order_cust_index");
            $table->string('project');
            $table->foreignId('project_id')->nullable()->constrained("customer_projects")->index("live_order_cust_proj_index");
            $table->string('site');
            $table->string('mix_code');
            $table->foreignId('cust_product_id')->nullable()->constrained("customer_products")->index("live_order_cust_prod_index");
            $table->double('quantity');
            $table->foreignId('structural_reference_id')->nullable()->constrained("structural_references")->index("ive_order_struc_ref_index");
            $table->dateTime('delivery_date')->default(NULL) -> index("live_order_del_date_index");
            $table->double('interval');
            $table->double('pump')->nullable();
            $table->integer("pump_qty")->default(ConstantHelper::DZERO);
            $table->string('location');
            $table->integer("travel_to_site") -> default(ConstantHelper::TRAVEL_TIME);
            $table->integer("return_to_plant") -> default(ConstantHelper::TRAVEL_TIME);
            $table->dateTime('planned_start_time')->nullable();
            $table->dateTime('planned_end_time')->nullable();
            $table->dateTime('actual_start_time')-> nullable() -> default(NULL);
            $table->dateTime('actual_end_time')-> nullable() -> default(NULL);
            $table->double('planned_deviation')->nullable();
            $table->double('actual_deviation')-> nullable() -> default(NULL);
            $table->string('deviation_reason')->nullable();
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
        Schema::dropIfExists('live_orders');
    }
};
