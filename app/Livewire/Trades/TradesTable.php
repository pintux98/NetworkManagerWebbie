<?php

namespace App\Livewire\Trades;

use App\Models\Trade\TradeLog;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class TradesTable extends PowerGridComponent
{
    public string $tableName = 'TradesTable';
    public string $sortField = 'trade_date';
    public string $sortDirection = 'desc';

    public function setUp(): array
    {
        return [
            PowerGrid::header()
                ->showSearchInput(),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        $query = TradeLog::query();
        
        if ($this->search) {
            $searchTerm = $this->search;
            
            // First, get all grouped transactions
            $groupedQuery = $query->groupedByTransaction();
            
            // Then filter the grouped results based on search criteria
             $groupedQuery->havingRaw('(
                 player_a LIKE ? OR 
                 player_b LIKE ? OR
                 messages LIKE ?
             )', [
                 '%' . $searchTerm . '%',
                 '%' . $searchTerm . '%', 
                 '%' . $searchTerm . '%'
             ]);
            
            return $groupedQuery;
        }
        
        return $query->groupedByTransaction();
    }



    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('player_a', function ($row) {
                return '<a href="/players/' . $this->getPlayerUuid($row->player_a) . '" class="text-decoration-none">' . $row->player_a . '</a>';
            })
            ->add('player_b', function ($row) {
                return '<a href="/players/' . $this->getPlayerUuid($row->player_b) . '" class="text-decoration-none">' . $row->player_b . '</a>';
            })
            ->add('trade_status', function ($row) {
                $parsed = TradeLog::parseTradeMessages($row->messages, $row->player_a, $row->player_b);
                $status = $parsed['status'];
                $color = match($status) {
                    'finished' => 'success',
                    'cancelled' => 'danger',
                    'started' => 'warning',
                    default => 'secondary'
                };
                return '<span class="badge bg-' . $color . '">' . ucfirst($status) . '</span>';
            })
            ->add('items_summary', function ($row) {
                $parsed = TradeLog::parseTradeMessages($row->messages, $row->player_a, $row->player_b);
                return $this->formatTradeItems($parsed);
            })
            ->add('item_count', function ($row) {
                $parsed = TradeLog::parseTradeMessages($row->messages, $row->player_a, $row->player_b);
                return count($parsed['player1_items']) + count($parsed['player2_items']);
            })
            ->add('coins_total', function ($row) {
                $parsed = TradeLog::parseTradeMessages($row->messages, $row->player_a, $row->player_b);
                $total = $parsed['player1_coins'] + $parsed['player2_coins'];
                return $total > 0 ? number_format($total) : '0';
            })
            ->add('trade_date')
            ->add('human_date', function ($row) {
                return $row->trade_date ? \Carbon\Carbon::parse($row->trade_date)->diffForHumans() : '';
            });
    }

    public function columns(): array
    {
        return [
            Column::action('Action')
                ->headerAttribute('text-center'),

            Column::make('Player A', 'player_a')
                ->sortable(),

            Column::make('Player B', 'player_b')
                ->sortable(),

            Column::make('Status', 'trade_status')
                ->sortable(false),

            Column::make('Items', 'items_summary')
                ->sortable(false),

            Column::make('Item Count', 'item_count')
                ->sortable(false),

            Column::make('Coins', 'coins_total')
                ->sortable(false),

            Column::make('Date', 'trade_date')
                ->sortable(),

            Column::make('Time Ago', 'human_date')
                ->sortable(false)
                ->hidden(true, false),
        ];
    }

    public function actions(object $row): array
    {
        // Show action button for all transactions
        return [
            Button::add('info')
                ->slot('<i class="fas fa-info-circle"></i>')
                ->class('btn btn-sm btn-outline-info')
                ->tooltip('View Trade Details')
                ->id()
                ->dispatch('info', ['rowId' => $row->min_id])
        ];
    }

    private function formatTradeItems($parsed)
    {
        $itemCounts = [];
        
        // Merge items from both players and count duplicates
        $allItems = array_merge($parsed['player1_items'], $parsed['player2_items']);
        
        foreach ($allItems as $item) {
            $cleanName = $this->cleanItemName($item['name']);
            if (isset($itemCounts[$cleanName])) {
                $itemCounts[$cleanName] += $item['quantity'];
            } else {
                $itemCounts[$cleanName] = $item['quantity'];
            }
        }
        
        // Format items with merged quantities
        $items = [];
        foreach ($itemCounts as $itemName => $quantity) {
            $items[] = $quantity . 'x ' . $itemName;
        }
        
        // Add coins if any
        $totalCoins = $parsed['player1_coins'] + $parsed['player2_coins'];
        if ($totalCoins > 0) {
            $items[] = number_format($totalCoins) . ' Coins';
        }
        
        if (empty($items)) {
            return 'No items';
        }
        
        return implode('<br>', $items);
    }
    
    private function getPlayerUuid($playerName)
    {
        // Try to get UUID from nm_players table
        $player = \App\Models\Player\Player::where('username', $playerName)->first();
        return $player ? $player->uuid : $playerName;
    }

    /**
     * Clean item name by removing Minecraft color codes
     */
    private function cleanItemName($itemName)
    {
        // Remove Minecraft color codes (§ followed by any character)
        $cleanName = preg_replace('/§./', '', $itemName);
        
        // Remove hex color codes (§x§R§G§B§format)
        $cleanName = preg_replace('/§x(§[0-9a-fA-F]){6}/', '', $cleanName);
        
        // Trim any extra whitespace
        return trim($cleanName);
    }



    /**
     * Format trade item specification for display
     */
    private function formatTradeItem($specification, $quantity, $category, $type)
    {
        // Handle economy/vault entries
        if (stripos($specification, 'Economy Vault Icon') !== false || stripos($category, 'economy') !== false) {
            return '<span class="text-warning">' . number_format($quantity) . ' <i class="fas fa-coins"></i></span>';
        }

        // Try to decode JSON specification
        $decoded = json_decode($specification, true);
        
        if (is_array($decoded) && isset($decoded['name'])) {
            // Extract the name and preserve Minecraft color codes
            $name = $decoded['name'];
            // Convert Minecraft color codes to HTML
            $name = $this->convertMinecraftColors($name);
            return '<span>' . $name . '</span> <span class="text-muted">(' . $quantity . 'x)</span>';
        }
        
        // Fallback for non-JSON or items without name
        $itemType = ucwords(str_replace(['_', ':'], [' ', ' '], $type));
        return '<span>' . $itemType . '</span> <span class="text-muted">(' . $quantity . 'x)</span>';
    }



    /**
     * Format trade item specification for display with type information
     */
    private function formatTradeItemWithType($specification, $quantity, $category, $type)
    {
        // Handle economy/vault entries
        if (stripos($specification, 'Economy Vault Icon') !== false || stripos($category, 'economy') !== false) {
            return '<span class="text-warning">' . number_format($quantity) . ' <i class="fas fa-coins"></i></span>';
        }

        // Try to decode JSON specification
        $decoded = json_decode($specification, true);
        
        if (is_array($decoded) && isset($decoded['name'])) {
            // Extract the name and preserve Minecraft color codes
            $name = $decoded['name'];
            // Convert Minecraft color codes to HTML
            $name = $this->convertMinecraftColors($name);
            // Include the item type as requested
            $itemType = !empty($type) ? ' <span class="text-info">' . strtoupper($type) . '</span>' : '';
            return '<span>' . $name . '</span>' . $itemType . ' <span class="text-muted">(' . $quantity . 'x)</span>';
        }
        
        // Fallback for non-JSON or items without name - show the type prominently
        $itemType = !empty($type) ? strtoupper($type) : 'UNKNOWN_ITEM';
        return '<span class="text-info">' . $itemType . '</span> <span class="text-muted">(' . $quantity . 'x)</span>';
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
}