<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\JobPhoto;
use App\Models\Technician;
use App\Models\TechnicianJob;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApiPortalTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_customer_can_login_create_and_view_only_own_tickets(): void
    {
        $customerUser = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
        ]);
        $customer = $this->createCustomer($customerUser);
        $otherCustomer = $this->createCustomer(
            User::factory()->create(['role' => User::ROLE_CUSTOMER])
        );
        $category = TicketCategory::query()->create([
            'name' => 'Connection Drop',
            'is_active' => true,
        ]);
        $otherTicket = $this->createTicket($otherCustomer, $category);

        $loginResponse = $this->postJson('/api/login', [
            'phone' => $customer->phone,
            'password' => 'password',
            'device_name' => 'customer-portal',
        ]);

        $loginResponse
            ->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.role', User::ROLE_CUSTOMER);

        $headers = $this->bearerHeaders($loginResponse->json('access_token'));

        $createResponse = $this->postJson('/api/customer/tickets', [
            'ticket_category_id' => $category->getKey(),
            'subject' => 'Internet is down',
            'description' => 'Customer cannot browse any website.',
            'priority' => Ticket::PRIORITY_HIGH,
        ], $headers);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.status', Ticket::STATUS_OPEN)
            ->assertJsonPath('data.subject', 'Internet is down');

        $ticketId = $createResponse->json('data.id');

        $this->getJson('/api/customer/tickets', $headers)
            ->assertOk()
            ->assertJsonPath('data.0.id', $ticketId);

        $this->getJson("/api/customer/tickets/{$ticketId}", $headers)
            ->assertOk()
            ->assertJsonPath('data.id', $ticketId);

        $this->getJson("/api/customer/tickets/{$otherTicket->getKey()}", $headers)
            ->assertForbidden();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticketId,
            'customer_id' => $customer->getKey(),
            'status' => Ticket::STATUS_OPEN,
        ]);
    }

    public function test_customer_cannot_create_new_ticket_while_active_ticket_exists(): void
    {
        $customerUser = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
        ]);
        $customer = $this->createCustomer($customerUser);
        $category = TicketCategory::query()->create([
            'name' => 'Connection Drop',
            'is_active' => true,
        ]);

        $token = $customerUser->issueApiToken('test');
        $headers = $this->bearerHeaders($token);

        $this->postJson('/api/customer/tickets', [
            'ticket_category_id' => $category->getKey(),
            'subject' => 'First issue',
            'description' => 'Internet is down.',
        ], $headers)
            ->assertCreated();

        $this->postJson('/api/customer/tickets', [
            'ticket_category_id' => $category->getKey(),
            'subject' => 'Second issue',
            'description' => 'Still having problems.',
        ], $headers)
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('ticket');

        $customer->tickets()->first()->forceFill([
            'status' => Ticket::STATUS_CLOSED,
            'resolved_at' => now(),
            'closed_at' => now(),
        ])->save();

        $this->postJson('/api/customer/tickets', [
            'ticket_category_id' => $category->getKey(),
            'subject' => 'Third issue',
            'description' => 'New problem after resolution.',
        ], $headers)
            ->assertCreated();
    }

    public function test_technician_can_manage_own_job_lifecycle_through_api(): void
    {
        Storage::fake('public');

        $technicianUser = User::factory()->create([
            'role' => User::ROLE_TECHNICIAN,
        ]);
        $job = $this->createTechnicianJob($technicianUser);
        $technician = $job->technician()->firstOrFail();

        $loginResponse = $this->postJson('/api/login', [
            'phone' => $technician->phone,
            'password' => 'password',
            'device_name' => 'technician-portal',
        ]);

        $loginResponse
            ->assertOk()
            ->assertJsonPath('user.role', User::ROLE_TECHNICIAN);

        $headers = $this->bearerHeaders($loginResponse->json('access_token'));

        $this->getJson('/api/technician/jobs', $headers)
            ->assertOk()
            ->assertJsonPath('data.0.id', $job->getKey());

        $estimatedArrivalAt = now()->addDay()->toDateString();

        $this->postJson("/api/technician/jobs/{$job->getKey()}/start", [
            'estimated_arrival_at' => $estimatedArrivalAt,
        ], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', TechnicianJob::STATUS_IN_PROGRESS)
            ->assertJsonPath('data.estimated_arrival_at', $estimatedArrivalAt);

        $photoResponse = $this->post("/api/technician/jobs/{$job->getKey()}/photos", [
            'photo' => UploadedFile::fake()->image('issue.jpg'),
        ], $headers + ['Accept' => 'application/json']);

        $photoResponse
            ->assertCreated()
            ->assertJsonPath('data.photo_type', JobPhoto::TYPE_AFTER);

        $photoPath = $photoResponse->json('data.photo_path');
        Storage::disk('public')->assertExists($photoPath);

        $this->postJson("/api/technician/jobs/{$job->getKey()}/complete", [
            'comment' => 'Cleaned connector and verified link.',
        ], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', TechnicianJob::STATUS_COMPLETED)
            ->assertJsonPath('data.ticket.status', Ticket::STATUS_CLOSED);

        $this->assertDatabaseHas('tickets', [
            'id' => $job->ticket_id,
            'status' => Ticket::STATUS_CLOSED,
            'resolution_note' => 'Cleaned connector and verified link.',
        ]);
        $this->assertDatabaseHas('ticket_comments', [
            'ticket_id' => $job->ticket_id,
            'technician_id' => $technician->getKey(),
            'comment' => 'Cleaned connector and verified link.',
        ]);
    }

    public function test_technician_cannot_view_another_technicians_job(): void
    {
        $assignedUser = User::factory()->create([
            'role' => User::ROLE_TECHNICIAN,
        ]);
        $otherUser = User::factory()->create([
            'role' => User::ROLE_TECHNICIAN,
        ]);
        $job = $this->createTechnicianJob($assignedUser);
        $this->createTechnicianProfile($otherUser);

        $token = $otherUser->issueApiToken('test');

        $this->getJson("/api/technician/jobs/{$job->getKey()}", $this->bearerHeaders($token))
            ->assertForbidden();
    }

    public function test_logout_revokes_the_current_api_token(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
        ]);
        $this->createCustomer($user);
        $token = $user->issueApiToken('test');
        $headers = $this->bearerHeaders($token);

        $this->getJson('/api/me', $headers)
            ->assertOk();

        $this->postJson('/api/logout', [], $headers)
            ->assertNoContent();

        $this->getJson('/api/me', $headers)
            ->assertUnauthorized();
    }

    public function test_phone_login_rejects_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
        ]);
        $customer = $this->createCustomer($user);

        $this->postJson('/api/login', [
            'phone' => $customer->phone,
            'password' => 'wrong-password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('phone');
    }

    protected function bearerHeaders(string $token): array
    {
        return [
            'Authorization' => "Bearer {$token}",
        ];
    }

    protected function createCustomer(User $user): Customer
    {
        return Customer::query()->create([
            'user_id' => $user->getKey(),
            'customer_code' => 'CUST-'.Str::upper(Str::random(8)),
            'name' => $user->name,
            'phone' => $this->phoneForUser($user, '09'),
            'email' => $user->email,
            'status' => Customer::STATUS_ACTIVE,
        ]);
    }

    protected function createTechnicianProfile(User $user): Technician
    {
        return Technician::query()->create([
            'user_id' => $user->getKey(),
            'name' => $user->name,
            'phone' => $this->phoneForUser($user, '08'),
            'email' => $user->email,
            'status' => Technician::STATUS_ACTIVE,
        ]);
    }

    protected function createTicket(Customer $customer, TicketCategory $category): Ticket
    {
        return Ticket::query()->create([
            'customer_id' => $customer->getKey(),
            'ticket_category_id' => $category->getKey(),
            'ticket_no' => 'TKT-'.Str::upper(Str::random(8)),
            'subject' => 'Existing ticket',
            'description' => 'Existing ticket for another customer.',
            'priority' => Ticket::PRIORITY_MEDIUM,
            'status' => Ticket::STATUS_OPEN,
            'reported_at' => now(),
        ]);
    }

    protected function createTechnicianJob(User $technicianUser): TechnicianJob
    {
        $customer = $this->createCustomer(User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
        ]));
        $category = TicketCategory::query()->create([
            'name' => 'LOS '.Str::upper(Str::random(6)),
            'is_active' => true,
        ]);
        $technician = $this->createTechnicianProfile($technicianUser);
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

    protected function phoneForUser(User $user, string $prefix): string
    {
        return $prefix.str_pad((string) $user->getKey(), 9, '0', STR_PAD_LEFT);
    }
}
