<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\TechnicianJob;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canViewTickets();
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $user->canViewTickets();
    }

    public function create(User $user): bool
    {
        return $user->canCreateTickets();
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $user->canUpdateTickets();
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin();
    }

    public function assignTechnician(User $user, Ticket $ticket): bool
    {
        return $user->canAssignTickets() && $ticket->canAssignTechnician();
    }

    public function markInProgress(User $user, Ticket $ticket): bool
    {
        return $user->canUpdateTickets() && $ticket->canMarkInProgress();
    }

    public function close(User $user, Ticket $ticket): bool
    {
        return $user->canCloseTickets() && $ticket->canClose();
    }

    public function comment(User $user, Ticket $ticket): bool
    {
        if ($user->canAddTicketComments()) {
            return true;
        }

        $technicianId = $user->technicianProfileId();

        if ($technicianId === null) {
            return false;
        }

        if ((int) $ticket->technician_id === $technicianId) {
            return true;
        }

        if ($ticket->getKey() === null) {
            return false;
        }

        return TechnicianJob::query()
            ->where('ticket_id', $ticket->getKey())
            ->where('technician_id', $technicianId)
            ->exists();
    }

    public function createComment(User $user, Ticket $ticket): bool
    {
        return $this->comment($user, $ticket);
    }

    public function viewComments(User $user, Ticket $ticket): bool
    {
        return $this->comment($user, $ticket) || $user->can('view', $ticket);
    }
}
