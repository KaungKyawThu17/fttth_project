<?php

namespace App\Filament\Widgets;

use App\Models\Ticket;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Collection;

class TicketStatusChart extends ChartWidget
{
    protected static ?int $sort = 20;

    protected ?string $heading = 'Ticket status overview';

    protected ?string $pollingInterval = '10s';

    protected string $color = 'info';

    protected int|string|array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    public static function canView(): bool
    {
        return auth()->user()?->canViewCompanyDashboard() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $counts = $this->ticketCountsBy('status');

        return [
            'datasets' => [
                [
                    'label' => 'Tickets',
                    'data' => collect(Ticket::statusOptions())
                        ->keys()
                        ->map(fn (string $status): int => (int) ($counts[$status] ?? 0))
                        ->all(),
                    'backgroundColor' => [
                        '#6b7280',
                        '#3b82f6',
                        '#f59e0b',
                        '#16a34a',
                        '#ef4444',
                    ],
                ],
            ],
            'labels' => array_values(Ticket::statusOptions()),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    /**
     * @return Collection<string, int>
     */
    protected function ticketCountsBy(string $column): Collection
    {
        return Ticket::query()
            ->select($column)
            ->selectRaw('count(*) as aggregate')
            ->groupBy($column)
            ->pluck('aggregate', $column);
    }
}
