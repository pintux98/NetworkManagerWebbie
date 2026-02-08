<?php

namespace App\Models\Player;

use App\Helpers\CountryUtils;
use App\Helpers\TimeUtils;
use App\Models\ProtocolVersion;
use App\Models\Tag;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Nwidart\Modules\Facades\Module;
use Ramsey\Uuid\Uuid;
use App\Models\Player\PlayerPing;
use App\Models\Player\Session;
use App\Models\Player\Login;

class Player extends Model
{
    use HasUuids;

    /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'manager';

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
    protected $primaryKey = 'uuid';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uuid',
        'username',
        'nickname',
        'language',
        'tagid',
        'ip',
        'country',
        'version',
        'firstlogin',
        'lastlogin',
        'lastlogout',
        'online',
        'playtime',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'language' => 'integer',
        'tagid' => 'integer',
        'firstlogin' => 'integer',
        'lastlogin' => 'integer',
        'lastlogout' => 'integer',

        'online' => 'boolean',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [

    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    public function tag(): HasOne
    {
        return $this->hasOne(Tag::class, 'id', 'tagid');
    }

    public function ignoredPlayers(): HasMany
    {
        return $this->hasMany(IgnoredPlayer::class, 'uuid', 'uuid');
    }

    protected function playtime(): Attribute
    {
        return Attribute::make(get: fn (int $value) => TimeUtils::millisToReadableFormat($value));
    }

    public function tagNames(): ?string
    {
        if (Module::isEnabled('UltimateTags')) {
            $user = \Addons\UltimateTags\App\Models\User::find(Uuid::fromString($this->uuid)->getBytes());
            if ($user) {
                return $user->tags->map(fn ($tag) => $tag->name)->implode(', ');
            }
        }

        return $this->tag?->name;
    }

    public function fullCountry(): string
    {
        return CountryUtils::countryCodeToCountry($this->country);
    }

    public function version(): Attribute
    {
        return Attribute::make(get: fn (int $value) => ProtocolVersion::tryFrom($value) ?? ProtocolVersion::SNAPSHOT);
    }

    public static function getName($uuid)
    {
        if ($uuid == null) {
            return null;
        }
        if ($uuid == 'f78a4d8d-d51b-4b39-98a3-230f2de0c670') {
            return 'CONSOLE';
        }
        $player = Player::select('username')
            ->where('uuid', $uuid)
            ->first();
        if ($player == null) {
            return $player;
        }

        return $player->username;
    }

    public static function getUUID($username): ?string
    {
        if ($username == null) {
            return null;
        }
        if ($username == 'CONSOLE') {
            return 'f78a4d8d-d51b-4b39-98a3-230f2de0c670';
        }
        $player = Player::select('uuid')
            ->where('username', $username)
            ->first();
        if ($player == null) {
            return $player;
        }

        return $player->uuid;
    }

    public static function getIP($uuid): ?string
    {
        $player = Player::select('ip')
            ->where('uuid', $uuid)
            ->first();
        if ($player == null) {
            return null;
        }

        return $player->ip;
    }

    public static function getMostPlayedVersions()
    {
        $result = DB::table('logins')
            ->selectRaw('DISTINCT(version) as version, count(*) AS count, COUNT(*) * 100.0 / sum(COUNT(*)) over() as percentage')
            ->orderBy('count', 'desc')
            ->groupBy('version')
            ->get();

        return $result->map(function ($item) {
            $protocolVersion = ProtocolVersion::tryFrom($item->version);
            $version = $protocolVersion == null ? 'snapshot' : $protocolVersion->name();

            return [
                'version' => $version,
                'players' => $item->count,
                'percentage' => number_format($item->percentage, 2, '.', ' '),
            ];
        });
    }

    public static function getMostUsedVirtualHosts()
    {
        $result = Login::selectRaw('vhost, COUNT(DISTINCT uuid, vhost) as count, COUNT(DISTINCT uuid, vhost) * 100.0 / sum(COUNT(DISTINCT uuid, vhost)) over() as percentage')
            ->groupBy('vhost')
            ->orderBy('count', 'desc')
            ->get();

        return $result->map(function ($item) {
            return [
                'vhost' => ($item->vhost == null ? 'Unknown' : $item->vhost),
                'players' => $item->count,
                'percentage' => number_format($item->percentage, 2, '.', ' '),
            ];
        });
    }

    public function getAveragePlaytime(): string
    {
        // Use a single query to get the average playtime
        $averagePlaytime = Session::where('uuid', $this->uuid)->avg('time');
        
        if ($averagePlaytime === null || $averagePlaytime == 0) {
            return '0 seconds';
        }
        
        try {
            return CarbonInterval::millisecond($averagePlaytime)->cascade()->forHumans();
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function getAverageDailyLogin(): string
    {
        // Use a single query to get the average start time
        $averageStart = Session::where('uuid', $this->uuid)->avg('start');
        
        if ($averageStart === null) {
            return '00:00:00';
        }

        return date('H:i:s', $averageStart / 1000);
    }

    public function getSessions(): \Illuminate\Support\Collection
    {
        return Session::where('uuid', $this->uuid)
            ->get();
    }

    /**
     * Finds players with the same ip
     * but to make it more accurate to only search for
     * those who have been online for the last 30 days
     *
     * @return Collection with player objects of alt accounts
     */
    public function getAltAccounts(): Collection
    {
        $last30DaysMs = Carbon::now()->subDays(30)->getTimestampMs();
        
        // Use a single query with join to get alt accounts directly
        return Player::whereIn('uuid', function($query) use ($last30DaysMs) {
            $query->select('uuid')
                ->from('logins')
                ->where('uuid', '!=', $this->uuid)
                ->where('ip', $this->ip)
                ->where('time', '>', $last30DaysMs)
                ->distinct();
        })->get();
    }

    public function getMostUsedVersions()
    {
        $result = DB::table('logins')
            ->selectRaw('DISTINCT(version) as version, count(*) AS count, COUNT(*) * 100.0 / sum(COUNT(*)) over() as percentage')
            ->where('version', '<>', 0)
            ->where('uuid', $this->uuid)
            ->orderBy('count', 'DESC')
            ->groupBy('version')
            ->get();

        // If no data found, return empty array
        if ($result->isEmpty()) {
            return [];
        }

        $data = [];
        foreach ($result as $item) {
            $protocolVersion = ProtocolVersion::tryFrom($item->version);
            $version = $protocolVersion == null ? 'snapshot' : $protocolVersion->name();

            // Ensure percentage is a valid number
            $percentage = (float) $item->percentage;
            if (!is_finite($percentage) || $percentage < 0) {
                $percentage = 0;
            }

            $data[] = ['name' => $version, 'y' => $percentage];
        }

        return $data;
    }

    public function getPingDataAsString(): string
    {
        // Use a single query with aggregation functions
        $pingData = PlayerPing::selectRaw('MIN(min_ping) as min_ping, MAX(max_ping) as max_ping, AVG(avg_ping) as avg_ping')
            ->where('uuid', $this->uuid)
            ->first();
            
        if (!$pingData) {
            return "Avg 0ms, Best 0ms, Worst 0ms";
        }
        
        $min = $pingData->min_ping ?? 0;
        $max = $pingData->max_ping ?? 0;
        $avg = round($pingData->avg_ping ?? 0, 2);
        
        return "Avg {$avg}ms, Best {$min}ms, Worst {$max}ms";
    }

    public function getTimestampFormatted($timestamp): string
    {
        return date('d-m-Y H:i:s', $timestamp / 1000);
    }
}
