@extends('layouts.app')

@section('content')
    <livewire:player.player-details :player="$player" :server="$server" />
@endsection