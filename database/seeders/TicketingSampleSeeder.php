<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Technician;
use App\Models\TechnicianJob;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TicketingSampleSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@email.com'],
            [
                'name' => 'admin',
                'password' => 'password',
                'role' => User::ROLE_ADMIN,
                'email_verified_at' => now(),
            ],
        );

        $categories = collect([
            ['name' => 'Connection Drop', 'description' => 'Intermittent or unavailable FTTH connection.'],
            ['name' => 'Slow Speed', 'description' => 'Customer speed is below package expectation.'],
            ['name' => 'LOS Signal', 'description' => 'Optical signal or line of sight issue.'],
            ['name' => 'Router Issue', 'description' => 'Router configuration or hardware issue.'],
        ])->mapWithKeys(fn (array $category): array => [
            $category['name'] => TicketCategory::query()->updateOrCreate(
                ['name' => $category['name']],
                [
                    'description' => $category['description'],
                    'is_active' => true,
                ],
            ),
        ]);

        $technicians = collect([
            ['name' => 'Aung Technician', 'email' => 'aung.tech@example.com', 'phone' => '09111111101'],
            ['name' => 'Myo Technician', 'email' => 'myo.tech@example.com', 'phone' => '09111111102'],
            ['name' => 'Htet Technician', 'email' => 'htet.tech@example.com', 'phone' => '09111111103'],
            ['name' => 'Nay Technician', 'email' => 'nay.tech@example.com', 'phone' => '09111111104'],
        ])->mapWithKeys(function (array $technician): array {
            $user = User::query()->updateOrCreate(
                ['email' => $technician['email']],
                [
                    'name' => $technician['name'],
                    'password' => 'password',
                    'role' => User::ROLE_TECHNICIAN,
                    'email_verified_at' => now(),
                ],
            );

            return [
                $technician['name'] => Technician::query()->updateOrCreate(
                    ['email' => $technician['email']],
                    [
                        'user_id' => $user->getKey(),
                        'name' => $technician['name'],
                        'phone' => $technician['phone'],
                        'address' => 'Yangon field office',
                        'status' => Technician::STATUS_ACTIVE,
                    ],
                ),
            ];
        });

        $customers = collect([
            ['code' => 'CUST-1001', 'name' => 'Mingalar Mart', 'phone' => '09770001001', 'township' => 'Sanchaung'],
            ['code' => 'CUST-1002', 'name' => 'Shwe Family Home', 'phone' => '09770001002', 'township' => 'Kamayut'],
            ['code' => 'CUST-1003', 'name' => 'Golden Lotus Cafe', 'phone' => '09770001003', 'township' => 'Bahan'],
            ['code' => 'CUST-1004', 'name' => 'City Dental Clinic', 'phone' => '09770001004', 'township' => 'Tamwe'],
            ['code' => 'CUST-1005', 'name' => 'Yadanar Office', 'phone' => '09770001005', 'township' => 'Hlaing'],
            ['code' => 'CUST-1006', 'name' => 'Apex Learning Center', 'phone' => '09770001006', 'township' => 'Yankin'],
            ['code' => 'CUST-1007', 'name' => 'River View Condo', 'phone' => '09770001007', 'township' => 'Ahlone'],
            ['code' => 'CUST-1008', 'name' => 'Tech Hub Co Ltd', 'phone' => '09770001008', 'township' => 'Mayangone'],
            ['code' => 'CUST-1009', 'name' => 'North Star Store', 'phone' => '09770001009', 'township' => 'Insein'],
            ['code' => 'CUST-1010', 'name' => 'Royal Pearl Residence', 'phone' => '09770001010', 'township' => 'Thingangyun'],
        ])->mapWithKeys(function (array $customer): array {
            $email = str($customer['code'])->lower()->append('@example.com')->toString();
            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $customer['name'],
                    'password' => 'password',
                    'role' => User::ROLE_CUSTOMER,
                    'email_verified_at' => now(),
                ],
            );

            return [
                $customer['code'] => Customer::query()->updateOrCreate(
                    ['customer_code' => $customer['code']],
                    [
                        'user_id' => $user->getKey(),
                        'package_id' => 'FTTH-50M',
                        'name' => $customer['name'],
                        'phone' => $customer['phone'],
                        'secondary_phone' => null,
                        'email' => $email,
                        'address' => "{$customer['township']} Township, Yangon",
                        'township' => $customer['township'],
                        'city' => 'Yangon',
                        'status' => Customer::STATUS_ACTIVE,
                    ],
                ),
            ];
        });

        $tickets = [
            [
                'ticket_no' => 'TKT-SAMPLE-0001',
                'customer' => 'CUST-1001',
                'category' => 'Connection Drop',
                'technician' => null,
                'subject' => 'Internet connection drops every hour',
                'priority' => Ticket::PRIORITY_HIGH,
                'status' => Ticket::STATUS_OPEN,
                'reported_at' => now()->subDays(3),
            ],
            [
                'ticket_no' => 'TKT-SAMPLE-0002',
                'customer' => 'CUST-1002',
                'category' => 'LOS Signal',
                'technician' => 'Aung Technician',
                'job_no' => 'JOB-SAMPLE-0002',
                'subject' => 'LOS light blinking on ONU',
                'priority' => Ticket::PRIORITY_URGENT,
                'status' => Ticket::STATUS_ASSIGNED,
                'job_status' => TechnicianJob::STATUS_ASSIGNED,
                'scheduled_date' => today(),
                'reported_at' => now()->subDays(2),
                'assigned_at' => now()->subDay(),
            ],
            [
                'ticket_no' => 'TKT-SAMPLE-0003',
                'customer' => 'CUST-1003',
                'category' => 'Slow Speed',
                'technician' => 'Myo Technician',
                'job_no' => 'JOB-SAMPLE-0003',
                'subject' => 'Speed below 10 Mbps during evening',
                'priority' => Ticket::PRIORITY_MEDIUM,
                'status' => Ticket::STATUS_IN_PROGRESS,
                'job_status' => TechnicianJob::STATUS_IN_PROGRESS,
                'scheduled_date' => today(),
                'reported_at' => now()->subDays(2),
                'assigned_at' => now()->subDay(),
                'started_at' => now()->subHours(2),
            ],
            [
                'ticket_no' => 'TKT-SAMPLE-0004',
                'customer' => 'CUST-1004',
                'category' => 'Router Issue',
                'technician' => 'Htet Technician',
                'job_no' => 'JOB-SAMPLE-0004',
                'subject' => 'Router Wi-Fi signal is unstable',
                'priority' => Ticket::PRIORITY_LOW,
                'status' => Ticket::STATUS_CLOSED,
                'job_status' => TechnicianJob::STATUS_COMPLETED,
                'scheduled_date' => today(),
                'reported_at' => now()->subDays(4),
                'assigned_at' => now()->subDays(3),
                'started_at' => now()->subHours(5),
                'completed_at' => now()->subHours(3),
                'resolved_at' => now()->subHours(3),
                'closed_at' => now()->subHours(3),
                'resolution_note' => 'Router channel changed and signal verified.',
            ],
            [
                'ticket_no' => 'TKT-SAMPLE-0005',
                'customer' => 'CUST-1005',
                'category' => 'Connection Drop',
                'technician' => 'Nay Technician',
                'job_no' => 'JOB-SAMPLE-0005',
                'subject' => 'Fiber patch cord damaged',
                'priority' => Ticket::PRIORITY_HIGH,
                'status' => Ticket::STATUS_CLOSED,
                'job_status' => TechnicianJob::STATUS_COMPLETED,
                'scheduled_date' => today()->subDay(),
                'reported_at' => now()->subDays(5),
                'assigned_at' => now()->subDays(4),
                'started_at' => now()->subDays(2),
                'completed_at' => now()->subDay(),
                'resolved_at' => now()->subDay(),
                'closed_at' => now()->subHours(12),
                'resolution_note' => 'Patch cord replaced and service restored.',
            ],
            [
                'ticket_no' => 'TKT-SAMPLE-0006',
                'customer' => 'CUST-1006',
                'category' => 'Slow Speed',
                'technician' => 'Aung Technician',
                'job_no' => 'JOB-SAMPLE-0006',
                'subject' => 'High latency to local services',
                'priority' => Ticket::PRIORITY_MEDIUM,
                'status' => Ticket::STATUS_ASSIGNED,
                'job_status' => TechnicianJob::STATUS_ASSIGNED,
                'scheduled_date' => today()->addDay(),
                'reported_at' => now()->subDay(),
                'assigned_at' => now()->subHours(8),
            ],
            [
                'ticket_no' => 'TKT-SAMPLE-0007',
                'customer' => 'CUST-1007',
                'category' => 'Router Issue',
                'technician' => null,
                'subject' => 'Customer cannot access router admin page',
                'priority' => Ticket::PRIORITY_LOW,
                'status' => Ticket::STATUS_OPEN,
                'reported_at' => now()->subHours(10),
            ],
            [
                'ticket_no' => 'TKT-SAMPLE-0008',
                'customer' => 'CUST-1008',
                'category' => 'LOS Signal',
                'technician' => 'Myo Technician',
                'job_no' => 'JOB-SAMPLE-0008',
                'subject' => 'Signal level out of range',
                'priority' => Ticket::PRIORITY_URGENT,
                'status' => Ticket::STATUS_CLOSED,
                'job_status' => TechnicianJob::STATUS_COMPLETED,
                'scheduled_date' => today(),
                'reported_at' => now()->subDays(3),
                'assigned_at' => now()->subDays(2),
                'started_at' => now()->subHours(4),
                'completed_at' => now()->subHour(),
                'resolved_at' => now()->subHour(),
                'closed_at' => now()->subHour(),
                'resolution_note' => 'Connector cleaned and optical level normalized.',
            ],
            [
                'ticket_no' => 'TKT-SAMPLE-0009',
                'customer' => 'CUST-1009',
                'category' => 'Connection Drop',
                'technician' => 'Htet Technician',
                'job_no' => 'JOB-SAMPLE-0009',
                'subject' => 'Frequent PPPoE reconnects',
                'priority' => Ticket::PRIORITY_HIGH,
                'status' => Ticket::STATUS_IN_PROGRESS,
                'job_status' => TechnicianJob::STATUS_IN_PROGRESS,
                'scheduled_date' => today(),
                'reported_at' => now()->subHours(18),
                'assigned_at' => now()->subHours(12),
                'started_at' => now()->subHour(),
            ],
            [
                'ticket_no' => 'TKT-SAMPLE-0010',
                'customer' => 'CUST-1010',
                'category' => 'Router Issue',
                'technician' => 'Nay Technician',
                'job_no' => 'JOB-SAMPLE-0010',
                'subject' => 'Customer cancelled router replacement visit',
                'priority' => Ticket::PRIORITY_MEDIUM,
                'status' => Ticket::STATUS_CANCELLED,
                'job_status' => TechnicianJob::STATUS_CANCELLED,
                'scheduled_date' => today()->addDays(2),
                'reported_at' => now()->subDays(2),
                'assigned_at' => now()->subDay(),
            ],
        ];

        foreach ($tickets as $ticketData) {
            $technician = isset($ticketData['technician'])
                ? $technicians->get($ticketData['technician'])
                : null;

            $ticket = Ticket::query()->updateOrCreate(
                ['ticket_no' => $ticketData['ticket_no']],
                [
                    'customer_id' => $customers->get($ticketData['customer'])->getKey(),
                    'ticket_category_id' => $categories->get($ticketData['category'])->getKey(),
                    'technician_id' => $technician?->getKey(),
                    'created_by' => $admin->getKey(),
                    'subject' => $ticketData['subject'],
                    'description' => "Sample ticket for {$ticketData['subject']}.",
                    'priority' => $ticketData['priority'],
                    'status' => $ticketData['status'],
                    'reported_at' => $ticketData['reported_at'],
                    'assigned_at' => $ticketData['assigned_at'] ?? null,
                    'resolved_at' => $ticketData['resolved_at'] ?? null,
                    'closed_at' => $ticketData['closed_at'] ?? null,
                    'resolution_note' => $ticketData['resolution_note'] ?? null,
                ],
            );

            $ticket->comments()->firstOrCreate(
                ['comment' => 'Sample customer complaint recorded for testing.'],
                [
                    'user_id' => $admin->getKey(),
                    'technician_id' => null,
                ],
            );

            if (! isset($ticketData['job_no']) || ! $technician instanceof Technician) {
                continue;
            }

            TechnicianJob::query()->updateOrCreate(
                ['job_no' => $ticketData['job_no']],
                [
                    'ticket_id' => $ticket->getKey(),
                    'customer_id' => $ticket->customer_id,
                    'technician_id' => $technician->getKey(),
                    'job_type' => TechnicianJob::TYPE_COMPLAINT_CHECK,
                    'status' => $ticketData['job_status'],
                    'scheduled_date' => $ticketData['scheduled_date'],
                    'started_at' => $ticketData['started_at'] ?? null,
                    'completed_at' => $ticketData['completed_at'] ?? null,
                ],
            );
        }
    }
}
