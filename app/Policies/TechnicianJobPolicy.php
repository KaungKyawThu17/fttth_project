<?php

namespace App\Policies;

use App\Models\TechnicianJob;
use App\Models\User;

class TechnicianJobPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canViewAllTechnicianJobs() || $user->technicianProfileId() !== null;
    }

    public function view(User $user, TechnicianJob $technicianJob): bool
    {
        if ($user->canViewAllTechnicianJobs()) {
            return true;
        }

        return $this->isAssignedTechnician($user, $technicianJob);
    }

    public function create(User $user): bool
    {
        return $user->canManageTechnicianJobs();
    }

    public function update(User $user, TechnicianJob $technicianJob): bool
    {
        return $user->canManageTechnicianJobs();
    }

    public function delete(User $user, TechnicianJob $technicianJob): bool
    {
        return $user->canManageTechnicianJobs();
    }

    public function restore(User $user, TechnicianJob $technicianJob): bool
    {
        return $user->canManageTechnicianJobs();
    }

    public function forceDelete(User $user, TechnicianJob $technicianJob): bool
    {
        return $user->canManageTechnicianJobs();
    }

    public function start(User $user, TechnicianJob $technicianJob): bool
    {
        return $user->canManageTechnicianJobs() || $this->isAssignedTechnician($user, $technicianJob);
    }

    public function complete(User $user, TechnicianJob $technicianJob): bool
    {
        return $user->canManageTechnicianJobs() || $this->isAssignedTechnician($user, $technicianJob);
    }

    public function cancel(User $user, TechnicianJob $technicianJob): bool
    {
        return $user->canCancelTechnicianJobs();
    }

    protected function isAssignedTechnician(User $user, TechnicianJob $technicianJob): bool
    {
        $technicianId = $user->technicianProfileId();

        return $technicianId !== null && (int) $technicianJob->technician_id === $technicianId;
    }
}
