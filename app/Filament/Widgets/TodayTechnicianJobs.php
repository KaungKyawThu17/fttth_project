<?php

namespace App\Filament\Widgets;

use App\Models\TechnicianJob;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class TodayTechnicianJobs extends TableWidget
{
    protected static ?int $sort = 32;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->canViewCompanyDashboard() ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading("Today's technician jobs")
            ->poll('10s')
            ->query(fn (): Builder => TechnicianJob::query()
                ->with(['ticket', 'customer', 'technician'])
                ->today()
                ->orderBy('scheduled_date')
                ->orderBy('job_no'))
            ->columns([
                TextColumn::make('job_no')
                    ->label('Job no.')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('ticket.ticket_no')
                    ->label('Ticket no.')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label('Customer name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('technician.name')
                    ->label('Technician name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Job status')
                    ->badge()
                    ->formatStateUsing(fn (string $state, TechnicianJob $record): string => $record->statusLabel())
                    ->color(fn (TechnicianJob $record): string => $record->statusColor())
                    ->sortable(),
                TextColumn::make('scheduled_date')
                    ->label('Scheduled date')
                    ->date()
                    ->sortable(),
            ]);
    }
}
