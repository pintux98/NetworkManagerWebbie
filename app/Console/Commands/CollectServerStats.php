<?php

namespace App\Console\Commands;

use App\Models\MonitoredServer;
use App\Services\MinecraftServerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CollectServerStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'servers:collect-stats {--server-id= : Collect stats for a specific server ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Collect statistics from all active monitored Minecraft servers';

    protected MinecraftServerService $serverService;

    public function __construct(MinecraftServerService $serverService)
    {
        parent::__construct();
        $this->serverService = $serverService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting server statistics collection...');
        
        $serverId = $this->option('server-id');
        
        if ($serverId) {
            // Collect stats for a specific server
            $server = MonitoredServer::where('id', $serverId)
                ->where('is_active', true)
                ->first();
                
            if (!$server) {
                $this->error("Server with ID {$serverId} not found or not active.");
                return 1;
            }
            
            $this->collectServerStats($server);
        } else {
            // Collect stats for all active servers
            $servers = MonitoredServer::where('is_active', true)->get();
            
            if ($servers->isEmpty()) {
                $this->warn('No active servers found to monitor.');
                return 0;
            }
            
            $this->info("Found {$servers->count()} active servers to monitor.");
            
            foreach ($servers as $server) {
                $this->collectServerStats($server);
            }
        }
        
        $this->info('Server statistics collection completed.');
        return 0;
    }
    
    private function collectServerStats(MonitoredServer $server)
    {
        try {
            $this->line("Collecting stats for: {$server->name} ({$server->full_address})");
            
            $result = $this->serverService->updateServerData($server);
            
            if ($result['success']) {
                $status = $result['data']['is_online'] ? 'Online' : 'Offline';
                $players = $result['data']['is_online'] ? 
                    "{$result['data']['player_count']}/{$result['data']['max_players']}" : 'N/A';
                    
                $this->info("  ✓ Status: {$status}, Players: {$players}");
                
                if (isset($result['data']['ping_ms'])) {
                    $this->line("    Ping: {$result['data']['ping_ms']}ms");
                }
                
                if (isset($result['data']['version'])) {
                    $this->line("    Version: {$result['data']['version']}");
                }
            } else {
                $this->warn("  ✗ Failed to collect stats: {$result['message']}");
            }
            
        } catch (\Exception $e) {
            $this->error("  ✗ Error collecting stats for {$server->name}: {$e->getMessage()}");
            Log::error('Server stats collection failed', [
                'server_id' => $server->id,
                'server_name' => $server->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
