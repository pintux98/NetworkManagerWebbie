<?php

namespace App\Models\Vote;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class VoteStreak extends Model
{
    use HasFactory;

    protected $connection = 'ervoto';
    protected $table = 'streak';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'last_vote',
        'streak_value',
        'platform',
    ];

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'streak_value' => 'integer',
        'last_vote' => 'datetime',
    ];

    /**
     * Get the user that owns this streak.
     */
    public function user()
    {
        return $this->belongsTo(VoteUser::class, 'user_id', 'id');
    }

    /**
     * Get streak for a specific user and platform.
     */
    public static function getStreakForUserAndPlatform($userId, $platform)
    {
        return static::where('user_id', $userId)
                    ->where('platform', $platform)
                    ->first();
    }

    /**
     * Get formatted last vote date.
     */
    public function getFormattedLastVoteAttribute()
    {
        return $this->last_vote ? $this->last_vote->format('Y-m-d H:i:s') : null;
    }
}