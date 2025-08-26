<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Player\Player;

class Xconomy extends Model
{
    use HasFactory;

    protected $connection = 'survivaldb';
    protected $table = 'xconomy';
    protected $primaryKey = 'UID';
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'UID',
        'player',
        'balance',
        'hidden',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'hidden' => 'integer',
    ];

    /**
     * Get the player associated with this economy record.
     */
    public function playerRecord()
    {
        return $this->belongsTo(Player::class, 'UID', 'uuid');
    }

    /**
     * Scope to filter by player name.
     */
    public function scopeByPlayer($query, $playerName)
    {
        return $query->where('player', 'like', '%' . $playerName . '%');
    }

    /**
     * Scope to filter by player UUID.
     */
    public function scopeByUuid($query, $uuid)
    {
        return $query->where('UID', $uuid);
    }

    /**
     * Get formatted balance with currency symbol.
     */
    public function getFormattedBalanceAttribute()
    {
        return '$' . number_format($this->balance, 2);
    }

    /**
     * Check if the balance is hidden.
     */
    public function isHidden()
    {
        return $this->hidden === 1;
    }

    /**
     * Get balance for a specific player UUID.
     */
    public static function getBalanceByUuid($uuid)
    {
        $record = static::where('UID', $uuid)->first();
        return $record ? $record->balance : 0;
    }

    /**
     * Get formatted balance for a specific player UUID.
     */
    public static function getFormattedBalanceByUuid($uuid)
    {
        $balance = static::getBalanceByUuid($uuid);
        return '$' . number_format($balance, 2);
    }
}