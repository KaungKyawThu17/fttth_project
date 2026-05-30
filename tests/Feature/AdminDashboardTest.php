<?php

namespace Tests\Feature;

use App\Filament\Resources\Tickets\TicketResource;
use App\Filament\Widgets\ComplaintCategoryReport;
use App\Filament\Widgets\TechnicianWorkloadReport;
use App\Filament\Widgets\TicketStatsOverview;
use App\Filament\Widgets\TodayTechnicianJobs;
use App\Models\Customer;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_dashboard_widgets_render_with_empty_ticketing_tables(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk();

        Livewire::actingAs($admin)
            ->test(TicketStatsOverview::class)
            ->assertStatus(200);

        Livewire::actingAs($admin)
            ->test(ComplaintCategoryReport::class)
            ->assertStatus(200);

        Livewire::actingAs($admin)
            ->test(TechnicianWorkloadReport::class)
            ->assertStatus(200);

        Livewire::actingAs($admin)
            ->test(TodayTechnicianJobs::class)
            ->assertStatus(200);
    }

    public function test_admin_ticket_view_refreshes_without_manual_reload(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);
        $customer = Customer::query()->create([
            'customer_code' => 'CUST-'.Str::upper(Str::random(8)),
            'name' => 'Test Customer',
            'status' => Customer::STATUS_ACTIVE,
        ]);
        $category = TicketCategory::query()->create([
            'name' => 'LOS '.Str::upper(Str::random(6)),
            'is_active' => true,
        ]);
        $ticket = Ticket::query()->create([
            'customer_id' => $customer->getKey(),
            'ticket_category_id' => $category->getKey(),
            'ticket_no' => 'TKT-'.Str::upper(Str::random(8)),
            'subject' => 'Internet connection down',
            'description' => 'Customer reported LOS.',
            'priority' => Ticket::PRIORITY_MEDIUM,
            'status' => Ticket::STATUS_OPEN,
            'reported_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(TicketResource::getUrl('view', ['record' => $ticket]))
            ->assertOk()
            ->assertSeeHtml('wire:poll.5s');
    }
}
