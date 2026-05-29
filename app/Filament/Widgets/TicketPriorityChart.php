<?php

namespace App\Filament\Widgets;

use App\Models\Ticket;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Collection;

class TicketPriorityChart extends ChartWidget
{
    protected static ?int $sort = 21;

    protected ?string $heading = 'Ticket priority overview';

    protected ?string $pollingInterval = '10s';

    protected string $color = 'warning';

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
        $counts = $this->ticketCountsBy('priority');

        return [
            'datasets' => [
                [
                    'label' => 'Tickets',
                    'data' => collect(Ticket::priorityOptions())
                        ->keys()
                        ->map(fn (string $priority): int => (int) ($counts[$priority] ?? 0))
                        ->all(),
                    'backgroundColor' => [
                        '#6b7280',
                        '#3b82f6',
                        '#f59e0b',
                        '#ef4444',
                    ],
                ],
            ],
            'labels' => array_values(Ticket::priorityOptions()),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
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
