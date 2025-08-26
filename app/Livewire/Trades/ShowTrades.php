<?php

namespace App\Livewire\Trades;

use App\Models\Trade\TradeMetric;
use Illuminate\View\View;
use Livewire\Component;

class ShowTrades extends Component
{
    public function render(): View
    {
        return view('livewire.trades.show-trades');
    }
}