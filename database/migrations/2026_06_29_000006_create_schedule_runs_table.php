<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks each background schedule-generation run so the UI can poll its status
 * (queued → processing → completed/failed) instead of blocking the HTTP request.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('schedule_runs')) {
            return;
        }
        Schema::create('schedule_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('group_company_id');
            $table->unsignedBigInteger('user_id');
            $table->date('schedule_date');
            $table->string('status')->default('queued'); // queued|processing|completed|failed
            $table->text('message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['group_company_id', 'user_id', 'schedule_date'], 'schedule_runs_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_runs');
    }
};
