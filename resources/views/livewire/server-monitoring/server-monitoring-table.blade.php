<div>
    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Server Monitoring</h2>
        @if($canEdit)
            <button wire:click="toggleAddForm" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Server
            </button>
        @endif
    </div>

    <!-- Add Server Form -->
    @if($showAddForm)
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Add New Server</h5>
            </div>
            <div class="card-body">
                <form wire:submit.prevent="addServer">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Server Name</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       wire:model="name" id="name" placeholder="My Minecraft Server">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="ip_address" class="form-label">IP Address</label>
                                <input type="text" class="form-control @error('ip_address') is-invalid @enderror" 
                                       wire:model="ip_address" id="ip_address" placeholder="127.0.0.1 or mc.example.com">
                                @error('ip_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="port" class="form-label">Port</label>
                                <input type="number" class="form-control @error('port') is-invalid @enderror" 
                                       wire:model="port" id="port" min="1" max="65535">
                                @error('port') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="server_type" class="form-label">Server Type</label>
                                <select class="form-select @error('server_type') is-invalid @enderror" 
                                        wire:model="server_type" id="server_type">
                                    <option value="minecraft">Minecraft</option>
                                    <option value="bedrock">Minecraft Bedrock</option>
                                </select>
                                @error('server_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Add Server
                        </button>
                        <button type="button" wire:click="toggleAddForm" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Filters and Search -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" wire:model.live="search" 
                               id="search" placeholder="Search servers...">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="statusFilter" class="form-label">Status Filter</label>
                        <select class="form-select" wire:model.live="statusFilter" id="statusFilter">
                            <option value="all">All Servers</option>
                            <option value="online">Online Only</option>
                            <option value="offline">Offline Only</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Quick Actions</label>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-primary btn-sm" wire:click="$refresh">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Servers Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Monitored Servers ({{ $servers->total() }})</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th wire:click="sortBy('name')" style="cursor: pointer;">
                                Server Name
                                @if($sortField === 'name')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </th>
                            <th wire:click="sortBy('ip_address')" style="cursor: pointer;">
                                Address
                                @if($sortField === 'ip_address')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </th>
                            <th wire:click="sortBy('current_players')" style="cursor: pointer;">
                                Players
                                @if($sortField === 'current_players')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </th>
                            <th>MOTD</th>
                            <th>Version</th>
                            <th wire:click="sortBy('last_ping_at')" style="cursor: pointer;">
                                Last Check
                                @if($sortField === 'last_ping_at')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </th>
                            <th>Uptime</th>
                            @if($canEdit)
                                <th>Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($servers as $server)
                            <tr>
                                <td>
                                    <div class="px-3 py-2 rounded" style="background-color: {{ $server->is_online ? '#d4edda' : '#f8d7da' }}; border: 1px solid {{ $server->is_online ? '#c3e6cb' : '#f5c6cb' }};">
                                        <strong class="text-{{ $server->is_online ? 'success' : 'danger' }}">{{ $server->name }}</strong>
                                    </div>
                                </td>
                                <td>
                                    <code>{{ $server->full_address }}</code>
                                </td>
                                <td>
                                    @if($server->is_online)
                                        <span class="badge bg-primary">
                                            {{ $server->current_players }}/{{ $server->max_players }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($server->current_motd)
                                        <div class="motd-container" style="max-width: 200px;">
                                            <div class="motd-text" style="white-space: pre-wrap; word-break: break-word; font-size: 0.875rem; line-height: 1.2;" title="{{ strip_tags($server->current_motd) }}">
                                                {!! nl2br(e($server->current_motd)) !!}
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($server->version)
                                        <small class="text-muted">{{ $server->formatted_version }}</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($server->last_ping_at)
                                        <small class="text-muted" title="{{ $server->last_ping_at->format('Y-m-d H:i:s') }}">
                                            {{ $server->last_ping_at->diffForHumans() }}
                                        </small>
                                    @else
                                        <span class="text-muted">Never</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ $server->uptime_percentage }}%</span>
                                </td>
                                @if($canEdit)
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('server-monitoring.details', $server->id) }}" 
                                               class="btn btn-outline-info" 
                                               title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button class="btn btn-outline-primary" 
                                                    wire:click="pingServer({{ $server->id }})" 
                                                    title="Ping Server">
                                                <i class="fas fa-satellite-dish"></i>
                                            </button>
                                            <button class="btn btn-outline-danger" 
                                                    wire:click="deleteServer({{ $server->id }})" 
                                                    onclick="return confirm('Are you sure you want to delete this server?')" 
                                                    title="Delete Server">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canEdit ? '8' : '7' }}" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-server fa-2x mb-2"></i>
                                        <p>No servers found.</p>
                                        @if($canEdit)
                                            <button wire:click="toggleAddForm" class="btn btn-primary">
                                                <i class="fas fa-plus"></i> Add Your First Server
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($servers->hasPages())
            <div class="card-footer">
                {{ $servers->links() }}
            </div>
        @endif
    </div>

    <!-- Player Count Chart -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Player Count Over Time</h5>
        </div>
        <div class="card-body">
            <div id="playerCountChart" style="height: 300px; width: 100%;"></div>
        </div>
    </div>



    @push('scripts')
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize player count chart
            initPlayerCountChart();
            
            // Listen for Livewire updates to refresh chart
            Livewire.on('refreshChart', () => {
                initPlayerCountChart();
            });
        });

        function initPlayerCountChart() {
            // Fetch chart data via AJAX
            fetch('/api/server-monitoring/chart-data?' + new URLSearchParams({
                search: @this.search,
                statusFilter: @this.statusFilter,
                sortField: @this.sortField,
                sortDirection: @this.sortDirection
            }))
            .then(response => response.json())
            .then(data => {
                Highcharts.chart('playerCountChart', {
                    chart: {
                        type: 'line',
                        backgroundColor: 'transparent'
                    },
                    title: {
                        text: 'Player Count Over Time'
                    },
                    xAxis: {
                        type: 'datetime',
                        title: {
                            text: 'Time'
                        }
                    },
                    yAxis: {
                        title: {
                            text: 'Player Count'
                        },
                        min: 0
                    },
                    tooltip: {
                        shared: true,
                        crosshairs: true
                    },
                    legend: {
                        enabled: true
                    },
                    series: data.series || []
                });
            })
            .catch(error => {
                console.error('Error loading chart data:', error);
                document.getElementById('playerCountChart').innerHTML = 
                    '<div class="text-center text-muted py-5"><i class="fas fa-exclamation-triangle"></i> Error loading chart data</div>';
            });
        }
    </script>
    @endpush
</div>
