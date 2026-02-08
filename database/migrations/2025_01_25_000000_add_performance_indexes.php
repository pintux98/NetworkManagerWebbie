<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add indexes for players table (check if they don't exist first)
        Schema::connection('manager')->table('players', function (Blueprint $table) {
            // Check if indexes exist before creating them
            $indexes = DB::connection('manager')->select("SHOW INDEX FROM nm_players WHERE Key_name = 'idx_players_uuid'");
            if (empty($indexes)) {
                $table->index('uuid', 'idx_players_uuid');
            }
            
            $indexes = DB::connection('manager')->select("SHOW INDEX FROM nm_players WHERE Key_name = 'idx_players_firstlogin'");
            if (empty($indexes)) {
                $table->index('firstlogin', 'idx_players_firstlogin');
            }
            
            $indexes = DB::connection('manager')->select("SHOW INDEX FROM nm_players WHERE Key_name = 'idx_players_lastlogin'");
            if (empty($indexes)) {
                $table->index('lastlogin', 'idx_players_lastlogin');
            }
            
            $indexes = DB::connection('manager')->select("SHOW INDEX FROM nm_players WHERE Key_name = 'idx_players_ip'");
            if (empty($indexes)) {
                $table->index('ip', 'idx_players_ip');
            }
            
            $indexes = DB::connection('manager')->select("SHOW INDEX FROM nm_players WHERE Key_name = 'idx_players_online'");
            if (empty($indexes)) {
                $table->index('online', 'idx_players_online');
            }
        });

        // Add indexes for sessions table
        Schema::connection('manager')->table('sessions', function (Blueprint $table) {
            $table->index('uuid', 'idx_sessions_uuid');
            $table->index('start', 'idx_sessions_start');
            $table->index('end', 'idx_sessions_end');
            // Use raw SQL for composite index with limited length
            DB::connection('manager')->statement('CREATE INDEX idx_sessions_uuid_start ON nm_sessions (uuid(36), start)');
        });

        // Add indexes for punishments table
        Schema::connection('manager')->table('punishments', function (Blueprint $table) {
            $table->index('uuid', 'idx_punishments_uuid');
            $table->index('type', 'idx_punishments_type');
            $table->index('time', 'idx_punishments_time');
            $table->index('punisher', 'idx_punishments_punisher');
            $table->index('active', 'idx_punishments_active');
        });
        
        // Add composite index for punishments with raw SQL
        DB::connection('manager')->statement('CREATE INDEX idx_punishments_uuid_type ON nm_punishments (uuid(36), type(10))');

        // Add indexes for player_ping table
        Schema::connection('manager')->table('player_ping', function (Blueprint $table) {
            $table->index('uuid', 'idx_player_ping_uuid');
            $table->index('time', 'idx_player_ping_time');
        });

        // Add indexes for logins table (if it exists)
        if (Schema::connection('manager')->hasTable('logins')) {
            Schema::connection('manager')->table('logins', function (Blueprint $table) {
                $table->index('uuid', 'idx_logins_uuid');
                $table->index('ip', 'idx_logins_ip');
                $table->index('time', 'idx_logins_time');
            });
            
            // Add composite index for logins with raw SQL
            DB::connection('manager')->statement('CREATE INDEX idx_logins_uuid_time ON nm_logins (uuid(36), time)');
        }

        // Add indexes for players_ignored table (if it exists)
        if (Schema::connection('manager')->hasTable('players_ignored')) {
            Schema::connection('manager')->table('players_ignored', function (Blueprint $table) {
                $table->index('uuid', 'idx_players_ignored_uuid');
                $table->index('ignored_uuid', 'idx_players_ignored_ignored_uuid');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes for players table
        Schema::connection('manager')->table('players', function (Blueprint $table) {
            $table->dropIndex('idx_players_uuid');
            $table->dropIndex('idx_players_firstlogin');
            $table->dropIndex('idx_players_lastlogin');
            $table->dropIndex('idx_players_ip');
            $table->dropIndex('idx_players_online');
        });

        // Drop indexes for sessions table
        Schema::connection('manager')->table('sessions', function (Blueprint $table) {
            $table->dropIndex('idx_sessions_uuid');
            $table->dropIndex('idx_sessions_start');
            $table->dropIndex('idx_sessions_end');
        });
        
        // Drop composite index with raw SQL
        DB::connection('manager')->statement('DROP INDEX idx_sessions_uuid_start ON nm_sessions');

        // Drop indexes for punishments table
        Schema::connection('manager')->table('punishments', function (Blueprint $table) {
            $table->dropIndex('idx_punishments_uuid');
            $table->dropIndex('idx_punishments_type');
            $table->dropIndex('idx_punishments_time');
            $table->dropIndex('idx_punishments_punisher');
            $table->dropIndex('idx_punishments_active');
        });
        
        // Drop composite index with raw SQL
        DB::connection('manager')->statement('DROP INDEX idx_punishments_uuid_type ON nm_punishments');

        // Drop indexes for player_ping table
        Schema::connection('manager')->table('player_ping', function (Blueprint $table) {
            $table->dropIndex('idx_player_ping_uuid');
            $table->dropIndex('idx_player_ping_time');
        });

        // Drop indexes for logins table (if it exists)
        if (Schema::connection('manager')->hasTable('logins')) {
            Schema::connection('manager')->table('logins', function (Blueprint $table) {
                $table->dropIndex('idx_logins_uuid');
                $table->dropIndex('idx_logins_ip');
                $table->dropIndex('idx_logins_time');
            });
            
            // Drop composite index with raw SQL
            DB::connection('manager')->statement('DROP INDEX idx_logins_uuid_time ON nm_logins');
        }

        // Drop indexes for players_ignored table (if it exists)
        if (Schema::connection('manager')->hasTable('players_ignored')) {
            Schema::connection('manager')->table('players_ignored', function (Blueprint $table) {
                $table->dropIndex('idx_players_ignored_uuid');
                $table->dropIndex('idx_players_ignored_ignored_uuid');
            });
        }
    }
};