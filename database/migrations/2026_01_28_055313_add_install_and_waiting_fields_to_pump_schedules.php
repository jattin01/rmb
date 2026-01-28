<?php 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('selected_order_pump_schedules', function (Blueprint $table) {
            $this->addColumns($table);
        });

        Schema::table('order_pump_schedules', function (Blueprint $table) {
            $this->addColumns($table);
        });
    }

    public function down(): void
    {
        Schema::table('selected_order_pump_schedules', function (Blueprint $table) {
            $this->dropColumns($table);
        });

        Schema::table('order_pump_schedules', function (Blueprint $table) {
            $this->dropColumns($table);
        });
    }

    private function addColumns(Blueprint $table): void
    {
        $table->integer('install_time')->nullable()->after('insp_end');
        $table->dateTime('install_start')->nullable()->after('install_time');
        $table->dateTime('install_end')->nullable()->after('install_start');

        $table->integer('waiting_time')->nullable()->after('install_end');
        $table->dateTime('waiting_start')->nullable()->after('waiting_time');
        $table->dateTime('waiting_end')->nullable()->after('waiting_start');
    }

    private function dropColumns(Blueprint $table): void
    {
        $table->dropColumn([
            'install_time',
            'install_start',
            'install_end',
            'waiting_time',
            'waiting_start',
            'waiting_end',
        ]);
    }
};
