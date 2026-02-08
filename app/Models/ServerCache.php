<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerCache extends Model
{
    protected $table = 'server_cache';
    
    protected $fillable = [
        'server_id',
        'cached_data',
        'last_updated',
    ];
    
    protected $casts = [
        'cached_data' => 'array',
        'last_updated' => 'datetime',
    ];
    
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
    
    /**
     * Check if cache is expired (older than 5 minutes)
     */
    public function isExpired(): bool
    {
        return $this->last_updated->diffInMinutes(now()) >= 5;
    }
    
    /**
     * Update cache with new data
     */
    public function updateCache(array $data): void
    {
        $this->update([
            'cached_data' => $data,
            'last_updated' => now(),
        ]);
    }
}
