<?php

namespace App\Filament\Resources\TechnicianJobs\Tables;

use App\Filament\Resources\TechnicianJobs\TechnicianJobResource;
use App\Models\TechnicianJob;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TechnicianJobsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->defaultSort('created_at', 'desc')
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
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.phone')
                    ->label('Customer phone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('technician.name')
                    ->label('Technician')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('job_type')
                    ->label('Job type')
                    ->formatStateUsing(fn (string $state, TechnicianJob $record): string => $record->jobTypeLabel())
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state, TechnicianJob $record): string => $record->statusLabel())
                    ->color(fn (TechnicianJob $record): string => $record->statusColor())
                    ->sortable(),
                TextColumn::make('scheduled_date')
                    ->date()
                    ->sortable()
                    ->placeholder('Not scheduled'),
                TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Not started'),
                TextColumn::make('completed_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Not completed'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(TechnicianJob::statusOptions())
                    ->multiple()
                    ->native(false),
                SelectFilter::make('technician')
                    ->relationship(
                        name: 'technician',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query): Builder => $query->orderBy('name'),
                    )
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->visible(fn (): bool => auth()->user()?->canViewAllTechnicianJobs() ?? false),
                SelectFilter::make('job_type')
                    ->label('Job type')
                    ->options(TechnicianJob::jobTypeOptions())
                    ->multiple()
                    ->native(false),
                Filter::make('scheduled_date')
                    ->schema([
                        DatePicker::make('scheduled_from')
                            ->label('Scheduled from')
                            ->native(false),
                        DatePicker::make('scheduled_until')
                            ->label('Scheduled until')
                            ->native(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['scheduled_from'] ?? null,
                            fn (Builder $query, string $date): Builder => $query->whereDate('scheduled_date', '>=', $date),
                        )
                        ->when(
                            $data['scheduled_until'] ?? null,
                            fn (Builder $query, string $date): Builder => $query->whereDate('scheduled_date', '<=', $date),
                        )),
                Filter::make('today_jobs')
                    ->label('Today jobs')
                    ->query(fn (Builder $query): Builder => $query->today()),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    ...TechnicianJobResource::workflowActions(),
                ]),
            ]);
    }
}
