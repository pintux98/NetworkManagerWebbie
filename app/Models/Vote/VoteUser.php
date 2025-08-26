<?php

namespace App\Models\Vote;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoteUser extends Model
{
    use HasFactory;

    protected $connection = 'ervoto';
    protected $table = 'user';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'username',
    ];

    protected $casts = [
        'id' => 'integer',
    ];

    /**
     * Get the vote streaks for this user.
     */
    public function streaks()
    {
        return $this->hasMany(VoteStreak::class, 'user_id', 'id');
    }

    /**
     * Get the vote streak for a specific platform.
     */
    public function getStreakForPlatform($platform)
    {
        return $this->streaks()->where('platform', $platform)->first();
    }

    /**
     * Find user by UUID.
     */
    public static function findByUuid($uuid)
    {
        return static::where('uuid', $uuid)->first();
    }
}