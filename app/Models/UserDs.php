<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserDs extends Model
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
    protected $table = 'user_ds';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

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
        'id',
        'username',
        'discriminator',
    ];

    /**
     * Get the links associated with this Discord user.
     */
    public function links(): HasMany
    {
        return $this->hasMany(Links::class, 'user_ds_id', 'id');
    }

    /**
     * Get the full Discord tag (username#discriminator).
     *
     * @return string
     */
    public function getDiscordTag(): string
    {
        // Handle new Discord username system (no discriminator)
        if (empty($this->discriminator) || $this->discriminator === '0') {
            return '@' . $this->username;
        }
        
        // Handle legacy Discord username system (with discriminator)
        return $this->username . '#' . $this->discriminator;
    }

    /**
     * Get the copyable Discord mention tag.
     *
     * @return string
     */
    public function getMentionTag(): string
    {
        return '<@' . $this->id . '>';
    }

    /**
     * Get the display username.
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->username;
    }
}