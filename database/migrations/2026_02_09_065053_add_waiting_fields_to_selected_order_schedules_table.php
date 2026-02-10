<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('selected_order_schedules', function (Blueprint $table) {

            $table->dateTime('waiting_start')
                ->nullable()
                ->after('insp_end');

            $table->dateTime('waiting_end')
                ->nullable()
                ->after('waiting_start');

            $table->integer('waiting_time')
                ->default(0)
                ->after('waiting_end')
                ->comment('Waiting time in minutes between insp_end and pouring_start');
        });
        Schema::table('order_schedules', function (Blueprint $table) {

            $table->dateTime('waiting_start')
                ->nullable()
                ->after('insp_end');

            $table->dateTime('waiting_end')
                ->nullable()
                ->after('waiting_start');

            $table->integer('waiting_time')
                ->default(0)
                ->after('waiting_end')
                ->comment('Waiting time in minutes between insp_end and pouring_start');
        });

    }

    public function down(): void
    {
        Schema::table('selected_order_schedules', function (Blueprint $table) {

            $table->dropColumn([
                'waiting_start',
                'waiting_end',
                'waiting_time',
            ]);
        });
        Schema::table('order_schedules', function (Blueprint $table) {

            $table->dropColumn([
                'waiting_start',
                'waiting_end',
                'waiting_time',
            ]);
        });
    }
};
