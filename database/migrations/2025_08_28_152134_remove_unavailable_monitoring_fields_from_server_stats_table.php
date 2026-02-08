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
        Schema::table('server_stats', function (Blueprint $table) {
            // Remove columns that cannot be obtained from standard server ping
            $table->dropColumn([
                'memory_used',
                'memory_max', 
                'cpu_usage',
                'disk_usage',
                'entities_count',
                'chunks_loaded'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('server_stats', function (Blueprint $table) {
            // Re-add the columns if migration is rolled back
            $table->bigInteger('memory_used')->nullable()->after('tps');
            $table->bigInteger('memory_max')->nullable()->after('memory_used');
            $table->decimal('cpu_usage', 5, 2)->nullable()->after('plugins');
            $table->bigInteger('disk_usage')->nullable()->after('cpu_usage');
            $table->integer('entities_count')->nullable()->after('world_name');
            $table->integer('chunks_loaded')->nullable()->after('entities_count');
        });
    }
};
