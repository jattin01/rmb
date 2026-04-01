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
    public function up()
{
    Schema::create('casting_structures', function (Blueprint $table) {
        $table->id();
        $table->string('group_company_id');
        $table->string('structure_name', 100);
        $table->integer('default_interval_minutes');
        $table->boolean('is_critical')->default(false); // raft, slab, continuous
        $table->timestamps();

        $table->index('group_company_id');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('casting_structures');
    }
};
