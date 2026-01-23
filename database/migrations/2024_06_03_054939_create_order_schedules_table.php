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
        Schema::create('order_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId("order_id")->constrained("orders")->index("order_sch_order_index");
            $table->foreignId('group_company_id')->constrained("group_companies")->index("order_sch_grp_index");
            $table->date("schedule_date");
            $table->string("order_no");
            $table->string("location");
            $table->integer("trip");
            $table->string("mix_code");
            $table->foreignId('cust_product_id')->nullable()->constrained("customer_products")->index("order_sch_cust_prod_index");
            $table->string("batching_plant");
            $table->foreignId('batching_plant_id')->nullable()->constrained("batching_plants")->index("order_sch_bp_index");
            $table->string("transit_mixer");
            $table->foreignId('transit_mixer_id')->nullable()->constrained("transit_mixers")->index("order_sch_tm_index");
            $table->string("pump") -> nullable();
            $table->foreignId('pump_id')->nullable()->constrained("pumps")->index("order_sch_pump_n_index");
            $table->integer("batching_qty");
            $table->integer("loading_time");
            $table->dateTime("loading_start")->default(NULL);
            $table->dateTime("loading_end")->default(NULL);
            $table->integer("qc_time");
            $table->dateTime("qc_start")->default(NULL);
            $table->dateTime("qc_end")->default(NULL);
            $table->integer("travel_time");
            $table->dateTime("travel_start")->default(NULL);
            $table->dateTime("travel_end")->default(NULL);
            $table->integer("insp_time");
            $table->dateTime("insp_start")->default(NULL);
            $table->dateTime("insp_end")->default(NULL);
            $table->integer("pouring_time");
            $table->dateTime("pouring_start")->default(NULL);
            $table->dateTime("pouring_end")->default(NULL);
            $table->integer("cleaning_time");
            $table->dateTime("cleaning_start")->default(NULL);
            $table->dateTime("cleaning_end")->default(NULL);
            $table->integer("return_time");
            $table->dateTime("return_start")->default(NULL);
            $table->dateTime("return_end")->default(NULL);
            $table->dateTime("delivery_start")->default(NULL);
            $table->string("deviation");
            $table->string("deviation_reason") -> nullable();
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
        Schema::dropIfExists('selected_order_schedules');
    }
};
