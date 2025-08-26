<?php

namespace App\Models\Trade;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Player\Player;

class TradePlayer extends Model
{
    use HasFactory;

    protected $connection = 'survivaldb';
    protected $table = 'trade_players';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'name',
    ];

    protected $casts = [
        'id' => 'integer',
    ];

    /**
     * Get the trade metrics where this player is the sender.
     */
    public function sentTrades()
    {
        return $this->hasMany(TradeMetric::class, 'sender', 'id');
    }

    /**
     * Get the trade metrics where this player is the receiver.
     */
    public function receivedTrades()
    {
        return $this->hasMany(TradeMetric::class, 'receiver', 'id');
    }

    /**
     * Get all trade metrics for this player (both sent and received).
     */
    public function allTrades()
    {
        return TradeMetric::where('sender', $this->id)
                         ->orWhere('receiver', $this->id)
                         ->orderBy('date', 'desc');
    }

    /**
     * Find trade player by UUID.
     */
    public static function findByUuid($uuid)
    {
        return static::where('uuid', $uuid)->first();
    }

    /**
     * Get the main player model from the manager database.
     */
    public function getMainPlayer()
    {
        return Player::where('uuid', $this->uuid)->first();
    }

    /**
     * Get formatted player name with link to player profile.
     */
    public function getFormattedNameAttribute()
    {
        return $this->name;
    }
}