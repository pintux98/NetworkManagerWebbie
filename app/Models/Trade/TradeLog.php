<?php

namespace App\Models\Trade;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Player\Player;

class TradeLog extends Model
{
    protected $connection = 'survivaldb';
    protected $table = 'tradelog';
    
    protected $fillable = [
        'player1',
        'player2', 
        'message',
        'timestamp'
    ];
    
    protected $casts = [
        'timestamp' => 'datetime'
    ];
    
    public $timestamps = false;
    
    /**
     * Get player1 relationship
     */
    public function player1Relation()
    {
        return $this->belongsTo(Player::class, 'player1', 'name');
    }
    
    /**
     * Get player2 relationship
     */
    public function player2Relation()
    {
        return $this->belongsTo(Player::class, 'player2', 'name');
    }
    
    /**
     * Scope to group trades by transaction
     * Groups trades from "Trade started" to "Trade finished/cancelled"
     */
    public function scopeGroupedByTransaction(Builder $query)
    {
        return $query
            ->select([
                \DB::raw('MIN(tradelog.id) as min_id'),
                \DB::raw('MAX(tradelog.id) as max_id'),
                \DB::raw('LEAST(tradelog.player1, tradelog.player2) as player_a'),
                \DB::raw('GREATEST(tradelog.player1, tradelog.player2) as player_b'),
                \DB::raw('MIN(tradelog.timestamp) as trade_date'),
                \DB::raw('MAX(tradelog.timestamp) as trade_end_date'),
                \DB::raw('GROUP_CONCAT(tradelog.message ORDER BY tradelog.id SEPARATOR "||||") as messages'),
                \DB::raw('COUNT(*) as message_count')
            ])
            ->from('tradelog')
            ->groupBy([
                \DB::raw('LEAST(tradelog.player1, tradelog.player2)'),
                \DB::raw('GREATEST(tradelog.player1, tradelog.player2)'),
                \DB::raw('DATE(tradelog.timestamp)'),
                \DB::raw('HOUR(tradelog.timestamp)'),
                \DB::raw('FLOOR(MINUTE(tradelog.timestamp) / 5)')
            ])
            ->havingRaw('GROUP_CONCAT(tradelog.message ORDER BY tradelog.id SEPARATOR "||||") LIKE "%Trade started%"')
            ->havingRaw('(GROUP_CONCAT(tradelog.message ORDER BY tradelog.id SEPARATOR "||||") LIKE "%Trade finished%" OR GROUP_CONCAT(tradelog.message ORDER BY tradelog.id SEPARATOR "||||") LIKE "%Trade cancelled%")')
            ->orderBy('trade_date', 'desc');
    }
    
    /**
     * Parse trade messages to extract trade information
     */
    public static function parseTradeMessages($messages, $playerA = null, $playerB = null)
    {
        $messageArray = explode('||||', $messages);
        $tradeData = [
            'status' => 'unknown',
            'player1_items' => [],
            'player2_items' => [],
            'player1_coins' => 0,
            'player2_coins' => 0,
            'started' => false,
            'finished' => false,
            'cancelled' => false,
            'players' => []
        ];
        
        // Extract player names from messages if not provided
        if (!$playerA || !$playerB) {
            foreach ($messageArray as $message) {
                if (preg_match('/(\w+), (\w+), (.+)/', $message, $matches)) {
                    if (!in_array($matches[1], $tradeData['players'])) {
                        $tradeData['players'][] = $matches[1];
                    }
                    if (!in_array($matches[2], $tradeData['players'])) {
                        $tradeData['players'][] = $matches[2];
                    }
                }
            }
            $playerA = $tradeData['players'][0] ?? null;
            $playerB = $tradeData['players'][1] ?? null;
        }
        
        foreach ($messageArray as $message) {
            if (str_contains($message, 'Trade started')) {
                $tradeData['started'] = true;
                $tradeData['status'] = 'started';
            } elseif (str_contains($message, 'Trade finished')) {
                $tradeData['finished'] = true;
                $tradeData['status'] = 'finished';
            } elseif (str_contains($message, 'Trade cancelled')) {
                $tradeData['cancelled'] = true;
                $tradeData['status'] = 'cancelled';
            } elseif (str_contains($message, 'received') && str_contains($message, 'Coins:')) {
                // Parse coin received
                preg_match('/(\w+) received Coins: (\d+)/', $message, $matches);
                if (count($matches) >= 3) {
                    $player = $matches[1];
                    $amount = (int)$matches[2];
                    if ($player === $playerA) {
                        $tradeData['player1_coins'] += $amount;
                    } elseif ($player === $playerB) {
                        $tradeData['player2_coins'] += $amount;
                    }
                }
            } elseif (str_contains($message, 'offered') && str_contains($message, 'Coins:')) {
                // Parse coin offered (negative) - these are already handled by received
                // Just for completeness, we could track who offered what
            } elseif (str_contains($message, 'received') && str_contains($message, 'x ')) {
                // Parse item received
                preg_match('/(\w+) received (\d+)x (.+?) \((.+?)\)/', $message, $matches);
                if (count($matches) >= 5) {
                    $player = $matches[1];
                    $quantity = (int)$matches[2];
                    $itemName = $matches[3];
                    $itemType = $matches[4];
                    
                    $item = [
                        'name' => $itemName,
                        'quantity' => $quantity,
                        'type' => $itemType
                    ];
                    
                    if ($player === $playerA) {
                        $tradeData['player1_items'][] = $item;
                    } elseif ($player === $playerB) {
                        $tradeData['player2_items'][] = $item;
                    }
                }
            }
        }
        
        return $tradeData;
    }
    
    /**
     * Get formatted trade summary
     */
    public function getFormattedSummary()
    {
        $parsed = self::parseTradeMessages($this->messages);
        
        $summary = [];
        
        // Add items
        $allItems = array_merge($parsed['player1_items'], $parsed['player2_items']);
        foreach ($allItems as $item) {
            $summary[] = $item['quantity'] . 'x ' . $item['name'];
        }
        
        // Add coins if any
        $totalCoins = $parsed['player1_coins'] + $parsed['player2_coins'];
        if ($totalCoins > 0) {
            $summary[] = number_format($totalCoins) . ' Coins';
        }
        
        return implode(', ', $summary);
    }
}