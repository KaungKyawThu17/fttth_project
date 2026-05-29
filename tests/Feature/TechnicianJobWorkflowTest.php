<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Technician;
use App\Models\TechnicianJob;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TechnicianJobWorkflowTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_technician_completion_closes_the_ticket(): void
    {
        $technicianUser = User::factory()->create([
            'role' => User::ROLE_TECHNICIAN,
        ]);
        $job = $this->createTechnicianJob($technicianUser);

        $this->actingAs($technicianUser);

        $job->startJob();
        $completedJob = $job->refresh()->completeJob('Cleaned connector and verified service.');

        $ticket = $completedJob->ticket()->firstOrFail();

        $this->assertSame(TechnicianJob::STATUS_COMPLETED, $completedJob->status);
        $this->assertSame(Ticket::STATUS_CLOSED, $ticket->status);
        $this->assertSame('Cleaned connector and verified service.', $ticket->resolution_note);
        $this->assertNotNull($ticket->resolved_at);
        $this->assertNotNull($ticket->closed_at);
        $this->assertDatabaseHas('ticket_comments', [
            'ticket_id' => $ticket->getKey(),
            'technician_id' => $completedJob->technician_id,
            'comment' => 'Cleaned connector and verified service.',
        ]);
        $this->assertDatabaseHas('ticket_comments', [
            'ticket_id' => $ticket->getKey(),
            'technician_id' => $completedJob->technician_id,
            'comment' => 'Technician completed the job and closed the ticket.',
        ]);
    }

    protected function createTechnicianJob(User $technicianUser): TechnicianJob
    {
        $customer = Customer::query()->create([
            'customer_code' => 'CUST-'.Str::upper(Str::random(8)),
            'name' => 'Test Customer',
            'phone' => '09123456789',
            'status' => Customer::STATUS_ACTIVE,
        ]);
        $category = TicketCategory::query()->create([
            'name' => 'LOS '.Str::upper(Str::random(6)),
            'is_active' => true,
        ]);
        $technician = Technician::query()->create([
            'user_id' => $technicianUser->getKey(),
            'name' => $technicianUser->name,
            'phone' => '09987654321',
            'email' => $technicianUser->email,
            'status' => Technician::STATUS_ACTIVE,
        ]);
        $ticket = Ticket::query()->create([
            'customer_id' => $customer->getKey(),
            'ticket_category_id' => $category->getKey(),
            'technician_id' => $technician->getKey(),
            'ticket_no' => 'TKT-'.Str::upper(Str::random(8)),
            'subject' => 'Internet connection down',
            'description' => 'Customer reported LOS.',
            'priority' => Ticket::PRIORITY_MEDIUM,
            'status' => Ticket::STATUS_ASSIGNED,
            'reported_at' => now(),
            'assigned_at' => now(),
        ]);

        return TechnicianJob::query()->create([
            'ticket_id' => $ticket->getKey(),
            'customer_id' => $customer->getKey(),
            'technician_id' => $technician->getKey(),
            'job_no' => 'JOB-'.Str::upper(Str::random(8)),
            'job_type' => TechnicianJob::TYPE_COMPLAINT_CHECK,
            'status' => TechnicianJob::STATUS_ASSIGNED,
            'scheduled_date' => now()->toDateString(),
        ]);
    }
}
