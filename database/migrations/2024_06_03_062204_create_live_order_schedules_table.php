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
        Schema::create('live_order_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId("order_id")->constrained("live_orders")->index("pub_live_order_sch_order_index");
            $table->foreignId('group_company_id')->constrained("group_companies")->index("pub_live_order_sch_grp_index");
            $table->date("schedule_date");
            $table->string("order_no");
            $table->string("location");
            $table->integer("trip");
            $table->string("mix_code");
            $table->foreignId('cust_product_id')->nullable()->constrained("customer_products")->index("pub_live_sch_cust_prod_index");
            $table->string("batching_plant");
            $table->foreignId('batching_plant_id')->nullable()->constrained("batching_plants")->index("live_order_sch_bp_index");
            $table->string("transit_mixer");
            $table->foreignId('transit_mixer_id')->nullable()->constrained("transit_mixers")->index("live_order_sch_tm_index");
            $table->string("pump") -> nullable();
            $table->foreignId('pump_id')->nullable()->constrained("pumps")->index("live_order_sch_pump_index");
            $table->integer("batching_qty");

            $table->integer("planned_loading_time");
            $table->dateTime("planned_loading_start")->default(NULL);
            $table->dateTime("planned_loading_end")->default(NULL);
            $table->integer("actual_loading_time")-> nullable() -> default(NULL);
            $table->dateTime("actual_loading_start")-> nullable() -> default(NULL);
            $table->dateTime("actual_loading_end")-> nullable() -> default(NULL);

            $table->integer("planned_qc_time");
            $table->dateTime("planned_qc_start")->default(NULL);
            $table->dateTime("planned_qc_end")->default(NULL);
            $table->integer("actual_qc_time")-> nullable() -> default(NULL);
            $table->dateTime("actual_qc_start")-> nullable() -> default(NULL);
            $table->dateTime("actual_qc_end")-> nullable() -> default(NULL);

            $table->integer("planned_travel_time");
            $table->dateTime("planned_travel_start")->default(NULL);
            $table->dateTime("planned_travel_end")->default(NULL);
            $table->integer("actual_travel_time")-> nullable() -> default(NULL);
            $table->dateTime("actual_travel_start")-> nullable() -> default(NULL);
            $table->dateTime("actual_travel_end")-> nullable() -> default(NULL);


            $table->integer("planned_insp_time");
            $table->dateTime("planned_insp_start")->default(NULL);
            $table->dateTime("planned_insp_end")->default(NULL);
            $table->integer("actual_insp_time")-> nullable() -> default(NULL);
            $table->dateTime("actual_insp_start")-> nullable() -> default(NULL);
            $table->dateTime("actual_insp_end")-> nullable() -> default(NULL);

            $table->integer("planned_pouring_time");
            $table->dateTime("planned_pouring_start")->default(NULL);
            $table->dateTime("planned_pouring_end")->default(NULL);
            $table->integer("actual_pouring_time")-> nullable() -> default(NULL);
            $table->dateTime("actual_pouring_start")-> nullable() -> default(NULL);
            $table->dateTime("actual_pouring_end")-> nullable() -> default(NULL);

            $table->integer("planned_cleaning_time");
            $table->dateTime("planned_cleaning_start")->default(NULL);
            $table->dateTime("planned_cleaning_end")->default(NULL);
            $table->integer("actual_cleaning_time")-> nullable() -> default(NULL);
            $table->dateTime("actual_cleaning_start")-> nullable() -> default(NULL);
            $table->dateTime("actual_cleaning_end")-> nullable() -> default(NULL);

            $table->integer("planned_return_time");
            $table->dateTime("planned_return_start")->default(NULL);
            $table->dateTime("planned_return_end")->default(NULL);
            $table->integer("actual_return_time")-> nullable() -> default(NULL);
            $table->dateTime("actual_return_start")-> nullable() -> default(NULL);
            $table->dateTime("actual_return_end")-> nullable() -> default(NULL);

            $table->dateTime("planned_delivery_start")->default(NULL);
            $table->dateTime("actual_delivery_start")-> nullable() -> default(NULL);

            $table->string("planned_deviation");
            $table->string("actual_deviation")-> nullable() -> default(NULL);
            
            $table->string("deviation_reason") -> nullable() -> default(NULL);

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
        Schema::dropIfExists('live_order_schedules');
    }
};
