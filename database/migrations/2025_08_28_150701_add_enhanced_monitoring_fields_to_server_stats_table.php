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
            $table->decimal('tps', 5, 2)->nullable()->after('version'); // Ticks per second
            $table->bigInteger('memory_used')->nullable()->after('tps'); // Memory used in bytes
            $table->bigInteger('memory_max')->nullable()->after('memory_used'); // Max memory in bytes
            $table->integer('response_time_ms')->nullable()->after('memory_max'); // Response time in milliseconds
            $table->json('plugins')->nullable()->after('response_time_ms'); // Plugin list as JSON
            $table->decimal('cpu_usage', 5, 2)->nullable()->after('plugins'); // CPU usage percentage
            $table->bigInteger('disk_usage')->nullable()->after('cpu_usage'); // Disk usage in bytes
            $table->string('world_name')->nullable()->after('disk_usage'); // Main world name
            $table->integer('entities_count')->nullable()->after('world_name'); // Total entities
            $table->integer('chunks_loaded')->nullable()->after('entities_count'); // Loaded chunks
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('server_stats', function (Blueprint $table) {
            $table->dropColumn([
                'tps',
                'memory_used',
                'memory_max',
                'response_time_ms',
                'plugins',
                'cpu_usage',
                'disk_usage',
                'world_name',
                'entities_count',
                'chunks_loaded'
            ]);
        });
    }
};
