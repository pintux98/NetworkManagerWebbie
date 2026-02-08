<div class="container-fluid py-4">
    <!-- Server Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-1">{{ $server->name }}</h2>
                            <p class="text-muted mb-0">
                                <i class="fas fa-server me-2"></i>{{ $server->ip }}:{{ $server->port }}
                                @if($serverStats->isNotEmpty())
                                    <span class="badge {{ $serverStats->first()->is_online ? 'bg-success' : 'bg-danger' }} ms-2">
                                        {{ $serverStats->first()->is_online ? 'Online' : 'Offline' }}
                                    </span>
                                @endif
                            </p>
                        </div>
                        <div>
                            <button wire:click="refreshData" class="btn btn-outline-primary">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Server Stats Cards -->
    <div class="row mb-4">
        @if($serverStats->isNotEmpty())
            @php $latestStat = $serverStats->first(); @endphp
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Players Online</h5>
                        <h3 class="text-primary">{{ $latestStat->player_count ?? 0 }}/{{ $latestStat->max_players ?? 0 }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Response Time</h5>
                        <h3 class="text-info">{{ $latestStat->response_time_ms ?? 'N/A' }}ms</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Version</h5>
                        <h3 class="text-success">{{ $latestStat->formatted_version ?? 'Unknown' }}</h3>
                    </div>
                </div>
            </div>

        @endif
    </div>

    <!-- Charts Section -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Uptime (Last 24 Hours)</h5>
                </div>
                <div class="card-body">
                    <canvas id="uptimeChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Player Count (Last 24 Hours)</h5>
                </div>
                <div class="card-body">
                    <canvas id="playerChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Network Tools Section -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Traceroute</h5>
                    <button wire:click="runTraceroute" class="btn btn-sm btn-primary" 
                            {{ $isLoadingTraceroute ? 'disabled' : '' }}>
                        @if($isLoadingTraceroute)
                            <i class="fas fa-spinner fa-spin"></i> Running...
                        @else
                            <i class="fas fa-route"></i> Run Traceroute
                        @endif
                    </button>
                </div>
                <div class="card-body">
                    @if($showTraceroute && !empty($tracerouteResults))
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Hop</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($tracerouteResults as $result)
                                        <tr>
                                            @if(isset($result['error']))
                                                <td colspan="2" class="text-danger">{{ $result['error'] }}</td>
                                            @else
                                                <td>{{ $result['hop'] }}</td>
                                                <td><small>{{ $result['details'] }}</small></td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">Click "Run Traceroute" to analyze the network path to this server.</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">WHOIS Information</h5>
                    <button wire:click="runWhois" class="btn btn-sm btn-info" 
                            {{ $isLoadingWhois ? 'disabled' : '' }}>
                        @if($isLoadingWhois)
                            <i class="fas fa-spinner fa-spin"></i> Loading...
                        @else
                            <i class="fas fa-search"></i> Run WHOIS
                        @endif
                    </button>
                </div>
                <div class="card-body">
                    @if($showWhois && !empty($whoisData))
                        @if(isset($whoisData['error']))
                            <div class="alert alert-danger">{{ $whoisData['error'] }}</div>
                        @else
                            <div class="mb-2">
                                <strong>Host:</strong> {{ $whoisData['host'] }}<br>
                                <strong>Lookup Time:</strong> {{ $whoisData['timestamp'] }}
                            </div>
                            <pre class="bg-light p-3 rounded" style="max-height: 300px; overflow-y: auto;">{{ $whoisData['raw_output'] }}</pre>
                        @endif
                    @else
                        <p class="text-muted">Click "Run WHOIS" to get domain/IP information for this server.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Server History -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Server History</h5>
                </div>
                <div class="card-body">
                    @if($serverStats->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Status</th>
                                        <th>Players</th>
                                        <th>Version</th>
                                        <th>Response Time</th>

                                        <th>World</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($serverStats as $stat)
                                        <tr>
                                            <td>{{ $stat->created_at->format('M d, Y H:i:s') }}</td>
                                            <td>
                                                <span class="badge {{ $stat->is_online ? 'bg-success' : 'bg-danger' }}">
                                                    {{ $stat->is_online ? 'Online' : 'Offline' }}
                                                </span>
                                            </td>
                                            <td>{{ $stat->current_players ?? 0 }}/{{ $stat->max_players ?? 0 }}</td>
                                            <td>{{ $stat->version ?? 'N/A' }}</td>
                                            <td>{{ $stat->response_time_ms ?? 'N/A' }}ms</td>
    
                                            <td>{{ $stat->world_name ?? 'N/A' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">No server statistics available yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
<script>
    let uptimeChart, playerChart;

    function destroyCharts() {
        if (uptimeChart) {
            uptimeChart.destroy();
            uptimeChart = null;
        }
        if (playerChart) {
            playerChart.destroy();
            playerChart = null;
        }
    }

    function initCharts() {
        // Destroy existing charts first
        destroyCharts();

        // Uptime Chart
        const uptimeCtx = document.getElementById('uptimeChart');
        if (uptimeCtx) {
            uptimeChart = new Chart(uptimeCtx, {
                type: 'line',
                data: {
                    datasets: [{
                        label: 'Server Status',
                        data: @json($uptimeData),
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.1,
                        stepped: true
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        x: {
                            type: 'time',
                            time: {
                                parser: 'YYYY-MM-DD HH:mm:ss',
                                displayFormats: {
                                    hour: 'HH:mm'
                                }
                            }
                        },
                        y: {
                            min: 0,
                            max: 1,
                            ticks: {
                                callback: function(value) {
                                    return value === 1 ? 'Online' : 'Offline';
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }

        // Player Count Chart
        const playerCtx = document.getElementById('playerChart');
        if (playerCtx) {
            playerChart = new Chart(playerCtx, {
                type: 'line',
                data: {
                    datasets: [{
                        label: 'Player Count',
                        data: @json($playerCountData),
                        borderColor: 'rgb(255, 99, 132)',
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        x: {
                            type: 'time',
                            time: {
                                parser: 'YYYY-MM-DD HH:mm:ss',
                                displayFormats: {
                                    hour: 'HH:mm'
                                }
                            }
                        },
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
    }

    // Initialize charts when page loads
    document.addEventListener('DOMContentLoaded', function() {
        initCharts();
    });

    // Listen for refresh event from Livewire
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('refreshCharts', () => {
            setTimeout(initCharts, 100);
        });
    });
</script>
@endpush