<div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header text-center py-3">
                    <h5 class="mb-0 text-center">
                        <strong>{{ $this->serverDisplayName }} Server Details for {{ $player->username }}</strong>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Player Information -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header text-center py-3">
                                    <h6 class="mb-0 text-center">
                                        <strong>Player Information</strong>
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-borderless">
                                            <tbody>
                                            <tr>
                                                <th scope="row">@lang('player.player.information.nickname')</th>
                                                <td>
                                                    @if($this->hasZutilsNickname)
                                                        {!! $this->formattedZutilsNickname !!}
                                                    @else
                                                        {{$player->username}}
                                                    @endif
                                                </td>
                                            </tr>
                                            @if($this->hasUltraPlaytimeData)
                                            <tr>
                                                <th scope="row">UltraPlaytime</th>
                                                <td>{{$this->formattedUltraPlaytime}}</td>
                                            </tr>
                                            @endif
                                            @if($this->hasPlaytimeRank)
                                            <tr>
                                                <th scope="row">Rank</th>
                                                <td>{!! $this->formattedPlaytimeRank !!}</td>
                                            </tr>
                                            @endif
                                            <tr>
                                                <th scope="row">{{ $this->formattedVoteStreak }}</th>
                                                <td>
                                                    @if($this->hasVoteStreak)
                                                        <span class="text-primary fw-bold">{{ $this->voteStreakValue }}</span>
                                                    @else
                                                        <span class="text-muted">0</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @if($this->hasXconomyData)
                                                @if(!$this->isBalanceHidden)
                                                <tr>
                                                    <th scope="row">Balance</th>
                                                    <td class="text-success fw-bold">{{ $this->formattedPlayerBalance }}</td>
                                                </tr>
                                                @else
                                                <tr>
                                                    <th scope="row">Balance</th>
                                                    <td class="text-muted"><i class="fas fa-eye-slash"></i> Hidden</td>
                                                </tr>
                                                @endif
                                            @else
                                                <tr>
                                                    <th scope="row">Balance</th>
                                                    <td class="text-muted">No economy data available</td>
                                                </tr>
                                            @endif
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Information -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header text-center py-3">
                                    <h6 class="mb-0 text-center">
                                        <strong>Additional Information</strong>
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-borderless">
                                            <tbody>
                                            <tr>
                                                <th scope="row">Server</th>
                                                <td class="text-info fw-bold">{{ $this->serverDisplayName }}</td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>



                    <!-- Navigation -->
                    <div class="row mt-4">
                        <div class="col-12 text-center">
                            <a href="/players/{{ $player->uuid }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Player Overview
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>