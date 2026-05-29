<?php

namespace App\Services;

use App\Models\Technician;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use InvalidArgumentException;

class TicketCommentService
{
    /**
     * Add a ticket comment with explicit actor IDs.
     */
    public function addComment(
        Ticket $ticket,
        string $comment,
        ?int $userId = null,
        ?int $technicianId = null
    ): TicketComment {
        $comment = trim($comment);

        if (blank($comment)) {
            throw new InvalidArgumentException('Comment is required.');
        }

        if ($userId !== null && ! User::query()->whereKey($userId)->exists()) {
            throw new InvalidArgumentException('The selected user does not exist.');
        }

        if ($technicianId !== null && ! Technician::query()->whereKey($technicianId)->exists()) {
            throw new InvalidArgumentException('The selected technician does not exist.');
        }

        return $ticket->comments()->create([
            'user_id' => $userId,
            'technician_id' => $technicianId,
            'comment' => $comment,
        ]);
    }

    public function addCommentForCurrentUser(Ticket $ticket, string $comment): TicketComment
    {
        $actor = $this->currentActorAttributes();

        return $this->addComment(
            ticket: $ticket,
            comment: $comment,
            userId: $actor['user_id'],
            technicianId: $actor['technician_id'],
        );
    }

    /**
     * @throws AuthorizationException
     */
    public function addManualComment(Ticket $ticket, string $comment): TicketComment
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $this->canUserComment($user, $ticket)) {
            throw new AuthorizationException('You are not allowed to comment on this ticket.');
        }

        return $this->addCommentForCurrentUser($ticket, $comment);
    }

    public function canCurrentUserComment(Ticket $ticket): bool
    {
        $user = auth()->user();

        return $user instanceof User && $this->canUserComment($user, $ticket);
    }

    public function canUserComment(User $user, Ticket $ticket): bool
    {
        return $user->can('createForTicket', [TicketComment::class, $ticket]);
    }

    /**
     * @return array{user_id: int|null, technician_id: int|null}
     */
    protected function currentActorAttributes(): array
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return [
                'user_id' => null,
                'technician_id' => null,
            ];
        }

        if ($user->isTechnician()) {
            return [
                'user_id' => null,
                'technician_id' => $user->technicianProfileId(),
            ];
        }

        return [
            'user_id' => $user->getKey(),
            'technician_id' => null,
        ];
    }
}
