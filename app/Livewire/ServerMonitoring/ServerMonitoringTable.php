<?php

namespace App\Livewire\ServerMonitoring;

use App\Models\MonitoredServer;
use App\Services\MinecraftServerService;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class ServerMonitoringTable extends Component
{
    use WithPagination;

    public $search = '';
    public $sortField = 'current_players';
    public $sortDirection = 'desc';
    public $statusFilter = 'all'; // all, online, offline
    public $showAddForm = false;
    
    // Add server form fields
    public $name = '';
    public $ip_address = '';
    public $port = 25565;
    public $server_type = 'minecraft';
    

    
    protected $rules = [
        'name' => 'required|string|max:255',
        'ip_address' => 'required|string|max:255',
        'port' => 'required|integer|min:1|max:65535',
        'server_type' => 'required|string|max:50',
    ];

    public function mount()
    {
        // Check if user has permission to view server stats
        if (!$this->canViewServerStats()) {
            abort(403, 'You do not have permission to view server statistics.');
        }
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function toggleAddForm()
    {
        if (!$this->canEditServerStats()) {
            session()->flash('error', 'You do not have permission to add servers.');
            return;
        }
        
        $this->showAddForm = !$this->showAddForm;
        $this->resetForm();
    }

    public function addServer()
    {
        if (!$this->canEditServerStats()) {
            session()->flash('error', 'You do not have permission to add servers.');
            return;
        }

        $this->validate();

        MonitoredServer::create([
            'name' => $this->name,
            'ip_address' => $this->ip_address,
            'port' => $this->port,
            'server_type' => $this->server_type,
            'is_active' => true,
        ]);

        session()->flash('success', 'Server added successfully!');
        $this->resetForm();
        $this->showAddForm = false;
    }

    public function deleteServer($serverId)
    {
        if (!$this->canEditServerStats()) {
            session()->flash('error', 'You do not have permission to delete servers.');
            return;
        }

        $server = MonitoredServer::find($serverId);
        if ($server) {
            $server->delete();
            session()->flash('success', 'Server deleted successfully!');
        }
    }

    public function toggleServerStatus($serverId)
    {
        if (!$this->canEditServerStats()) {
            session()->flash('error', 'You do not have permission to modify servers.');
            return;
        }

        $server = MonitoredServer::find($serverId);
        if ($server) {
            $server->update(['is_active' => !$server->is_active]);
            session()->flash('success', 'Server status updated successfully!');
        }
    }

    public function pingServer($serverId)
    {
        if (!$this->canEditServerStats()) {
            session()->flash('error', 'You do not have permission to ping servers.');
            return;
        }

        try {
            $server = MonitoredServer::findOrFail($serverId);
            
            $service = new MinecraftServerService();
            $pingResult = $service->pingServer($server->ip_address, $server->port);
            
            if ($pingResult['success']) {
                $server->update([
                    'is_online' => true,
                    'current_players' => $pingResult['players']['online'] ?? 0,
                    'max_players' => $pingResult['players']['max'] ?? 0,
                    'current_motd' => $pingResult['description']['text'] ?? '',
                    'version' => $pingResult['version']['name'] ?? '',
                    'last_ping_at' => now(),
                ]);
                
                // Store server stats
                \App\Models\ServerStat::create([
                    'server_id' => $server->id,
                    'player_count' => $pingResult['players']['online'] ?? 0,
                    'max_players' => $pingResult['players']['max'] ?? 0,
                    'is_online' => true,
                    'ping_ms' => $pingResult['ping'] ?? null,
                    'version' => $pingResult['version']['name'] ?? null,
                    'tps' => $pingResult['tps'] ?? null,
                    'response_time_ms' => $pingResult['response_time_ms'] ?? null,
                    'plugins' => $pingResult['plugins'] ?? null,
                    'world_name' => $pingResult['world_name'] ?? null,
                    'recorded_at' => now(),
                ]);
                
                session()->flash('success', "Server {$server->name} pinged successfully!");
            } else {
                $server->update([
                    'is_online' => false,
                    'last_ping_at' => now(),
                ]);
                
                // Store offline stats
                \App\Models\ServerStat::create([
                    'server_id' => $server->id,
                    'player_count' => 0,
                    'max_players' => 0,
                    'is_online' => false,
                    'ping_ms' => null,
                    'version' => null,
                    'recorded_at' => now(),
                ]);
                
                session()->flash('error', "Failed to ping server {$server->name}: {$pingResult['error']}");
            }
            

        } catch (\Exception $e) {
            session()->flash('error', 'Error pinging server: ' . $e->getMessage());
        }
    }
    


    private function resetForm()
    {
        $this->name = '';
        $this->ip_address = '';
        $this->port = 25565;
        $this->server_type = 'minecraft';
        $this->resetErrorBag();
    }

    private function canViewServerStats(): bool
    {
        $user = Auth::user();
        return $user && (
            $user->hasPermissions('view_other_servers_stats') ||
            $user->is_admin
        );
    }

    private function canEditServerStats(): bool
    {
        $user = Auth::user();
        return $user && (
            $user->hasPermissions('edit_other_servers_stats') ||
            $user->is_admin
        );
    }

    public function getServersProperty()
    {
        $query = MonitoredServer::query();

        // Apply search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('ip_address', 'like', '%' . $this->search . '%');
            });
        }

        // Apply status filter
        if ($this->statusFilter === 'online') {
            $query->where('is_online', true);
        } elseif ($this->statusFilter === 'offline') {
            $query->where('is_online', false);
        }

        // Apply sorting
        $query->orderBy($this->sortField, $this->sortDirection);

        return $query->paginate(15);
    }

    public function render()
    {
        return view('livewire.server-monitoring.server-monitoring-table', [
            'servers' => $this->servers,
            'canEdit' => $this->canEditServerStats(),
        ]);
    }
}
