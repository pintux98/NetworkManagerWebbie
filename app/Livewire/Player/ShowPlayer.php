<?php

namespace App\Livewire\Player;

use App\Livewire\Punishments\PunishmentForm;
use App\Models\Links;
use App\Models\Permissions\GroupMember;
use App\Models\Permissions\PermissionPlayer;
use App\Models\Permissions\PlayerPermission;
use App\Models\Player\Login;
use App\Models\Player\Player;
use App\Models\Player\Session;
use App\Models\Punishment;
use App\Models\PunishmentType;
use App\Models\UserDs;
use App\Models\ZutilsPlayer;
use App\Models\LuckPermsUserPermission;
use App\Models\LuckPermsGroupPermission;
use App\Models\UltraPlaytimeUserData;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ShowPlayer extends Component
{
    public Player $player;

    public PunishmentForm $punishment;

    public bool $isGlobal = false;

    public bool $isTemporary = false;

    public ?Links $discordLink = null;

    public ?UserDs $discordUser = null;

    public ?ZutilsPlayer $zutilsPlayer = null;

    protected $except = ['discordLink', 'discordUser', 'zutilsPlayer'];

    public ?array $luckPermsGroup = null;

    public ?array $ultraPlaytimeData = null;

    public ?array $playtimeRank = null;

    private function sanitizeArrayForJson(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }
        
        return array_filter($data, function($value) {
            // Only allow scalar values, null, or simple arrays
            return is_scalar($value) || is_null($value) || (is_array($value) && $this->isSimpleArray($value));
        });
    }
    
    private function isSimpleArray(array $array): bool
    {
        foreach ($array as $value) {
            if (!is_scalar($value) && !is_null($value)) {
                return false;
            }
        }
        return true;
    }

    public function mount(): void
    {
        $this->loadDiscordInformation();
        $this->loadZutilsInformation();
        $this->loadLuckPermsInformation();
        $this->loadUltraPlaytimeInformation();
    }

    public function updated($fields): void
    {
        $this->punishment->updated($fields);
    }

    private function loadDiscordInformation(): void
    {
        try {
            $this->discordLink = Links::findByMinecraftUuid($this->player->uuid);
            
            if ($this->discordLink && $this->discordLink->user_ds_id) {
                $this->discordUser = $this->discordLink->discordUser;
            }
        } catch (\Exception $e) {
            // Silently handle database connection errors
            // This allows the page to still work if the Discord links database is unavailable
            $this->discordLink = null;
            $this->discordUser = null;
        }
    }

    private function loadZutilsInformation(): void
    {
        try {
            $this->zutilsPlayer = ZutilsPlayer::where('playerUUID', $this->player->uuid)->first();
        } catch (\Exception $e) {
            // Silently handle database connection errors
            // This allows the page to still work if the zutils database is unavailable
            $this->zutilsPlayer = null;
        }
    }

    private function loadLuckPermsInformation(): void
    {
        try {
            // Get user groups from LuckPerms
            $userGroups = LuckPermsUserPermission::getUserGroups($this->player->uuid);
            
            if (!empty($userGroups)) {
                $highestWeightGroup = null;
                $highestWeight = -1;
                
                // Find the group with the highest weight
                foreach ($userGroups as $groupName) {
                    $groupInfo = LuckPermsGroupPermission::getGroupInfo($groupName);
                    
                    if ($groupInfo && $groupInfo['weight'] > $highestWeight) {
                        $highestWeight = $groupInfo['weight'];
                        $highestWeightGroup = $groupInfo;
                    }
                }
                
                $this->luckPermsGroup = $this->sanitizeArrayForJson($highestWeightGroup);
            }
        } catch (\Exception $e) {
            // Silently handle database connection errors
            // This allows the page to still work if the LuckPerms database is unavailable
            $this->luckPermsGroup = null;
        }
    }

    private function loadUltraPlaytimeInformation(): void
    {
        try {
            // Get playtime data from UltraPlaytime
            $this->ultraPlaytimeData = $this->sanitizeArrayForJson(UltraPlaytimeUserData::getPlaytimeByUuid($this->player->uuid));
            
            if ($this->ultraPlaytimeData && isset($this->ultraPlaytimeData['playtime'])) {
                // Calculate rank based on playtime
                $this->playtimeRank = $this->sanitizeArrayForJson($this->calculatePlaytimeRank($this->ultraPlaytimeData['playtime']));
            }
        } catch (\Exception $e) {
            // Silently handle database connection errors
            // This allows the page to still work if the UltraPlaytime database is unavailable
            $this->ultraPlaytimeData = null;
            $this->playtimeRank = null;
        }
    }

    private function calculatePlaytimeRank($playtimeMilliseconds): array
    {
        $playtimeSeconds = floor($playtimeMilliseconds / 1000);
        $ranks = config('playtime_ranks.ranks');
        $defaultRank = config('playtime_ranks.default_rank');
        
        $currentRank = $defaultRank;
        
        // Find the highest rank the player qualifies for
        foreach ($ranks as $rankId => $rank) {
            if ($playtimeSeconds >= $rank['requirement']) {
                $currentRank = $rank;
                $currentRank['id'] = $rankId;
            }
        }
        
        return $currentRank;
    }

    #[Computed]
    public function punishmentTypeCases(): array
    {
        return PunishmentType::cases();
    }

    #[Computed]
    public function hasDiscordLink(): bool
    {
        return $this->discordLink !== null && $this->discordUser !== null;
    }

    #[Computed]
    public function discordUsername(): ?string
    {
        return $this->discordUser?->getDisplayName();
    }

    #[Computed]
    public function discordTag(): ?string
    {
        return $this->discordUser?->getDiscordTag();
    }

    #[Computed]
    public function discordMentionTag(): ?string
    {
        return $this->discordUser?->getMentionTag();
    }

    #[Computed]
    public function hasZutilsNickname(): bool
    {
        return $this->zutilsPlayer !== null && !empty($this->zutilsPlayer->nickname);
    }

    #[Computed]
    public function zutilsNickname(): ?string
    {
        return $this->zutilsPlayer?->nickname;
    }

    #[Computed]
    public function formattedZutilsNickname(): ?string
    {
        return $this->zutilsPlayer?->formatted_nickname;
    }

    #[Computed]
    public function displayNickname(): string
    {
        // Use zutils nickname if available, otherwise fall back to player name
        if ($this->hasZutilsNickname) {
            return $this->formattedZutilsNickname;
        }
        
        return $this->player->name;
    }

    #[Computed]
    public function hasLuckPermsGroup(): bool
    {
        return $this->luckPermsGroup !== null;
    }

    #[Computed]
    public function luckPermsGroupName(): ?string
    {
        return $this->luckPermsGroup['name'] ?? null;
    }

    #[Computed]
    public function luckPermsGroupWeight(): ?int
    {
        return $this->luckPermsGroup['weight'] ?? null;
    }

    #[Computed]
    public function luckPermsGroupPrefix(): ?string
    {
        return $this->luckPermsGroup['prefix'] ?? null;
    }

    #[Computed]
    public function formattedLuckPermsGroupPrefix(): ?string
    {
        $prefix = $this->luckPermsGroupPrefix;
        
        if ($prefix) {
            // Parse Minecraft color codes in the prefix
            return ZutilsPlayer::parseMinecraftColors($prefix);
        }
        
        return null;
    }

    #[Computed]
    public function displayGroupName(): ?string
    {
        if ($this->hasLuckPermsGroup) {
            // Use formatted prefix if available, otherwise use group name
            return $this->formattedLuckPermsGroupPrefix ?: ucfirst($this->luckPermsGroupName);
        }
        
        return null;
    }

    #[Computed]
    public function hasUltraPlaytimeData(): bool
    {
        return $this->ultraPlaytimeData !== null && isset($this->ultraPlaytimeData['playtime']);
    }

    #[Computed]
    public function ultraPlaytimeMilliseconds(): ?int
    {
        return $this->ultraPlaytimeData['playtime'] ?? null;
    }

    #[Computed]
    public function formattedUltraPlaytime(): ?string
    {
        if ($this->hasUltraPlaytimeData) {
            return UltraPlaytimeUserData::formatPlaytime($this->ultraPlaytimeMilliseconds);
        }
        
        return null;
    }

    #[Computed]
    public function hasPlaytimeRank(): bool
    {
        return $this->playtimeRank !== null;
    }

    #[Computed]
    public function playtimeRankName(): ?string
    {
        return $this->playtimeRank['display_name'] ?? null;
    }

    #[Computed]
    public function formattedPlaytimeRank(): ?string
    {
        $rankName = $this->playtimeRankName;
        
        if ($rankName) {
            // Parse Minecraft color codes in the rank name
            return ZutilsPlayer::parseMinecraftColors($rankName);
        }
        
        return null;
    }

    public function punishPlayer(): void
    {
        $this->punishment->reset();
        $this->punishment->typeId = 1; // Set type to 1 by default.
        $this->punishment->active = true; // Set active to true by default.
        $this->punishment->isTemporary = false; // Set isTemporary to false by default.
        $this->punishment->time = Carbon::now()->format('Y-m-d\Th:i');
        $this->punishment->punisherUUID = Auth::check() ? Auth::user()->getUUID() : null;
    }

    public function punish(): void
    {
        $validatedData = $this->validate();

        $type = PunishmentType::from($validatedData['typeId']);
        $punisher = $validatedData['punisherUUID'];
        $time = Carbon::parse($validatedData['time'])->getPreciseTimestamp(3);
        $end = $type->isTemporary() ? Carbon::parse($validatedData['end'])->getPreciseTimestamp(3) : -1;
        $reason = $validatedData['reason'];
        $server = $validatedData['server'];
        $silent = $validatedData['silent'];
        $active = $validatedData['active'];

        $ip = $this->player->ip;
        if ($ip == null) {
            session()->flash('error', 'Could not find valid ip for use '.$this->player->uuid.'!');
            $this->closeModal('addPunishmentModal');

            return;
        }

        Punishment::create([
            'type' => $type,
            'uuid' => $this->player->uuid,
            'punisher' => $punisher,
            'time' => $time,
            'end' => $end,
            'reason' => $reason,
            'ip' => $ip,
            'server' => $server,
            'silent' => $silent,
            'active' => $active,
        ]);

        session()->flash('message', 'Punishment Created Successfully');
        $this->closeModal('addPunishmentModal');
        $this->refreshPlayerPunishmentsTable();
    }

    public function deletePlayer()
    {
        $this->player->delete();
        GroupMember::where('playeruuid', $this->player->uuid)->delete();
        PlayerPermission::where('playeruuid', $this->player->uuid)->delete();
        PermissionPlayer::where('uuid', $this->player->uuid)->delete();
        Punishment::where('uuid', $this->player->uuid)->delete();
        Session::where('uuid', $this->player->uuid)->delete();
        Login::where('uuid', $this->player->uuid)->delete();

        return \redirect()->route('players.index');
    }

    public function closeModal(?string $modalId = null): void
    {
        $this->punishment->reset();
        if ($modalId != null) {
            $this->dispatch('close-modal', $modalId);
        }
    }

    private function refreshPlayerPunishmentsTable(): void
    {
        $this->dispatch('pg:eventRefresh-player-punishments-table');
    }

    public function render(): View
    {
        return view('livewire.players.show-player')->with('player', $this->player);
    }
}
