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

    public function test_non_admin_cannot_manage_users_or_create_technicians(): void
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
