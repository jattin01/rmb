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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_company_id')->constrained("group_companies")->index("product_company_index");
            $table->unsignedBigInteger('product_type_id')->nullable();
            $table->string('code', 200)->nullable();
            $table->string('name', 200)->nullable();
            $table->string('usage', 255)->nullable();
            $table->double('density_per_cum')->default(0);
            $table->double('cement_per_cum')->default(0);
            $table->double('flyash_per_cum')->default(0);
            $table->double('water_per_cum')->default(0);
            $table->double('sand_per_cum')->default(0);
            $table->double('aggregate_10mm_per_cum')->default(0);
            $table->double('aggregate_20mm_per_cum')->default(0);
            $table->double('admixture_per_cum')->default(0);
            $table->foreign('product_type_id')->references('id')->on('product_types')->onDelete('NO ACTION');
            $table->index('product_type_id');
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
        Schema::dropIfExists('products');
    }
};
