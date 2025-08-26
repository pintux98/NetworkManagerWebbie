<?php

namespace App\Models\Trade;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TradeMetric extends Model
{
    use HasFactory;

    protected $connection = 'survivaldb';
    protected $table = 'trade_metrics';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'sender',
        'sender_server',
        'sender_world',
        'receiver',
        'receiver_server',
        'receiver_world',
        'category',
        'type',
        'specification',
        'quantity',
        'date',
    ];

    protected $casts = [
        'id' => 'integer',
        'sender' => 'integer',
        'receiver' => 'integer',
        'date' => 'datetime',
    ];

    /**
     * Get the sender player.
     */
    public function senderPlayer()
    {
        return $this->belongsTo(TradePlayer::class, 'sender', 'id');
    }

    /**
     * Get the receiver player.
     */
    public function receiverPlayer()
    {
        return $this->belongsTo(TradePlayer::class, 'receiver', 'id');
    }

    /**
     * Get formatted date.
     */
    public function getFormattedDateAttribute()
    {
        return $this->date ? $this->date->format('Y-m-d H:i:s') : null;
    }

    /**
     * Get human readable date.
     */
    public function getHumanDateAttribute()
    {
        return $this->date ? $this->date->diffForHumans() : null;
    }

    /**
     * Get formatted category and type.
     */
    public function getFormattedTypeAttribute()
    {
        return ucfirst($this->category) . ' - ' . ucfirst($this->type);
    }



    /**
     * Scope to get trades for a specific player.
     */
    public function scopeForPlayer($query, $playerId)
    {
        return $query->where('sender', $playerId)
                    ->orWhere('receiver', $playerId);
    }

    /**
     * Scope to get recent trades.
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('date', '>=', now()->subDays($days));
    }

    /**
     * Get the direction of the trade for a specific player
     */
    public function getDirectionForPlayer(int $playerId): string
    {
        return $this->sender === $playerId ? 'sent' : 'received';
    }

    /**
     * Get the other player involved in the trade
     */
    public function getOtherPlayer(int $playerId): ?TradePlayer
    {
        if ($this->sender === $playerId) {
            return $this->receiverPlayer;
        } elseif ($this->receiver === $playerId) {
            return $this->senderPlayer;
        }
        
        return null;
    }
}