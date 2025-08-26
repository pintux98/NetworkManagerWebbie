<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LuckPermsGroupPermission extends Model
{
    use HasFactory;

    protected $connection = 'survivaldb';
    protected $table = 'luckperms_group_permissions';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'name',
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
     * Get group weight
     * 
     * @param string $groupName
     * @return int|null
     */
    public static function getGroupWeight($groupName)
    {
        $weightPermission = self::where('name', $groupName)
            ->where('permission', 'like', 'weight.%')
            ->where('value', true)
            ->first();

        if ($weightPermission) {
            return (int) str_replace('weight.', '', $weightPermission->permission);
        }

        return null;
    }

    /**
     * Get group prefix with weight
     * 
     * @param string $groupName
     * @return string|null
     */
    public static function getGroupPrefix($groupName)
    {
        $weight = self::getGroupWeight($groupName);
        
        if ($weight !== null) {
            $prefixPermission = self::where('name', $groupName)
                ->where('permission', 'like', "prefix.{$weight}.%")
                ->where('value', true)
                ->first();

            if ($prefixPermission) {
                // Extract the prefix part after prefix.{weight}.
                $prefix = str_replace("prefix.{$weight}.", '', $prefixPermission->permission);
                return $prefix;
            }
        }

        return null;
    }

    /**
     * Get group information (weight and prefix)
     * 
     * @param string $groupName
     * @return array|null
     */
    public static function getGroupInfo($groupName)
    {
        $weight = self::getGroupWeight($groupName);
        $prefix = self::getGroupPrefix($groupName);

        if ($weight !== null) {
            return [
                'name' => $groupName,
                'weight' => $weight,
                'prefix' => $prefix,
            ];
        }

        return null;
    }
}