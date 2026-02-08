<?php

namespace App\Console\Commands;

use App\Models\MonitoredServer;
use App\Services\MinecraftServerService;
use Illuminate\Console\Command;

class RefreshServerCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:refresh-servers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh cached server data for all monitored servers';

    /**
     * Execute the console command.
     */
    public function handle(MinecraftServerService $serverService)
    {
        $this->info('Starting server cache refresh...');
        
        $servers = MonitoredServer::all();
        $refreshed = 0;
        $failed = 0;
        
        foreach ($servers as $server) {
            try {
                $this->line("Refreshing cache for {$server->name} ({$server->ip}:{$server->port})");
                
                // Ping server and update cache
                $pingResult = $serverService->pingServer($server);
                
                if ($pingResult['success'] && $pingResult['online']) {
                    $serverService->updateServerCache($server, $pingResult);
                    $refreshed++;
                    $this->info("✓ Cache updated for {$server->name}");
                } else {
                    $this->warn("⚠ Server {$server->name} is offline, cache not updated");
                }
                
            } catch (\Exception $e) {
                $failed++;
                $this->error("✗ Failed to refresh cache for {$server->name}: {$e->getMessage()}");
            }
        }
        
        $this->info("Cache refresh completed. Refreshed: {$refreshed}, Failed: {$failed}");
        
        return Command::SUCCESS;
    }
}
