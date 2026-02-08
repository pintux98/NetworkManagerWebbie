<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerStat extends Model
{
    protected $table = 'server_stats';

    protected $fillable = [
        'server_id',
        'player_count',
        'max_players',
        'is_online',
        'ping_ms',
        'version',
        'recorded_at',
        // Enhanced fields available from server ping
        'tps',
        'response_time_ms',
        'plugins',
        'world_name',
    ];

    protected $casts = [
        'is_online' => 'boolean',
        'player_count' => 'integer',
        'max_players' => 'integer',
        'ping_ms' => 'integer',
        'recorded_at' => 'datetime',
        // Enhanced fields that exist in database
        'tps' => 'decimal:2',
        'response_time_ms' => 'integer',
        'plugins' => 'json',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(MonitoredServer::class, 'server_id');
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
