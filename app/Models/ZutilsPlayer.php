<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZutilsPlayer extends Model
{
    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'zutils';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'players';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'playerUUID';

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
        'playerUUID',
        'nickname',
        'lastJoin',
        'ip',
        'realName',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'lastJoin' => 'integer',
    ];

    /**
     * Convert Minecraft color codes to HTML.
     *
     * @param string|null $nickname
     * @return string|null
     */
    public static function parseMinecraftColors($nickname)
    {
        if (!$nickname) {
            return null;
        }

        // Minecraft color codes mapping
        $colorMap = [
            '&0' => '<span style="color: #000000;">',  // Black
            '&1' => '<span style="color: #0000AA;">',  // Dark Blue
            '&2' => '<span style="color: #00AA00;">',  // Dark Green
            '&3' => '<span style="color: #00AAAA;">',  // Dark Aqua
            '&4' => '<span style="color: #AA0000;">',  // Dark Red
            '&5' => '<span style="color: #AA00AA;">',  // Dark Purple
            '&6' => '<span style="color: #FFAA00;">',  // Gold
            '&7' => '<span style="color: #AAAAAA;">',  // Gray
            '&8' => '<span style="color: #555555;">',  // Dark Gray
            '&9' => '<span style="color: #5555FF;">',  // Blue
            '&a' => '<span style="color: #55FF55;">',  // Green
            '&b' => '<span style="color: #55FFFF;">',  // Aqua
            '&c' => '<span style="color: #FF5555;">',  // Red
            '&d' => '<span style="color: #FF55FF;">',  // Light Purple
            '&e' => '<span style="color: #FFFF55;">',  // Yellow
            '&f' => '<span style="color: #FFFFFF;">',  // White
            '&l' => '<span style="font-weight: bold;">',  // Bold
            '&m' => '<span style="text-decoration: line-through;">',  // Strikethrough
            '&n' => '<span style="text-decoration: underline;">',  // Underline
            '&o' => '<span style="font-style: italic;">',  // Italic
            '&r' => '</span>',  // Reset
        ];

        // Handle hex colors (&x&R&G&B&R&G&B format)
        $nickname = preg_replace_callback('/&x(&[0-9a-fA-F]){6}/', function($matches) {
            $hex = str_replace('&', '', $matches[0]);
            $hex = substr($hex, 1); // Remove the 'x'
            $color = '#' . $hex;
            return '<span style="color: ' . $color . ';">';
        }, $nickname);

        // Replace standard color codes
        foreach ($colorMap as $code => $html) {
            $nickname = str_replace($code, $html, $nickname);
        }

        // Count opening spans and add closing spans
        $openSpans = substr_count($nickname, '<span');
        $closeSpans = substr_count($nickname, '</span>');
        $nickname .= str_repeat('</span>', $openSpans - $closeSpans);

        return $nickname;
    }

    /**
     * Get the formatted nickname with colors.
     *
     * @return string|null
     */
    public function getFormattedNicknameAttribute()
    {
        return self::parseMinecraftColors($this->nickname);
    }
}