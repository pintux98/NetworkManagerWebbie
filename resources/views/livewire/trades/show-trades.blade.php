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
</div>