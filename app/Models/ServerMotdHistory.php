<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerMotdHistory extends Model
{
    protected $table = 'nm_server_motd_history';

    protected $fillable = [
        'server_id',
        'motd',
        'motd_clean',
        'first_seen_at',
        'last_seen_at',
    ];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(MonitoredServer::class, 'server_id');
    }

    public function cleanMotd(string $motd): string
    {
        // Remove Minecraft color codes (§ followed by any character)
        return preg_replace('/§./', '', $motd);
    }
}
