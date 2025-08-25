<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LuckPermsUserPermission extends Model
{
    use HasFactory;

    protected $connection = 'luckperms';
    protected $table = 'luckperms_user_permissions';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'permission',
        'value',
        'server',
        'world',
        'expiry',
        'contexts',
    ];

    protected $casts = [
        'value' => 'boolean',
        'expiry' => 'integer',
    ];

    /**
     * Get user groups from permissions
     * 
     * @param string $uuid
     * @return array
     */
    public static function getUserGroups($uuid)
    {
        return self::where('uuid', $uuid)
            ->where('permission', 'like', 'group.%')
            ->where('value', true)
            ->pluck('permission')
            ->map(function ($permission) {
                return str_replace('group.', '', $permission);
            })
            ->toArray();
    }
}