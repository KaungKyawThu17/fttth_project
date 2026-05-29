<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;

class TicketCommentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAddTicketComments() || $user->technicianProfileId() !== null;
    }

    public function view(User $user, TicketComment $ticketComment): bool
    {
        $ticket = $ticketComment->ticket;

        return $ticket instanceof Ticket && $user->can('viewComments', $ticket);
    }

    public function create(User $user): bool
    {
        return $user->canAddTicketComments() || $user->technicianProfileId() !== null;
    }

    public function update(User $user, TicketComment $ticketComment): bool
    {
        return false;
    }

    public function delete(User $user, TicketComment $ticketComment): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, TicketComment $ticketComment): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, TicketComment $ticketComment): bool
    {
        return $user->isAdmin();
    }

    public function createForTicket(User $user, Ticket $ticket): bool
    {
        return $user->can('createComment', $ticket);
    }
}
