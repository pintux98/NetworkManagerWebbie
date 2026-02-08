<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MonitoredServer;
use App\Models\ServerStat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ServerMonitoringController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function chartData(Request $request)
    {
        // Check permissions
        $user = Auth::user();
        if (!$user || (!$user->hasPermission('view_other_servers_stats') && !$user->is_admin)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $search = $request->get('search', '');
        $statusFilter = $request->get('statusFilter', 'all');
        $hours = $request->get('hours', 24); // Default to last 24 hours

        // Get filtered servers
        $serversQuery = MonitoredServer::query();
        
        if ($search) {
            $serversQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('ip_address', 'like', '%' . $search . '%');
            });
        }

        if ($statusFilter === 'online') {
            $serversQuery->where('is_online', true);
        } elseif ($statusFilter === 'offline') {
            $serversQuery->where('is_online', false);
        }

        $servers = $serversQuery->where('is_active', true)->get();

        if ($servers->isEmpty()) {
            return response()->json([
                'series' => [],
                'message' => 'No servers found matching the criteria'
            ]);
        }

        // Get stats for the last specified hours
        $startTime = Carbon::now()->subHours($hours);
        $series = [];

        foreach ($servers as $server) {
            $stats = ServerStat::where('server_id', $server->id)
                ->where('recorded_at', '>=', $startTime)
                ->orderBy('recorded_at')
                ->get();

            if ($stats->isNotEmpty()) {
                $data = [];
                foreach ($stats as $stat) {
                    $data[] = [
                        'x' => $stat->recorded_at->timestamp * 1000, // Highcharts expects milliseconds
                        'y' => $stat->player_count
                    ];
                }

                $series[] = [
                    'name' => $server->name,
                    'data' => $data,
                    'tooltip' => [
                        'valueSuffix' => ' players'
                    ]
                ];
            }
        }

        return response()->json([
            'series' => $series,
            'timeRange' => [
                'start' => $startTime->timestamp * 1000,
                'end' => Carbon::now()->timestamp * 1000
            ]
        ]);
    }

    public function serverStats(Request $request, $serverId)
    {
        // Check permissions
        $user = Auth::user();
        if (!$user || (!$user->hasPermission('view_other_servers_stats') && !$user->is_admin)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $server = MonitoredServer::findOrFail($serverId);
        $hours = $request->get('hours', 24);
        $startTime = Carbon::now()->subHours($hours);

        $stats = ServerStat::where('server_id', $server->id)
            ->where('recorded_at', '>=', $startTime)
            ->orderBy('recorded_at')
            ->get();

        $data = [];
        foreach ($stats as $stat) {
            $data[] = [
                'timestamp' => $stat->recorded_at->timestamp * 1000,
                'player_count' => $stat->player_count,
                'max_players' => $stat->max_players,
                'is_online' => $stat->is_online,
                'ping_ms' => $stat->ping_ms,
                'version' => $stat->version
            ];
        }

        return response()->json([
            'server' => [
                'id' => $server->id,
                'name' => $server->name,
                'address' => $server->full_address,
                'current_status' => [
                    'is_online' => $server->is_online,
                    'current_players' => $server->current_players,
                    'max_players' => $server->max_players,
                    'uptime_percentage' => $server->uptime_percentage
                ]
            ],
            'stats' => $data,
            'timeRange' => [
                'start' => $startTime->timestamp * 1000,
                'end' => Carbon::now()->timestamp * 1000
            ]
        ]);
    }

    public function summary(Request $request)
    {
        // Check permissions
        $user = Auth::user();
        if (!$user || (!$user->hasPermission('view_other_servers_stats') && !$user->is_admin)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $servers = MonitoredServer::where('is_active', true)->get();
        
        $totalServers = $servers->count();
        $onlineServers = $servers->where('is_online', true)->count();
        $totalPlayers = $servers->sum('current_players');
        $maxPlayers = $servers->sum('max_players');
        
        // Calculate average uptime
        $avgUptime = $servers->avg('uptime_percentage') ?? 0;

        return response()->json([
            'summary' => [
                'total_servers' => $totalServers,
                'online_servers' => $onlineServers,
                'offline_servers' => $totalServers - $onlineServers,
                'total_players' => $totalPlayers,
                'max_players' => $maxPlayers,
                'average_uptime' => round($avgUptime, 2)
            ],
            'servers' => $servers->map(function ($server) {
                return [
                    'id' => $server->id,
                    'name' => $server->name,
                    'address' => $server->full_address,
                    'is_online' => $server->is_online,
                    'current_players' => $server->current_players,
                    'max_players' => $server->max_players,
                    'uptime_percentage' => $server->uptime_percentage,
                    'last_ping_at' => $server->last_ping_at?->toISOString()
                ];
            })
        ]);
    }
}
