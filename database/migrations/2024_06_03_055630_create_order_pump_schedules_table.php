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
        Schema::create('order_pump_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId("order_id")->constrained("orders")->index("pump_sch_order_id");
            $table->foreignId('group_company_id')->constrained("group_companies")->index("pump_sch_grp_index");
            $table->date("schedule_date");
            $table->string("order_no");
            $table->string("pump");
            $table->foreignId('pump_id')->nullable()->constrained("pumps")->index("order_psch_pump_index");
            $table->string("mix_code");
            $table->foreignId('cust_product_id')->nullable()->constrained("customer_products")->index("order_psch_cust_prod_index");
            $table->string("location");
            $table->integer("trip");
            $table->integer("batching_qty");
            $table->integer("qc_time");
            $table->dateTime("qc_start") -> default(null);
            $table->dateTime("qc_end") -> default(null);
            $table->integer("travel_time");
            $table->dateTime("travel_start") -> default(null);
            $table->dateTime("travel_end") -> default(null);
            $table->integer("insp_time");
            $table->dateTime("insp_start") -> default(null);
            $table->dateTime("insp_end") -> default(null);
            $table->integer("pouring_time");
            $table->dateTime("pouring_start") -> default(null);
            $table->dateTime("pouring_end") -> default(null);
            $table->integer("cleaning_time");
            $table->dateTime("cleaning_start") -> default(null);
            $table->dateTime("cleaning_end") -> default(null);
            $table->integer("return_time") -> default(0);
            $table->dateTime("return_start") -> nullable();
            $table->dateTime("return_end") -> nullable();
            $table->dateTime("delivery_start") -> default(null);
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
        Schema::dropIfExists('order_pump_schedules');
    }
};
