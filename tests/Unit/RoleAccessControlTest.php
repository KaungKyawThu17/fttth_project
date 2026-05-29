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
        $manager = $this->userWithRole(User::ROLE_MANAGER);
        $support = $this->userWithRole(User::ROLE_SUPPORT);
        $technician = $this->userWithRole(User::ROLE_TECHNICIAN);

        $this->assertSame([
            User::ROLE_ADMIN => 'Admin',
            User::ROLE_MANAGER => 'Manager',
            User::ROLE_SUPPORT => 'Support',
        ], User::roleOptions());

        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->hasAnyRole([User::ROLE_ADMIN, User::ROLE_SUPPORT]));
        $this->assertTrue($admin->canAssignTickets());
        $this->assertTrue($admin->canManageTickets());
        $this->assertTrue($admin->canViewCompanyDashboard());

        $this->assertTrue($manager->canAssignTickets());
        $this->assertTrue($manager->canManageTickets());
        $this->assertTrue($manager->canManageTechnicians());
        $this->assertTrue($manager->canUploadJobPhotos());

        $this->assertTrue($support->canViewTickets());
        $this->assertTrue($support->canViewTechnicians());
        $this->assertFalse($support->canAssignTickets());
        $this->assertFalse($support->canManageTickets());
        $this->assertFalse($support->canManageTechnicians());
        $this->assertFalse($support->canUploadJobPhotos());

        $this->assertTrue($technician->isTechnician());
        $this->assertFalse($technician->hasAnyRole(User::panelAccessRoles()));
        $this->assertFalse($technician->canAssignTickets());
        $this->assertFalse($technician->canViewCompanyDashboard());
    }

    public function test_ticket_policy_hides_full_ticket_resource_from_technicians(): void
    {
        $policy = new TicketPolicy;
        $manager = $this->userWithRole(User::ROLE_MANAGER);
        $support = $this->userWithRole(User::ROLE_SUPPORT);
        $technician = $this->technicianUser(10);
        $openTicket = new Ticket(['status' => Ticket::STATUS_OPEN]);

        $this->assertTrue($policy->viewAny($manager));
        $this->assertTrue($policy->view($manager, $openTicket));
        $this->assertTrue($policy->create($manager));
        $this->assertTrue($policy->update($manager, $openTicket));
        $this->assertTrue($policy->delete($manager, $openTicket));
        $this->assertTrue($policy->assignTechnician($manager, $openTicket));
        $this->assertTrue($policy->close($manager, $openTicket));

        $this->assertTrue($policy->viewAny($support));
        $this->assertTrue($policy->view($support, $openTicket));
        $this->assertFalse($policy->create($support));
        $this->assertFalse($policy->update($support, $openTicket));
        $this->assertFalse($policy->delete($support, $openTicket));
        $this->assertFalse($policy->assignTechnician($support, $openTicket));
        $this->assertFalse($policy->close($support, $openTicket));

        $this->assertFalse($policy->viewAny($technician));
        $this->assertFalse($policy->view($technician, $openTicket));
        $this->assertFalse($policy->assignTechnician($technician, $openTicket));
    }

    public function test_technician_job_policy_limits_technicians_to_own_jobs(): void
    {
        $policy = new TechnicianJobPolicy;
        $assignedTechnician = $this->technicianUser(15);
        $otherTechnician = $this->technicianUser(20);
        $admin = $this->userWithRole(User::ROLE_ADMIN);
        $manager = $this->userWithRole(User::ROLE_MANAGER);
        $support = $this->userWithRole(User::ROLE_SUPPORT);
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
        $this->assertTrue($policy->update($manager, $job));
        $this->assertTrue($policy->start($manager, $job));
        $this->assertTrue($policy->complete($manager, $job));
        $this->assertTrue($policy->cancel($manager, $job));

        $this->assertTrue($policy->view($support, $job));
        $this->assertFalse($policy->update($support, $job));
        $this->assertFalse($policy->start($support, $job));
        $this->assertFalse($policy->complete($support, $job));
        $this->assertFalse($policy->cancel($support, $job));
    }

    public function test_ticket_comment_policy_allows_only_managers_or_assigned_technician_comments(): void
    {
        $policy = new TicketCommentPolicy;
        $manager = $this->userWithRole(User::ROLE_MANAGER);
        $support = $this->userWithRole(User::ROLE_SUPPORT);
        $assignedTechnician = $this->technicianUser(25);
        $otherTechnician = $this->technicianUser(30);
        $ticket = new Ticket(['technician_id' => 25]);

        $this->assertTrue($policy->viewAny($support));
        $this->assertTrue($policy->createForTicket($manager, $ticket));
        $this->assertFalse($policy->createForTicket($support, $ticket));
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
        $technician = new Technician;
        $technician->forceFill(['id' => $technicianId]);
        $technician->exists = true;

        $user->setRelation('technician', $technician);

        return $user;
    }
}
