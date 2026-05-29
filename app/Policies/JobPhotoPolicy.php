<?php

namespace App\Policies;

use App\Models\JobPhoto;
use App\Models\TechnicianJob;
use App\Models\User;

class JobPhotoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canViewAllTechnicianJobs() || $user->technicianProfileId() !== null;
    }

    public function view(User $user, JobPhoto $jobPhoto): bool
    {
        $technicianJob = $jobPhoto->technicianJob;

        return $technicianJob instanceof TechnicianJob
            && $this->canViewJobPhoto($user, $technicianJob);
    }

    public function create(User $user): bool
    {
        return $user->canUploadJobPhotos() || $user->technicianProfileId() !== null;
    }

    public function update(User $user, JobPhoto $jobPhoto): bool
    {
        return $user->canManageTechnicianJobs();
    }

    public function delete(User $user, JobPhoto $jobPhoto): bool
    {
        return $user->canManageTechnicianJobs();
    }

    public function restore(User $user, JobPhoto $jobPhoto): bool
    {
        return $user->canManageTechnicianJobs();
    }

    public function forceDelete(User $user, JobPhoto $jobPhoto): bool
    {
        return $user->canManageTechnicianJobs();
    }

    public function createForJob(User $user, TechnicianJob $technicianJob): bool
    {
        return $user->canUploadJobPhotos() || $this->isAssignedTechnician($user, $technicianJob);
    }

    protected function canViewJobPhoto(User $user, TechnicianJob $technicianJob): bool
    {
        return $user->canViewAllTechnicianJobs()
            || $this->isAssignedTechnician($user, $technicianJob);
    }

    protected function isAssignedTechnician(User $user, TechnicianJob $technicianJob): bool
    {
        $technicianId = $user->technicianProfileId();

        return $technicianId !== null && (int) $technicianJob->technician_id === $technicianId;
    }
}
