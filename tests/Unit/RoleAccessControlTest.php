<?php

namespace Tests\Unit;

use App\Models\Technician;
use App\Models\TechnicianJob;
use App\Models\Ticket;
use App\Models\User;
use App\Policies\TechnicianJobPolicy;
use App\Policies\TicketCommentPolicy;
use App\Policies\TicketPolicy;
use Tests\TestCase;

class RoleAccessControlTest extends TestCase
{
    public function test_user_role_helpers_identify_roles_and_permissions(): void
    {
        $admin = $this->userWithRole(User::ROLE_ADMIN);
        $technician = $this->userWithRole(User::ROLE_TECHNICIAN);

        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->hasAnyRole([User::ROLE_ADMIN, User::ROLE_SUPPORT]));
        $this->assertTrue($admin->canAssignTickets());
        $this->assertTrue($admin->canViewCompanyDashboard());

        $this->assertTrue($technician->isTechnician());
        $this->assertFalse($technician->canAssignTickets());
        $this->assertFalse($technician->canViewCompanyDashboard());
    }

    public function test_ticket_policy_hides_full_ticket_resource_from_technicians(): void
    {
        $policy = new TicketPolicy();
        $manager = $this->userWithRole(User::ROLE_MANAGER);
        $support = $this->userWithRole(User::ROLE_SUPPORT);
        $technician = $this->technicianUser(10);
        $openTicket = new Ticket(['status' => Ticket::STATUS_OPEN]);

        $this->assertTrue($policy->viewAny($manager));
        $this->assertTrue($policy->view($manager, $openTicket));
        $this->assertFalse($policy->update($manager, $openTicket));
        $this->assertTrue($policy->close($manager, $openTicket));

        $this->assertTrue($policy->create($support));
        $this->assertTrue($policy->update($support, $openTicket));

        $this->assertFalse($policy->viewAny($technician));
        $this->assertFalse($policy->view($technician, $openTicket));
        $this->assertFalse($policy->assignTechnician($technician, $openTicket));
    }

    public function test_technician_job_policy_limits_technicians_to_own_jobs(): void
    {
        $policy = new TechnicianJobPolicy();
        $assignedTechnician = $this->technicianUser(15);
        $otherTechnician = $this->technicianUser(20);
        $admin = $this->userWithRole(User::ROLE_ADMIN);
        $manager = $this->userWithRole(User::ROLE_MANAGER);
        $job = new TechnicianJob(['technician_id' => 15]);

        $this->assertTrue($policy->view($assignedTechnician, $job));
        $this->assertTrue($policy->start($assignedTechnician, $job));
        $this->assertTrue($policy->complete($assignedTechnician, $job));
        $this->assertFalse($policy->cancel($assignedTechnician, $job));

        $this->assertFalse($policy->view($otherTechnician, $job));
        $this->assertFalse($policy->start($otherTechnician, $job));
        $this->assertFalse($policy->complete($otherTechnician, $job));

        $this->assertTrue($policy->view($admin, $job));
        $this->assertTrue($policy->update($admin, $job));
        $this->assertTrue($policy->cancel($manager, $job));
    }

    public function test_ticket_comment_policy_allows_only_internal_or_assigned_technician_comments(): void
    {
        $policy = new TicketCommentPolicy();
        $support = $this->userWithRole(User::ROLE_SUPPORT);
        $assignedTechnician = $this->technicianUser(25);
        $otherTechnician = $this->technicianUser(30);
        $ticket = new Ticket(['technician_id' => 25]);

        $this->assertTrue($policy->createForTicket($support, $ticket));
        $this->assertTrue($policy->createForTicket($assignedTechnician, $ticket));
        $this->assertFalse($policy->createForTicket($otherTechnician, $ticket));
    }

    protected function userWithRole(string $role): User
    {
        return new User([
            'name' => 'Test User',
            'email' => "{$role}@example.com",
            'role' => $role,
        ]);
    }

    protected function technicianUser(int $technicianId): User
    {
        $user = $this->userWithRole(User::ROLE_TECHNICIAN);
        $technician = new Technician();
        $technician->forceFill(['id' => $technicianId]);
        $technician->exists = true;

        $user->setRelation('technician', $technician);

        return $user;
    }
}
