<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserMc extends Model
{
    /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'discord_links';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_mc';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'uuid';

    /**
     * The "type" of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username',
        'uuid',
    ];

    /**
     * Get the links associated with this Minecraft user.
     */
    public function links(): HasMany
    {
        return $this->hasMany(Links::class, 'user_mc_id', 'uuid');
    }

    /**
     * Find a Minecraft user by UUID.
     *
     * @param string $uuid
     * @return UserMc|null
     */
    public static function findByUuid(string $uuid): ?UserMc
    {
        return static::where('uuid', $uuid)->first();
    }

    /**
     * Find a Minecraft user by username.
     *
     * @param string $username
     * @return UserMc|null
     */
    public static function findByUsername(string $username): ?UserMc
    {
        return static::where('username', $username)->first();
    }
}