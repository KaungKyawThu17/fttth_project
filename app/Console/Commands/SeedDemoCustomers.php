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
    public function handle(): int
    {
        $count = (int) $this->option('count');
        $category = TicketCategory::query()->first();

        if (! $category) {
            $this->error('No ticket categories found. Run migrations and seeders first.');

            return self::FAILURE;
        }

        $existingCount = Customer::query()->count();
        $start = $existingCount + 1;

        for ($i = $start; $i < $start + $count; $i++) {
            $customer = Customer::query()->create([
                'customer_code' => sprintf('DEMO%03d', $i),
                'name' => "Demo Customer {$i}",
                'phone' => '09' . str_pad((string) random_int(100000000, 999999999), 9, '0', STR_PAD_LEFT),
                'address' => fake()->address(),
                'township' => fake()->city(),
                'city' => 'Yangon',
                'status' => Customer::STATUS_ACTIVE,
            ]);

            $ticketNo = sprintf('TKT-DEMO-%s-%03d', now()->format('YmdHis'), $i);

            $ticket = Ticket::query()->create([
                'customer_id' => $customer->getKey(),
                'ticket_category_id' => $category->getKey(),
                'ticket_no' => $ticketNo,
                'subject' => fake()->randomElement(['Slow internet speed', 'Connection dropping', 'No internet connection', 'Billing issue', 'Router problem']),
                'description' => fake()->paragraph(),
                'priority' => fake()->randomElement([Ticket::PRIORITY_LOW, Ticket::PRIORITY_MEDIUM, Ticket::PRIORITY_HIGH]),
                'status' => fake()->randomElement([Ticket::STATUS_OPEN, Ticket::STATUS_CLOSED]),
                'reported_at' => now()->subDays(random_int(1, 30)),
                'resolved_at' => now()->subDays(random_int(0, 5)),
                'closed_at' => now()->subDays(random_int(0, 5)),
            ]);

            TicketComment::query()->create([
                'ticket_id' => $ticket->getKey(),
                'comment' => fake()->randomElement([
                    'Customer is frustrated with the service.',
                    'Please send a technician to check.',
                    'Issue resolved after restarting the modem.',
                    'Customer reported intermittent connection.',
                    'Service quality has been declining.',
                    'Customer is considering switching providers.',
                    'Technician visited and fixed the issue.',
                ]),
            ]);

            $this->line("Created customer: {$customer->customer_code} - {$customer->name}");
        }

        $this->info("Created {$count} demo customers with tickets and comments.");

        return self::SUCCESS;
    }
}
