<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            DB::statement("UPDATE users SET username = email");
            DB::statement("UPDATE users
            SET group_id = (
                SELECT id FROM groups WHERE groups.id = (SELECT group_id FROM group_companies WHERE id = users.group_company_id LIMIT 1) LIMIT 1
            )");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
