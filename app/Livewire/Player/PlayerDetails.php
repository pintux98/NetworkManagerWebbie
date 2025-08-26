<?php

namespace App\Livewire\Player;

use App\Models\Player\Player;
use App\Models\Xconomy;
use App\Models\ZutilsPlayer;
use App\Models\UltraPlaytimeUserData;
use App\Models\Vote\VoteUser;
use App\Models\Vote\VoteStreak;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class PlayerDetails extends Component
{
    public Player $player;
    public string $server;
    public ?ZutilsPlayer $zutilsPlayer = null;
    public ?array $ultraPlaytimeData = null;
    public ?array $playtimeRank = null;
    public ?Xconomy $xconomyData = null;
    public ?VoteStreak $voteStreak = null;

    public function mount(): void
    {
        $this->loadZutilsInformation();
        $this->loadUltraPlaytimeInformation();
        $this->loadXconomyInformation();
        $this->loadVoteStreakInformation();
    }

    private function loadZutilsInformation(): void
    {
        try {
            $this->zutilsPlayer = ZutilsPlayer::where('playerUUID', $this->player->uuid)->first();
        } catch (\Exception $e) {
            // Silently handle database connection errors
            $this->zutilsPlayer = null;
        }
    }

    private function loadUltraPlaytimeInformation(): void
    {
        try {
            // Get playtime data from UltraPlaytime
            $this->ultraPlaytimeData = UltraPlaytimeUserData::getPlaytimeByUuid($this->player->uuid);
            
            if ($this->ultraPlaytimeData && isset($this->ultraPlaytimeData['playtime'])) {
                // Calculate rank based on playtime
                $this->playtimeRank = $this->calculatePlaytimeRank($this->ultraPlaytimeData['playtime']);
            }
        } catch (\Exception $e) {
            // Silently handle database connection errors
            $this->ultraPlaytimeData = null;
            $this->playtimeRank = null;
        }
    }

    private function loadXconomyInformation(): void
    {
        try {
            $this->xconomyData = Xconomy::where('UID', $this->player->uuid)->first();
        } catch (\Exception $e) {
            // Silently handle database connection errors
            $this->xconomyData = null;
        }
    }

    private function loadVoteStreakInformation(): void
    {
        try {
            // Find the user by UUID in the ervoto database
            $voteUser = VoteUser::findByUuid($this->player->uuid);
            
            if ($voteUser) {
                // Get the streak for "Minecraft Italia" platform
                $this->voteStreak = VoteStreak::getStreakForUserAndPlatform($voteUser->id, 'Minecraft Italia');
            }
        } catch (\Exception $e) {
            // Silently handle database connection errors
            $this->voteStreak = null;
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
    public function hasZutilsNickname(): bool
    {
        return $this->zutilsPlayer && !empty($this->zutilsPlayer->nickname);
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
        // Use zutils nickname if available, otherwise fall back to player username
        if ($this->hasZutilsNickname) {
            return $this->formattedZutilsNickname;
        }
        
        return $this->player->username;
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

    #[Computed]
    public function hasXconomyData(): bool
    {
        return $this->xconomyData !== null;
    }

    #[Computed]
    public function playerBalance(): float
    {
        return $this->xconomyData?->balance ?? 0.0;
    }

    #[Computed]
    public function formattedPlayerBalance(): string
    {
        return $this->xconomyData?->formatted_balance ?? '$0.00';
    }

    #[Computed]
    public function isBalanceHidden(): bool
    {
        return $this->xconomyData?->isHidden() ?? false;
    }

    #[Computed]
    public function serverDisplayName(): string
    {
        return match($this->server) {
            'survival' => 'Survival',
            'vanilla' => 'Vanilla',
            default => ucfirst($this->server)
        };
    }

    #[Computed]
    public function hasVoteStreak(): bool
    {
        return $this->voteStreak !== null;
    }

    #[Computed]
    public function voteStreakValue(): int
    {
        return $this->voteStreak?->streak_value ?? 0;
    }

    #[Computed]
    public function voteStreakPlatform(): string
    {
        return $this->voteStreak?->platform ?? 'Minecraft Italia';
    }

    #[Computed]
    public function formattedVoteStreak(): string
    {
        if ($this->hasVoteStreak) {
            return "Votes {$this->voteStreakPlatform} {$this->voteStreakValue}";
        }
        
        return 'Votes Minecraft Italia 0';
    }

    public function render(): View
    {
        return view('livewire.players.player-details');
    }
}