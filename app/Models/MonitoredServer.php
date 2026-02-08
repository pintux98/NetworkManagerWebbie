<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonitoredServer extends Model
{
    protected $table = 'monitored_servers';

    protected $fillable = [
        'name',
        'ip_address',
        'port',
        'server_type',
        'is_active',
        'last_ping_at',
        'is_online',
        'current_players',
        'max_players',
        'current_motd',
        'version',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_online' => 'boolean',
        'last_ping_at' => 'datetime',
        'current_players' => 'integer',
        'max_players' => 'integer',
        'port' => 'integer',
    ];

    public function stats(): HasMany
    {
        return $this->hasMany(ServerStat::class, 'server_id');
    }

    public function motdHistory(): HasMany
    {
        return $this->hasMany(ServerMotdHistory::class, 'server_id');
    }

    public function latestStats(): HasMany
    {
        return $this->stats()->orderBy('recorded_at', 'desc');
    }

    public function getFullAddressAttribute(): string
    {
        return $this->ip_address . ':' . $this->port;
    }

    public function getUptimePercentageAttribute(): float
    {
        $totalChecks = $this->stats()->count();
        if ($totalChecks === 0) {
            return 0;
        }
        
        $onlineChecks = $this->stats()->where('is_online', true)->count();
        return round(($onlineChecks / $totalChecks) * 100, 2);
    }

    public function getFormattedVersionAttribute(): string
    {
        if (!$this->version) {
            return 'Unknown';
        }

        // Check if version is a protocol number (numeric)
        if (is_numeric($this->version)) {
            $protocolVersion = ProtocolVersion::tryFrom((int) $this->version);
            return $protocolVersion ? $protocolVersion->name() : 'Protocol ' . $this->version;
        }

        // If it's already a version string, return as is
        return $this->version;
    }
}
