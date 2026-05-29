<?php

namespace App\Services\Tickets;

use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketAssignmentService;
use App\Services\TicketCommentService;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class TicketWorkflowService
{
    public function __construct(protected TicketCommentService $comments) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Ticket
    {
        $this->authorizeTicketAction('create', Ticket::class);

        return DB::transaction(function () use ($data): Ticket {
            return Ticket::query()->create($data)->refresh();
        });
    }

    public function assignTechnician(Ticket $ticket, int $technicianId): Ticket
    {
        return app(TicketAssignmentService::class)->assign($ticket, $technicianId, auth()->id());
    }

    public function markInProgress(Ticket $ticket): Ticket
    {
        return DB::transaction(function () use ($ticket): Ticket {
            $lockedTicket = $this->lockTicket($ticket);

            $this->authorizeTicketAction('markInProgress', $lockedTicket);

            if (! $lockedTicket->canMarkInProgress()) {
                throw new DomainException('Only assigned tickets with a technician can be marked in progress.');
            }

            $lockedTicket->forceFill([
                'status' => Ticket::STATUS_IN_PROGRESS,
            ])->save();

            return $lockedTicket->refresh();
        });
    }

    public function close(Ticket $ticket): Ticket
    {
        return DB::transaction(function () use ($ticket): Ticket {
            $lockedTicket = $this->lockTicket($ticket);

            $this->authorizeTicketAction('close', $lockedTicket);

            if (! $lockedTicket->canClose()) {
                throw new DomainException('Closed or cancelled tickets cannot be closed.');
            }

            $lockedTicket->forceFill([
                'status' => Ticket::STATUS_CLOSED,
                'resolved_at' => now(),
                'closed_at' => now(),
            ])->save();

            $this->comments->addCommentForCurrentUser($lockedTicket, 'Ticket closed.');

            return $lockedTicket->refresh();
        });
    }

    protected function lockTicket(Ticket $ticket): Ticket
    {
        return Ticket::query()
            ->whereKey($ticket->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * @param  class-string<Ticket>|Ticket  $arguments
     *
     * @throws AuthorizationException
     */
    protected function authorizeTicketAction(string $ability, string|Ticket $arguments): void
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $user->can($ability, $arguments)) {
            throw new AuthorizationException('You are not allowed to perform this ticket action.');
        }
    }
}
