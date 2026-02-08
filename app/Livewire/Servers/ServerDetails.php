<?php

namespace App\Livewire\Servers;

use App\Models\MonitoredServer;
use App\Models\ServerStat;
use App\Services\MinecraftServerService;
use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class ServerDetails extends Component
{
    public MonitoredServer $server;
    public $serverStats = [];
    public $uptimeData = [];
    public $playerCountData = [];
    public $tracerouteResults = [];
    public $whoisData = [];
    public $isLoadingTraceroute = false;
    public $isLoadingWhois = false;
    public $showTraceroute = false;
    public $showWhois = false;
    public $currentServerData = [];

    public function mount($id)
    {
        $this->server = MonitoredServer::findOrFail($id);
        $this->loadCurrentServerData();
        $this->loadServerStats();
        $this->loadChartData();
    }
    
    public function loadCurrentServerData()
    {
        // Use cached data if available, otherwise ping server
        $serverService = app(MinecraftServerService::class);
        $this->currentServerData = $serverService->getServerDataWithCache($this->server);
    }

    public function loadServerStats()
    {
        $this->serverStats = ServerStat::where('server_id', $this->server->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
    }

    public function loadChartData()
    {
        // Get stats for the last 24 hours
        $stats = ServerStat::where('server_id', $this->server->id)
            ->where('created_at', '>=', Carbon::now()->subHours(24))
            ->orderBy('created_at', 'asc')
            ->get();

        $this->uptimeData = [];
        $this->playerCountData = [];

        foreach ($stats as $stat) {
            $timestamp = $stat->created_at->format('Y-m-d H:i:s');
            
            // Uptime data (1 for online, 0 for offline)
            $this->uptimeData[] = [
                'x' => $timestamp,
                'y' => $stat->is_online ? 1 : 0
            ];

            // Player count data
            $this->playerCountData[] = [
                'x' => $timestamp,
                'y' => $stat->current_players ?? 0
            ];
        }
    }

    public function runTraceroute()
    {
        $this->isLoadingTraceroute = true;
        $this->tracerouteResults = [];

        try {
            // Run traceroute command (Windows uses tracert)
            $host = $this->server->ip;
            $command = "tracert -h 15 {$host}";
            
            $output = shell_exec($command);
            
            if ($output) {
                $lines = explode("\n", $output);
                $results = [];
                
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (preg_match('/^\s*(\d+)\s+(.+)$/', $line, $matches)) {
                        $results[] = [
                            'hop' => $matches[1],
                            'details' => $matches[2]
                        ];
                    }
                }
                
                $this->tracerouteResults = $results;
            }
        } catch (\Exception $e) {
            $this->tracerouteResults = [['error' => 'Failed to run traceroute: ' . $e->getMessage()]];
        }

        $this->isLoadingTraceroute = false;
        $this->showTraceroute = true;
    }

    public function runWhois()
    {
        $this->isLoadingWhois = true;
        $this->whoisData = [];

        try {
            // Use a whois API service
            $host = $this->server->ip;
            
            // Try to get whois data using nslookup for basic info
            $command = "nslookup {$host}";
            $output = shell_exec($command);
            
            if ($output) {
                $this->whoisData = [
                    'raw_output' => $output,
                    'host' => $host,
                    'timestamp' => now()->format('Y-m-d H:i:s')
                ];
            }
        } catch (\Exception $e) {
            $this->whoisData = ['error' => 'Failed to run whois lookup: ' . $e->getMessage()];
        }

        $this->isLoadingWhois = false;
        $this->showWhois = true;
    }

    public function refreshData()
    {
        $this->loadCurrentServerData();
        $this->loadServerStats();
        $this->loadChartData();
        $this->dispatch('refreshCharts');
    }

    public function render()
    {
        return view('livewire.servers.server-details');
    }
}