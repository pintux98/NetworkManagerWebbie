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
        Schema::connection('manager')->table('accountgroups', function (Blueprint $table) {
            $table->boolean('view_economy_logs')->default(false)->after('view_command_log');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('manager')->table('accountgroups', function (Blueprint $table) {
            $table->dropColumn('view_economy_logs');
        });
    }
};
