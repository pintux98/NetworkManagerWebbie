<?php

namespace App\Livewire\Trades;

use App\Models\Trade\TradeMetric;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class TradesTable extends PowerGridComponent
{
    public string $tableName = 'trades-table';

    public string $sortDirection = 'desc';

    public function setUp(): array
    {
        return [
            PowerGrid::header()
                ->showSearchInput()
                ->showToggleColumns(),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return TradeMetric::query()->with(['senderPlayer', 'receiverPlayer']);
    }

    public function relationSearch(): array
    {
        return [
            'senderPlayer' => [
                'name',
            ],
            'receiverPlayer' => [
                'name',
            ],
        ];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('sender_name', function ($item) {
                if ($item->senderPlayer) {
                    return '<a href="/players/' . $item->senderPlayer->uuid . '" class="text-decoration-none">' . e($item->senderPlayer->name) . '</a>';
                }
                return 'Unknown Player';
            })
            ->add('receiver_name', function ($item) {
                if ($item->receiverPlayer) {
                    return '<a href="/players/' . $item->receiverPlayer->uuid . '" class="text-decoration-none">' . e($item->receiverPlayer->name) . '</a>';
                }
                return 'Unknown Player';
            })
            ->add('category', fn ($item) => ucfirst($item->category))
            ->add('type', fn ($item) => ucfirst($item->type))
            ->add('specification', fn ($item) => $item->specification)
            ->add('quantity', fn ($item) => number_format($item->quantity))
            ->add('sender_server', fn ($item) => $item->sender_server)
            ->add('receiver_server', fn ($item) => $item->receiver_server)
            ->add('date', fn ($item) => $item->formatted_date)
            ->add('human_date', fn ($item) => $item->human_date);
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id')
                ->sortable()
                ->searchable(),

            Column::make('Sender', 'sender_name', 'sender')
                ->sortable()
                ->searchable(),

            Column::make('Receiver', 'receiver_name', 'receiver')
                ->sortable()
                ->searchable(),

            Column::make('Category', 'category')
                ->sortable()
                ->searchable(),

            Column::make('Type', 'type')
                ->sortable()
                ->searchable(),

            Column::make('Item', 'specification')
                ->sortable()
                ->searchable(),

            Column::make('Quantity', 'quantity')
                ->sortable()
                ->searchable(),

            Column::make('Sender Server', 'sender_server')
                ->sortable()
                ->searchable()
                ->hidden(true, false),

            Column::make('Receiver Server', 'receiver_server')
                ->sortable()
                ->searchable()
                ->hidden(true, false),

            Column::make('Date', 'date')
                ->sortable()
                ->searchable(),

            Column::make('Time Ago', 'human_date')
                ->sortable()
                ->searchable()
                ->hidden(true, false),

            Column::action('Action')
                ->headerAttribute('text-center'),
        ];
    }

    public function actions(TradeMetric $row): array
    {
        return [
            Button::add('info')
                ->attributes(['data-mdb-ripple-init' => '', 'data-mdb-modal-init' => '', 'data-mdb-target' => '#showTradeModal'])
                ->slot('<i class="material-icons text-info">info</i>')
                ->can(auth()->user()->can('view_trades'))
                ->id()
                ->class('bg-transparent border-0')
                ->dispatch('info', ['rowId' => $row->id]),
        ];
    }
}