<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\TechnicianJob;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canViewCustomers();
    }

    public function view(User $user, Customer $customer): bool
    {
        if ($user->canViewCustomers()) {
            return true;
        }

        $technicianId = $user->technicianProfileId();

        if ($technicianId === null) {
            return false;
        }

        return TechnicianJob::query()
            ->where('customer_id', $customer->getKey())
            ->where('technician_id', $technicianId)
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->canManageCustomers();
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->canManageCustomers();
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->canManageCustomers();
    }

    public function restore(User $user, Customer $customer): bool
    {
        return $user->canManageCustomers();
    }

    public function forceDelete(User $user, Customer $customer): bool
    {
        return $user->canManageCustomers();
    }
}
