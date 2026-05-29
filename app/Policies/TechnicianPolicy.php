<?php

namespace App\Policies;

use App\Models\Technician;
use App\Models\User;

class TechnicianPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canViewTechnicians();
    }

    public function view(User $user, Technician $technician): bool
    {
        return $user->canViewTechnicians()
            || $user->technicianProfileId() === (int) $technician->getKey();
    }

    public function create(User $user): bool
    {
        return $user->canManageTechnicians();
    }

    public function update(User $user, Technician $technician): bool
    {
        return $user->canManageTechnicians();
    }

    public function delete(User $user, Technician $technician): bool
    {
        return $user->canManageTechnicians();
    }

    public function restore(User $user, Technician $technician): bool
    {
        return $user->canManageTechnicians();
    }

    public function forceDelete(User $user, Technician $technician): bool
    {
        return $user->canManageTechnicians();
    }
}
