<div>
    @if (session('message'))
        <h5 class="alert alert-success">{{ session('message') }}</h5>
    @endif
    @if (session('error'))
        <h5 class="alert alert-danger">{{ session('error') }}</h5>
    @endif

    <x-card-table title="Trades">
        <livewire:trades.trades-table/>
    </x-card-table>

    <!-- Trade Details Modal -->
    @if($showModal)
        <div class="modal fade show" id="showTradeModal" tabindex="-1" style="display: block; background-color: rgba(0,0,0,0.5);" wire:ignore.self>
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Trade Details</h5>
                        <button type="button" class="btn-close" wire:click="closeModal()"></button>
                    </div>
                    <div class="modal-body">
                        @if($selectedTrade && $parsedTradeData)
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <strong>Date:</strong> {{ \Carbon\Carbon::parse($selectedTrade->trade_date)->format('M d, Y H:i:s') }}
                                </div>
                                <div class="col-md-4">
                                    <strong>Status:</strong> 
                                    @if($parsedTradeData['status'] === 'finished')
                                        <span class="badge bg-success">Finished</span>
                                    @elseif($parsedTradeData['status'] === 'cancelled')
                                        <span class="badge bg-danger">Cancelled</span>
                                    @else
                                        <span class="badge bg-warning">{{ ucfirst($parsedTradeData['status']) }}</span>
                                    @endif
                                </div>
                                <div class="col-md-4">
                                    <strong>Total Items:</strong> {{ count($parsedTradeData['player1_items']) + count($parsedTradeData['player2_items']) }}
                                </div>
                            </div>
                            
                            @if($parsedTradeData['status'] !== 'cancelled')
                            <div class="row">
                                <!-- Player A Card -->
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">
                                                @php $playerAUuid = $this->getPlayerUuid($selectedTrade->player_a); @endphp
                                                @if($playerAUuid)
                                                    <a href="{{ route('players.show', $playerAUuid) }}" class="text-decoration-none">
                                                        {{ $selectedTrade->player_a }}
                                                    </a>
                                                @else
                                                    {{ $selectedTrade->player_a }}
                                                @endif
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            @if($parsedTradeData['player1_coins'] > 0)
                                                <div class="mb-3">
                                                    <strong class="text-success"><i class="fas fa-arrow-down"></i> Received:</strong>
                                                    <div class="ms-3">
                                                        <span class="text-success">
                                                            {{ number_format($parsedTradeData['player1_coins']) }} <i class="fas fa-coins"></i> Coins
                                                        </span>
                                                    </div>
                                                </div>
                                            @endif
                                            
                                            @if(count($parsedTradeData['player1_items']) > 0)
                                                <div class="mb-3">
                                                    <strong class="text-success"><i class="fas fa-arrow-down"></i> Received:</strong>
                                                    <ul class="list-unstyled ms-3">
                                                        @foreach($parsedTradeData['player1_items'] as $item)
                                                            <li>• {!! $this->formatTradeItem($item) !!}</li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif
                                            
                                            @if($parsedTradeData['player2_coins'] > 0 || count($parsedTradeData['player2_items']) > 0)
                                                <div>
                                                    <strong class="text-warning"><i class="fas fa-arrow-up"></i> Offered:</strong>
                                                    <div class="ms-3">
                                                        @if($parsedTradeData['player2_coins'] > 0)
                                                            <div class="text-warning">{{ number_format($parsedTradeData['player2_coins']) }} <i class="fas fa-coins"></i> Coins</div>
                                                        @endif
                                                        @if(count($parsedTradeData['player2_items']) > 0)
                                                            @foreach($parsedTradeData['player2_items'] as $item)
                                                                <div>• {!! $this->formatTradeItem($item) !!}</div>
                                                            @endforeach
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif
                                            
                                            @if($parsedTradeData['player1_coins'] == 0 && count($parsedTradeData['player1_items']) == 0 && $parsedTradeData['player2_coins'] == 0 && count($parsedTradeData['player2_items']) == 0)
                                                <div class="text-muted">No items or coins traded</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Player B Card -->
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">
                                                @php $playerBUuid = $this->getPlayerUuid($selectedTrade->player_b); @endphp
                                                @if($playerBUuid)
                                                    <a href="{{ route('players.show', $playerBUuid) }}" class="text-decoration-none">
                                                        {{ $selectedTrade->player_b }}
                                                    </a>
                                                @else
                                                    {{ $selectedTrade->player_b }}
                                                @endif
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            @if($parsedTradeData['player2_coins'] > 0)
                                                <div class="mb-3">
                                                    <strong class="text-success"><i class="fas fa-arrow-down"></i> Received:</strong>
                                                    <div class="ms-3">
                                                        <span class="text-success">
                                                            {{ number_format($parsedTradeData['player2_coins']) }} <i class="fas fa-coins"></i> Coins
                                                        </span>
                                                    </div>
                                                </div>
                                            @endif
                                            
                                            @if(count($parsedTradeData['player2_items']) > 0)
                                                <div class="mb-3">
                                                    <strong class="text-success"><i class="fas fa-arrow-down"></i> Received:</strong>
                                                    <ul class="list-unstyled ms-3">
                                                        @foreach($parsedTradeData['player2_items'] as $item)
                                                            <li>• {!! $this->formatTradeItem($item) !!}</li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif
                                            
                                            @if($parsedTradeData['player1_coins'] > 0 || count($parsedTradeData['player1_items']) > 0)
                                                <div>
                                                    <strong class="text-warning"><i class="fas fa-arrow-up"></i> Offered:</strong>
                                                    <div class="ms-3">
                                                        @if($parsedTradeData['player1_coins'] > 0)
                                                            <div class="text-warning">{{ number_format($parsedTradeData['player1_coins']) }} <i class="fas fa-coins"></i> Coins</div>
                                                        @endif
                                                        @if(count($parsedTradeData['player1_items']) > 0)
                                                            @foreach($parsedTradeData['player1_items'] as $item)
                                                                <div>• {!! $this->formatTradeItem($item) !!}</div>
                                                            @endforeach
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif
                                            
                                            @if($parsedTradeData['player1_coins'] == 0 && count($parsedTradeData['player1_items']) == 0 && $parsedTradeData['player2_coins'] == 0 && count($parsedTradeData['player2_items']) == 0)
                                                <div class="text-muted">No items or coins traded</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @else
                                <div class="alert alert-warning text-center">
                                    <i class="fas fa-times-circle"></i> This trade was cancelled and no items were exchanged.
                                </div>
                            @endif
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeModal()">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>