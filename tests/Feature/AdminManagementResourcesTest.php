<?php

namespace Tests\Feature;

use App\Models\Technician;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdminManagementResourcesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_open_user_and_technician_management_pages(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);
        $technicianUser = User::factory()->create([
            'role' => User::ROLE_TECHNICIAN,
        ]);
        $technician = Technician::query()->create([
            'user_id' => $technicianUser->getKey(),
            'name' => 'Test Technician',
            'phone' => '09123456789',
            'email' => 'test.technician@example.com',
            'status' => Technician::STATUS_ACTIVE,
        ]);

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/users/create')
            ->assertOk();

        $this->actingAs($admin)
            ->get("/admin/users/{$technicianUser->getKey()}/edit")
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/technicians')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/technicians/create')
            ->assertOk();

        $this->actingAs($admin)
            ->get("/admin/technicians/{$technician->getKey()}/edit")
            ->assertOk();
    }

    public function test_manager_can_manage_technicians_but_not_users(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
        ]);

        $this->actingAs($manager)
            ->get('/admin/users')
            ->assertForbidden();

        $this->actingAs($manager)
            ->get('/admin/technicians')
            ->assertOk();

        $this->actingAs($manager)
            ->get('/admin/technicians/create')
            ->assertOk();
    }

    public function test_support_can_view_but_not_manage_administration_resources(): void
    {
        $support = User::factory()->create([
            'role' => User::ROLE_SUPPORT,
        ]);

        $this->actingAs($support)
            ->get('/admin/users')
            ->assertForbidden();

        $this->actingAs($support)
            ->get('/admin/technicians')
            ->assertOk();

        $this->actingAs($support)
            ->get('/admin/technicians/create')
            ->assertForbidden();
    }
}
