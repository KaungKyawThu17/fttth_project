<?php

namespace App\Filament\Widgets;

use App\Models\TechnicianJob;
use App\Models\Ticket;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TechnicianSummaryStats extends StatsOverviewWidget
{
    protected static ?int $sort = 11;

    protected ?string $pollingInterval = '10s';

    public static function canView(): bool
    {
        return auth()->user()?->canViewTechnicianDashboard() ?? false;
    }

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $technicianId = auth()->user()?->technicianProfileId();

        if ($technicianId === null) {
            return [];
        }

        return [
            Stat::make('My assigned jobs', $this->jobStatusCount($technicianId, TechnicianJob::STATUS_ASSIGNED))
                ->color('info'),
            Stat::make('My in-progress jobs', $this->jobStatusCount($technicianId, TechnicianJob::STATUS_IN_PROGRESS))
                ->color('warning'),
            Stat::make('My completed jobs today', TechnicianJob::query()
                ->forTechnician($technicianId)
                ->where('status', TechnicianJob::STATUS_COMPLETED)
                ->whereDate('completed_at', now()->toDateString())
                ->count())
                ->color('success'),
            Stat::make('My urgent assigned tickets', Ticket::query()
                ->where('technician_id', $technicianId)
                ->where('priority', Ticket::PRIORITY_URGENT)
                ->whereIn('status', [Ticket::STATUS_ASSIGNED, Ticket::STATUS_IN_PROGRESS])
                ->count())
                ->color('danger'),
        ];
    }

    protected function jobStatusCount(int $technicianId, string $status): int
    {
        return TechnicianJob::query()
            ->forTechnician($technicianId)
            ->where('status', $status)
            ->count();
    }
}
