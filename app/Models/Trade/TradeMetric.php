<?php

namespace App\Models\Trade;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
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

    /**
     * Scope to group trades by transaction (same date and participants, including bidirectional)
     */
    public function scopeGroupedByTransaction($query)
    {
        return $query->leftJoin('trade_players as sp', 'trade_metrics.sender', '=', 'sp.id')
            ->leftJoin('trade_players as rp', 'trade_metrics.receiver', '=', 'rp.id')
            ->selectRaw('
                MIN(trade_metrics.id) as id,
                LEAST(trade_metrics.sender, trade_metrics.receiver) as player1,
                GREATEST(trade_metrics.sender, trade_metrics.receiver) as player2,
                trade_metrics.date,
                COUNT(*) as item_count,
                GROUP_CONCAT(DISTINCT CONCAT(trade_metrics.category, ":", trade_metrics.type) SEPARATOR ", ") as trade_types,
                GROUP_CONCAT(DISTINCT trade_metrics.specification SEPARATOR ", ") as items,
                SUM(trade_metrics.quantity) as total_quantity,
                MIN(trade_metrics.sender_server) as sender_server,
                MIN(trade_metrics.receiver_server) as receiver_server,
                (
                    SELECT tp1.name 
                    FROM trade_players tp1 
                    WHERE tp1.id = LEAST(trade_metrics.sender, trade_metrics.receiver) 
                    LIMIT 1
                ) as sender_name,
                (
                    SELECT tp2.name 
                    FROM trade_players tp2 
                    WHERE tp2.id = GREATEST(trade_metrics.sender, trade_metrics.receiver) 
                    LIMIT 1
                ) as receiver_name,
                (
                    SELECT tp1.uuid 
                    FROM trade_players tp1 
                    WHERE tp1.id = LEAST(trade_metrics.sender, trade_metrics.receiver) 
                    LIMIT 1
                ) as sender_uuid,
                (
                    SELECT tp2.uuid 
                    FROM trade_players tp2 
                    WHERE tp2.id = GREATEST(trade_metrics.sender, trade_metrics.receiver) 
                    LIMIT 1
                ) as receiver_uuid
            ')
            ->groupBy([DB::raw('LEAST(trade_metrics.sender, trade_metrics.receiver)'), DB::raw('GREATEST(trade_metrics.sender, trade_metrics.receiver)'), 'trade_metrics.date', 'trade_metrics.sender', 'trade_metrics.receiver'])
            ->orderBy('trade_metrics.date', 'desc');
    }

    /**
     * Get all items in this trade transaction (including bidirectional)
     */
    public function getTransactionItems()
    {
        $player1 = min($this->sender, $this->receiver);
        $player2 = max($this->sender, $this->receiver);
        
        return static::where('date', $this->date)
                    ->where(function($query) use ($player1, $player2) {
                        $query->where(function($q) use ($player1, $player2) {
                            $q->where('sender', $player1)->where('receiver', $player2);
                        })->orWhere(function($q) use ($player1, $player2) {
                            $q->where('sender', $player2)->where('receiver', $player1);
                        });
                    })
                    ->get();
    }

    /**
     * Get formatted trade summary
     */
    public function getTradeSummaryAttribute()
    {
        $items = $this->getTransactionItems();
        $summary = [];
        
        foreach ($items as $item) {
            $key = $item->category . ':' . $item->type;
            if (!isset($summary[$key])) {
                $summary[$key] = [
                    'category' => $item->category,
                    'type' => $item->type,
                    'items' => [],
                    'total_quantity' => 0
                ];
            }
            
            $summary[$key]['items'][] = $item->specification;
            $summary[$key]['total_quantity'] += $item->quantity;
        }
        
        return $summary;
    }
}