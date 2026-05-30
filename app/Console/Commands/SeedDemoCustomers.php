<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketComment;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('demo:seed-customers {--count=15 : Number of demo customers to create}')]
#[Description('Create demo customers with tickets and comments for testing')]
class SeedDemoCustomers extends Command
{
    protected const SUBJECTS = [
        'Slow internet speed',
        'Connection dropping',
        'No internet connection',
        'Billing issue',
        'Router problem',
    ];

    protected const COMMENTS = [
        'Customer is frustrated with the service.',
        'Please send a technician to check.',
        'Issue resolved after restarting the modem.',
        'Customer reported intermittent connection.',
        'Service quality has been declining.',
        'Customer is considering switching providers.',
        'Technician visited and fixed the issue.',
    ];

    public function handle(): int
    {
        $count = (int) $this->option('count');

        $category = TicketCategory::query()->first() ?? TicketCategory::query()->create([
            'name' => 'General',
            'description' => 'General support category',
            'is_active' => true,
        ]);

        $lastCode = Customer::query()
            ->where('customer_code', 'like', 'DEMO%')
            ->orderByDesc('customer_code')
            ->value('customer_code');

        $start = $lastCode ? (int) str_replace('DEMO', '', $lastCode) + 1 : 1;

        for ($i = $start; $i < $start + $count; $i++) {
            $customer = Customer::query()->create([
                'customer_code' => sprintf('DEMO%03d', $i),
                'name' => "Demo Customer {$i}",
                'phone' => '09'.str_pad((string) random_int(100000000, 999999999), 9, '0', STR_PAD_LEFT),
                'address' => "{$i} Demo Street, Yangon",
                'township' => 'Hlaing',
                'city' => 'Yangon',
                'status' => Customer::STATUS_ACTIVE,
            ]);

            $ticketNo = sprintf('TKT-DEMO-%s-%03d', now()->format('YmdHis'), $i);

            $reportedAt = now()->subDays(random_int(1, 30));
            $resolvedAt = (clone $reportedAt)->addHours(random_int(1, 48));

            $ticket = Ticket::query()->create([
                'customer_id' => $customer->getKey(),
                'ticket_category_id' => $category->getKey(),
                'ticket_no' => $ticketNo,
                'subject' => self::SUBJECTS[array_rand(self::SUBJECTS)],
                'description' => "Demo ticket for customer {$customer->name}.",
                'priority' => [Ticket::PRIORITY_LOW, Ticket::PRIORITY_MEDIUM, Ticket::PRIORITY_HIGH][random_int(0, 2)],
                'status' => [Ticket::STATUS_OPEN, Ticket::STATUS_CLOSED][random_int(0, 1)],
                'reported_at' => $reportedAt,
                'resolved_at' => $resolvedAt,
                'closed_at' => (clone $resolvedAt)->addHours(random_int(1, 6)),
            ]);

            TicketComment::query()->create([
                'ticket_id' => $ticket->getKey(),
                'comment' => self::COMMENTS[array_rand(self::COMMENTS)],
            ]);

            $this->line("Created customer: {$customer->customer_code} - {$customer->name}");
        }

        $this->info("Created {$count} demo customers with tickets and comments.");

        return self::SUCCESS;
    }
}
