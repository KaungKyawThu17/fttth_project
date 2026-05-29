<?php

namespace Tests\Feature;

use App\Filament\Resources\TechnicianJobs\Pages\EditTechnicianJob;
use App\Filament\Resources\TechnicianJobs\Pages\ViewTechnicianJob;
use App\Filament\Resources\TechnicianJobs\RelationManagers\PhotosRelationManager;
use App\Models\Customer;
use App\Models\JobPhoto;
use App\Models\Technician;
use App\Models\TechnicianJob;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use App\Policies\JobPhotoPolicy;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class JobPhotosModuleTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_job_photo_manager_renders_on_technician_job_pages(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);
        $job = $this->createTechnicianJob();

        Livewire::actingAs($admin)
            ->test(ViewTechnicianJob::class, [
                'record' => $job->getKey(),
            ])
            ->assertOk()
            ->assertSeeLivewire(PhotosRelationManager::class);

        Livewire::actingAs($admin)
            ->test(EditTechnicianJob::class, [
                'record' => $job->getKey(),
            ])
            ->assertOk()
            ->assertSeeLivewire(PhotosRelationManager::class);
    }

    public function test_support_can_upload_job_photo_to_public_disk(): void
    {
        Storage::fake('public');

        $support = User::factory()->create([
            'role' => User::ROLE_SUPPORT,
        ]);
        $job = $this->createTechnicianJob();

        Livewire::actingAs($support)
            ->test(PhotosRelationManager::class, [
                'ownerRecord' => $job,
                'pageClass' => ViewTechnicianJob::class,
            ])
            ->callAction(TestAction::make(CreateAction::class)->table(), data: [
                'photo_path' => UploadedFile::fake()->image('issue.jpg'),
            ])
            ->assertHasNoFormErrors();

        $photo = JobPhoto::query()->firstOrFail();

        $this->assertSame($job->getKey(), $photo->technician_job_id);
        $this->assertSame(JobPhoto::TYPE_ISSUE, $photo->photo_type);
        $this->assertTrue($photo->isIssue());
        $this->assertStringStartsWith("technician-jobs/{$job->getKey()}/", $photo->photo_path);
        $this->assertStringContainsString("/storage/technician-jobs/{$job->getKey()}/", $photo->photo_url);

        Storage::disk('public')->assertExists($photo->photo_path);
    }

    public function test_deleting_job_photo_deletes_only_the_stored_file(): void
    {
        Storage::fake('public');

        $job = $this->createTechnicianJob();
        $path = "technician-jobs/{$job->getKey()}/before.jpg";

        Storage::disk('public')->put($path, 'image-content');

        $photo = JobPhoto::query()->create([
            'technician_job_id' => $job->getKey(),
            'photo_path' => $path,
            'photo_type' => JobPhoto::TYPE_BEFORE,
        ]);

        $photo->delete();

        Storage::disk('public')->assertMissing($path);
        $this->assertModelExists($job);
        $this->assertDatabaseMissing('job_photos', [
            'id' => $photo->getKey(),
        ]);
    }

    public function test_job_photo_policy_limits_uploads_to_support_admin_or_assigned_technician(): void
    {
        $policy = new JobPhotoPolicy();

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);
        $support = User::factory()->create([
            'role' => User::ROLE_SUPPORT,
        ]);
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
        ]);
        $noc = User::factory()->create([
            'role' => User::ROLE_NOC,
        ]);
        $assignedTechnicianUser = User::factory()->create([
            'role' => User::ROLE_TECHNICIAN,
        ]);
        $otherTechnicianUser = User::factory()->create([
            'role' => User::ROLE_TECHNICIAN,
        ]);

        $job = $this->createTechnicianJob($assignedTechnicianUser);
        $this->createTechnicianProfile($otherTechnicianUser);

        $photo = JobPhoto::query()->create([
            'technician_job_id' => $job->getKey(),
            'photo_path' => "technician-jobs/{$job->getKey()}/issue.jpg",
            'photo_type' => JobPhoto::TYPE_ISSUE,
        ]);

        $this->assertTrue($policy->createForJob($admin, $job));
        $this->assertTrue($policy->createForJob($support, $job));
        $this->assertTrue($policy->createForJob($assignedTechnicianUser->refresh(), $job));

        $this->assertFalse($policy->createForJob($manager, $job));
        $this->assertFalse($policy->createForJob($noc, $job));
        $this->assertFalse($policy->createForJob($otherTechnicianUser->refresh(), $job));
        $this->assertFalse($policy->view($otherTechnicianUser, $photo));

        $this->assertTrue($policy->delete($admin, $photo));
        $this->assertFalse($policy->delete($support, $photo));
        $this->assertFalse($policy->delete($assignedTechnicianUser, $photo));
    }

    protected function createTechnicianJob(?User $technicianUser = null): TechnicianJob
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

    protected function createTechnicianProfile(?User $user = null): Technician
    {
        return Technician::query()->create([
            'user_id' => $user?->getKey(),
            'name' => $user?->name ?? 'Test Technician '.Str::upper(Str::random(4)),
            'phone' => '09987654321',
            'email' => $user?->email,
            'status' => Technician::STATUS_ACTIVE,
        ]);
    }
}
