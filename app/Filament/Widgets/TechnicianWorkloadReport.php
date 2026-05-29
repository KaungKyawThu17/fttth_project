<?php

namespace App\Filament\Widgets;

use App\Models\Technician;
use App\Models\TechnicianJob;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class TechnicianWorkloadReport extends TableWidget
{
    protected static ?int $sort = 31;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->canViewCompanyDashboard() ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Technician workload')
            ->poll('10s')
            ->query(fn (): Builder => Technician::query()
                ->withCount([
                    'technicianJobs as assigned_jobs' => fn (Builder $query): Builder => $query
                        ->where('status', TechnicianJob::STATUS_ASSIGNED),
                    'technicianJobs as in_progress_jobs' => fn (Builder $query): Builder => $query
                        ->where('status', TechnicianJob::STATUS_IN_PROGRESS),
                    'technicianJobs as completed_jobs_today' => fn (Builder $query): Builder => $query
                        ->where('status', TechnicianJob::STATUS_COMPLETED)
                        ->whereDate('completed_at', now()->toDateString()),
                    'technicianJobs as total_completed_jobs' => fn (Builder $query): Builder => $query
                        ->where('status', TechnicianJob::STATUS_COMPLETED),
                ])
                ->where('status', Technician::STATUS_ACTIVE)
                ->orderByDesc('assigned_jobs')
                ->orderByDesc('in_progress_jobs')
                ->orderBy('name'))
            ->columns([
                TextColumn::make('name')
                    ->label('Technician name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('assigned_jobs')
                    ->label('Assigned jobs')
                    ->sortable(),
                TextColumn::make('in_progress_jobs')
                    ->label('In progress jobs')
                    ->sortable(),
                TextColumn::make('completed_jobs_today')
                    ->label('Completed jobs today')
                    ->sortable(),
                TextColumn::make('total_completed_jobs')
                    ->label('Total completed jobs')
                    ->sortable(),
            ]);
    }
}
