<?php

namespace App\Livewire\Trades;

use App\Models\Trade\TradeLog;
use App\Models\Player;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Attributes\On;

class ShowTrades extends Component
{
    public $showModal = false;
    public $selectedTrade = null;
    public $tradeDetails = [];
    public $parsedTradeData = [];

    #[On('info')]
    public function showTradeDetails($rowId)
    {
        // Get the grouped trade data using the same logic as the table
        $this->selectedTrade = TradeLog::groupedByTransaction()
            ->where('min_id', '<=', $rowId)
            ->where('max_id', '>=', $rowId)
            ->first();
        
        if ($this->selectedTrade) {
            // Parse the trade messages to get detailed information
            $this->parsedTradeData = TradeLog::parseTradeMessages(
                $this->selectedTrade->messages,
                $this->selectedTrade->player_a,
                $this->selectedTrade->player_b
            );
            $this->showModal = true;
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->selectedTrade = null;
        $this->tradeDetails = [];
        $this->parsedTradeData = [];
    }

    /**
     * Get player UUID for linking
     */
    public function getPlayerUuid($playerName)
    {
        $player = Player::where('name', $playerName)->first();
        return $player ? $player->uuid : null;
    }

    /**
     * Format trade item for display
     */
    public function formatTradeItem($item)
    {
        // Handle array structure from parseTradeMessages
        if (is_array($item)) {
            $quantity = $item['quantity'] ?? 1;
            $itemName = $item['name'] ?? 'Unknown Item';
            $itemType = $item['type'] ?? 'Unknown';
            
            // Clean item name and convert Minecraft colors
            $cleanName = $this->convertMinecraftColors($itemName);
            
            return '<span>' . $cleanName . '</span> <span class="text-muted">(' . $quantity . 'x)</span> <span class="text-info">[' . $itemType . ']</span>';
        }
        
        // Fallback for string format (legacy)
        if (is_string($item)) {
            // Handle coins
            if (strpos($item, 'Coins:') !== false) {
                preg_match('/Coins: (-?\d+)/', $item, $matches);
                if (isset($matches[1])) {
                    $amount = (int)$matches[1];
                    $color = $amount >= 0 ? 'text-success' : 'text-danger';
                    return '<span class="' . $color . '">'. number_format(abs($amount)) . ' <i class="fas fa-coins"></i></span>';
                }
            }
            
            // Handle items
            if (preg_match('/(\d+)x (.+?) \((.+?)\)/', $item, $matches)) {
                $quantity = $matches[1];
                $itemName = $matches[2];
                $itemType = $matches[3];
                
                // Clean item name and convert Minecraft colors
                $cleanName = $this->convertMinecraftColors($itemName);
                
                return '<span>' . $cleanName . '</span> <span class="text-muted">(' . $quantity . 'x)</span> <span class="text-info">[' . $itemType . ']</span>';
            }
        }
        
        return 'Unknown Item';
    }

    /**
     * Convert Minecraft color codes to HTML
     */
    private function convertMinecraftColors($text)
    {
        // Minecraft color code mappings
        $colorMap = [
            '§0' => '<span style="color: #000000">', // Black
            '§1' => '<span style="color: #0000AA">', // Dark Blue
            '§2' => '<span style="color: #00AA00">', // Dark Green
            '§3' => '<span style="color: #00AAAA">', // Dark Aqua
            '§4' => '<span style="color: #AA0000">', // Dark Red
            '§5' => '<span style="color: #AA00AA">', // Dark Purple
            '§6' => '<span style="color: #FFAA00">', // Gold
            '§7' => '<span style="color: #AAAAAA">', // Gray
            '§8' => '<span style="color: #555555">', // Dark Gray
            '§9' => '<span style="color: #5555FF">', // Blue
            '§a' => '<span style="color: #55FF55">', // Green
            '§b' => '<span style="color: #55FFFF">', // Aqua
            '§c' => '<span style="color: #FF5555">', // Red
            '§d' => '<span style="color: #FF55FF">', // Light Purple
            '§e' => '<span style="color: #FFFF55">', // Yellow
            '§f' => '<span style="color: #FFFFFF">', // White
            '§l' => '<strong>', // Bold
            '§r' => '</span></strong>', // Reset
        ];

        // Handle hex color codes (§x§R§G§B§format)
        $text = preg_replace_callback('/§x(§[0-9a-fA-F]){6}/', function($matches) {
            $hex = str_replace('§', '', substr($matches[0], 2));
            return '<span style="color: #' . $hex . '">';
        }, $text);

        // Replace standard color codes
        foreach ($colorMap as $code => $html) {
            $text = str_replace($code, $html, $text);
        }

        // Close any remaining open spans
        $openSpans = substr_count($text, '<span') - substr_count($text, '</span>');
        for ($i = 0; $i < $openSpans; $i++) {
            $text .= '</span>';
        }

        return $text;
    }

    public function render(): View
    {
        return view('livewire.trades.show-trades');
    }
}