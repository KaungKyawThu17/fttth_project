<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\TechnicianJob;
use App\Models\Ticket;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class TicketStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 10;

    protected ?string $pollingInterval = '10s';

    public static function canView(): bool
    {
        return auth()->user()?->canViewCompanyDashboard() ?? false;
    }

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        return [
            Stat::make('Total tickets', Ticket::query()->count())
                ->color('gray'),
            Stat::make('Open tickets', $this->ticketStatusCount(Ticket::STATUS_OPEN))
                ->color('gray'),
            Stat::make('Assigned tickets', $this->ticketStatusCount(Ticket::STATUS_ASSIGNED))
                ->color('info'),
            Stat::make('In progress tickets', $this->ticketStatusCount(Ticket::STATUS_IN_PROGRESS))
                ->color('warning'),
            Stat::make('Closed tickets', $this->ticketStatusCount(Ticket::STATUS_CLOSED))
                ->color('success'),
            Stat::make('Urgent tickets', Ticket::query()->where('priority', Ticket::PRIORITY_URGENT)->count())
                ->color('danger'),
            Stat::make('Today technician jobs', TechnicianJob::query()->today()->count())
                ->color('info'),
            Stat::make('Completed jobs today', TechnicianJob::query()
                ->where('status', TechnicianJob::STATUS_COMPLETED)
                ->whereDate('completed_at', now()->toDateString())
                ->count())
                ->color('success'),
            Stat::make('Active customers', Customer::query()->where('status', Customer::STATUS_ACTIVE)->count())
                ->color('success'),
            Stat::make('Avg. close time', $this->averageCloseTime())
                ->description('Reported to closed')
                ->color('info'),
        ];
    }

    protected function ticketStatusCount(string $status): int
    {
        return Ticket::query()
            ->where('status', $status)
            ->count();
    }

    protected function averageCloseTime(): string
    {
        $durations = Ticket::query()
            ->whereNotNull('reported_at')
            ->whereNotNull('closed_at')
            ->get(['reported_at', 'closed_at'])
            ->map(fn (Ticket $ticket): int => $ticket->reported_at->diffInSeconds($ticket->closed_at))
            ->filter(fn (int $seconds): bool => $seconds >= 0);

        if ($durations->isEmpty()) {
            return 'N/A';
        }

        return Number::format($durations->avg() / 3600, precision: 1).' hrs';
    }
}
