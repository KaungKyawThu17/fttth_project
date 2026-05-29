<?php

namespace App\Filament\Widgets;

use App\Models\Ticket;
use App\Models\TicketCategory;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class ComplaintCategoryReport extends TableWidget
{
    protected static ?int $sort = 30;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->canViewCompanyDashboard() ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Most common complaint categories')
            ->poll('10s')
            ->query(fn (): Builder => TicketCategory::query()
                ->withCount([
                    'tickets as total_tickets',
                    'tickets as open_tickets' => fn (Builder $query): Builder => $query
                        ->where('status', Ticket::STATUS_OPEN),
                    'tickets as closed_tickets' => fn (Builder $query): Builder => $query
                        ->where('status', Ticket::STATUS_CLOSED),
                ])
                ->orderByDesc('total_tickets')
                ->orderBy('name'))
            ->columns([
                TextColumn::make('name')
                    ->label('Category name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total_tickets')
                    ->label('Total tickets')
                    ->sortable(),
                TextColumn::make('open_tickets')
                    ->label('Open tickets')
                    ->sortable(),
                TextColumn::make('closed_tickets')
                    ->label('Closed tickets')
                    ->sortable(),
            ]);
    }
}
