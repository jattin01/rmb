<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFailureReasonToSelectedOrdersTable extends Migration
{
    public function up()
    {
        Schema::table('selected_orders', function (Blueprint $table) {
            $table->string('failure_reason')->nullable()->after('end_time');
        });
    }

    public function down()
    {
        Schema::table('selected_orders', function (Blueprint $table) {
            $table->dropColumn('failure_reason');
        });
    }
}