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
        // Create monitored_servers table
        Schema::create('monitored_servers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('ip_address');
            $table->integer('port')->default(25565);
            $table->string('server_type')->default('minecraft');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_ping_at')->nullable();
            $table->boolean('is_online')->default(false);
            $table->integer('current_players')->default(0);
            $table->integer('max_players')->default(0);
            $table->text('current_motd')->nullable();
            $table->string('version')->nullable();
            $table->timestamps();
            
            $table->index(['is_active', 'is_online']);
            $table->index('last_ping_at');
        });

        // Create server_stats table for historical data
        Schema::create('server_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained('monitored_servers')->onDelete('cascade');
            $table->integer('player_count');
            $table->integer('max_players');
            $table->boolean('is_online');
            $table->integer('ping_ms')->nullable();
            $table->string('version')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();
            
            $table->index(['server_id', 'recorded_at']);
            $table->index('recorded_at');
        });

        // Create server_motd_history table
        Schema::create('server_motd_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained('monitored_servers')->onDelete('cascade');
            $table->text('motd');
            $table->text('motd_clean')->nullable(); // MOTD without formatting codes
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->timestamps();
            
            $table->index(['server_id', 'first_seen_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_motd_history');
        Schema::dropIfExists('server_stats');
        Schema::dropIfExists('monitored_servers');
    }
};
