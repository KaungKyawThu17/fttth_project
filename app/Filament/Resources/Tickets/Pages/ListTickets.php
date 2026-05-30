<?php

namespace App\Filament\Resources\Tickets\Pages;

use App\Filament\Resources\Tickets\TicketResource;
use App\Models\Ticket;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'open' => Tab::make('Open')
                ->query(fn (Builder $query) => $query->where('status', Ticket::STATUS_OPEN)),
            'assigned' => Tab::make('Assigned')
                ->query(fn (Builder $query) => $query->where('status', Ticket::STATUS_ASSIGNED)),
            'in_progress' => Tab::make('In Progress')
                ->query(fn (Builder $query) => $query->where('status', Ticket::STATUS_IN_PROGRESS)),
            'closed' => Tab::make('Closed')
                ->query(fn (Builder $query) => $query->where('status', Ticket::STATUS_CLOSED)),
            'cancelled' => Tab::make('Cancelled')
                ->query(fn (Builder $query) => $query->where('status', Ticket::STATUS_CANCELLED)),
        ];
    }
}
