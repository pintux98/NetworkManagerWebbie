<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UltraPlaytimeUserData extends Model
{
    protected $connection = 'survivaldb';
    protected $table = 'uptime_user_data';
    protected $primaryKey = 'player_uuid';
    public $incrementing = false;
    public $timestamps = false;
    
    protected $fillable = [
        'player_uuid',
        'playtime',
        'rewards'
    ];
    
    protected $casts = [
        'playtime' => 'integer',
        'rewards' => 'string'
    ];
    
    /**
     * Convert UUID string to binary format for database storage
     * 
     * @param string $uuid
     * @return string
     */
    public static function uuidToBytes($uuid)
    {
        // Remove dashes from UUID
        $hex = str_replace('-', '', $uuid);
        
        // Convert hex string to binary
        return hex2bin($hex);
    }
    
    /**
     * Convert binary UUID from database to string format
     * 
     * @param string $bytes
     * @return string
     */
    public static function bytesToUuid($bytes)
    {
        // Convert binary to hex
        $hex = bin2hex($bytes);
        
        // Format as UUID with dashes
        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12)
        );
    }
    
    /**
     * Get playtime data for a specific player UUID
     * 
     * @param string $uuid
     * @return array|null
     */
    public static function getPlaytimeByUuid($uuid)
    {
        try {
            $binaryUuid = self::uuidToBytes($uuid);
            
            $result = DB::connection('survivaldb')
                ->table('uptime_user_data')
                ->where('player_uuid', $binaryUuid)
                ->first();
            
            if ($result) {
                return [
                    'uuid' => $uuid,
                    'playtime' => $result->playtime,
                    'rewards' => $result->rewards
                ];
            }
            
            return null;
        } catch (\Exception $e) {
            \Log::error('Error fetching UltraPlaytime data: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Format playtime from milliseconds to human readable format
     * 
     * @param int $milliseconds
     * @return string
     */
    public static function formatPlaytime($milliseconds)
    {
        if (!$milliseconds) {
            return '0 seconds';
        }
        
        $seconds = floor($milliseconds / 1000);
        
        $years = floor($seconds / (365 * 24 * 3600));
        $seconds %= (365 * 24 * 3600);
        
        $months = floor($seconds / (30 * 24 * 3600));
        $seconds %= (30 * 24 * 3600);
        
        $days = floor($seconds / (24 * 3600));
        $seconds %= (24 * 3600);
        
        $hours = floor($seconds / 3600);
        $seconds %= 3600;
        
        $minutes = floor($seconds / 60);
        $seconds %= 60;
        
        $parts = [];
        
        if ($years > 0) $parts[] = $years . ' year' . ($years > 1 ? 's' : '');
        if ($months > 0) $parts[] = $months . ' month' . ($months > 1 ? 's' : '');
        if ($days > 0) $parts[] = $days . ' day' . ($days > 1 ? 's' : '');
        if ($hours > 0) $parts[] = $hours . ' hour' . ($hours > 1 ? 's' : '');
        if ($minutes > 0) $parts[] = $minutes . ' minute' . ($minutes > 1 ? 's' : '');
        if ($seconds > 0) $parts[] = $seconds . ' second' . ($seconds > 1 ? 's' : '');
        
        if (empty($parts)) {
            return '0 seconds';
        }
        
        return implode(', ', $parts);
    }
}