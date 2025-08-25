<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Links extends Model
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
    protected $table = 'links';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'user_mc_id';

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
        'user_mc_id',
        'user_ds_id',
        'link_code',
    ];

    /**
     * Get the Discord user associated with this link.
     */
    public function discordUser(): BelongsTo
    {
        return $this->belongsTo(UserDs::class, 'user_ds_id', 'id');
    }

    /**
     * Get the Minecraft user associated with this link.
     */
    public function minecraftUser(): BelongsTo
    {
        return $this->belongsTo(UserMc::class, 'user_mc_id', 'uuid');
    }

    /**
     * Find a link by Minecraft UUID.
     *
     * @param string $uuid
     * @return Links|null
     */
    public static function findByMinecraftUuid(string $uuid): ?Links
    {
        return static::where('user_mc_id', $uuid)->first();
    }

    /**
     * Check if a Minecraft UUID is linked to Discord.
     *
     * @param string $uuid
     * @return bool
     */
    public static function isLinked(string $uuid): bool
    {
        return static::where('user_mc_id', $uuid)
            ->whereNotNull('user_ds_id')
            ->exists();
    }
}