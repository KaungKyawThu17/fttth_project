<?php

namespace App\Services;

use App\Models\Technician;
use App\Models\TechnicianJob;
use App\Models\Ticket;
use App\Models\User;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TicketAssignmentService
{
    public function __construct(protected TicketCommentService $comments) {}

    public function assign(Ticket $ticket, int $technicianId, ?int $assignedByUserId = null): Ticket
    {
        return DB::transaction(function () use ($ticket, $technicianId, $assignedByUserId): Ticket {
            $assignedByUser = $this->resolveAssigner($assignedByUserId);
            $technician = $this->resolveActiveTechnician($technicianId);
            $lockedTicket = $this->lockTicket($ticket);

            if (! $lockedTicket->canAssignTechnician()) {
                throw new DomainException('Closed or cancelled tickets cannot be assigned to a technician.');
            }

            $lockedTicket->forceFill([
                'technician_id' => $technician->getKey(),
                'status' => Ticket::STATUS_ASSIGNED,
                'assigned_at' => now(),
            ])->save();

            $this->syncTechnicianJob($lockedTicket, $technician);
            $this->createAssignmentComment($lockedTicket, $technician, $assignedByUser);

            return $lockedTicket->refresh()->load(['activeTechnicianJob', 'comments', 'technician']);
        }, attempts: 3);
    }

    protected function resolveAssigner(?int $assignedByUserId): ?User
    {
        if ($assignedByUserId === null) {
            $authenticatedUser = auth()->user();

            if ($authenticatedUser instanceof User) {
                if (! $authenticatedUser->canAssignTickets()) {
                    throw new AuthorizationException('Only admin, manager, support, or NOC users can assign technicians.');
                }

                return $authenticatedUser;
            }

            throw new AuthorizationException('Only admin, manager, support, or NOC users can assign technicians.');
        }

        $user = User::query()->find($assignedByUserId);

        if (! $user?->canAssignTickets()) {
            throw new AuthorizationException('Only admin, manager, support, or NOC users can assign technicians.');
        }

        return $user;
    }

    protected function resolveActiveTechnician(int $technicianId): Technician
    {
        $technician = Technician::query()
            ->whereKey($technicianId)
            ->where('status', Technician::STATUS_ACTIVE)
            ->first();

        if (! $technician) {
            throw new InvalidArgumentException('The selected technician does not exist or is not active.');
        }

        return $technician;
    }

    protected function lockTicket(Ticket $ticket): Ticket
    {
        return Ticket::query()
            ->whereKey($ticket->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    protected function syncTechnicianJob(Ticket $ticket, Technician $technician): TechnicianJob
    {
        $activeJob = TechnicianJob::query()
            ->where('ticket_id', $ticket->getKey())
            ->active()
            ->lockForUpdate()
            ->first();

        if ($activeJob) {
            $activeJob->forceFill([
                'customer_id' => $ticket->customer_id,
                'technician_id' => $technician->getKey(),
                'job_type' => TechnicianJob::TYPE_COMPLAINT_CHECK,
                'status' => TechnicianJob::STATUS_ASSIGNED,
            ])->save();

            return $activeJob;
        }

        $this->lockJobNumberGeneration();

        return $this->createTechnicianJob($ticket, $technician);
    }

    protected function createTechnicianJob(Ticket $ticket, Technician $technician): TechnicianJob
    {
        $nextNumber = $this->nextJobNumber();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                return TechnicianJob::query()->create([
                    'ticket_id' => $ticket->getKey(),
                    'customer_id' => $ticket->customer_id,
                    'technician_id' => $technician->getKey(),
                    'job_no' => $this->formatJobNumber($nextNumber + $attempt),
                    'job_type' => TechnicianJob::TYPE_COMPLAINT_CHECK,
                    'status' => TechnicianJob::STATUS_ASSIGNED,
                    'scheduled_date' => null,
                ]);
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt === 4) {
                    throw $exception;
                }
            }
        }

        throw new DomainException('Unable to generate a unique technician job number.');
    }

    protected function createAssignmentComment(Ticket $ticket, Technician $technician, ?User $assignedByUser): void
    {
        $this->comments->addComment(
            ticket: $ticket,
            comment: "Ticket assigned to technician: {$technician->name}",
            userId: $assignedByUser?->getKey(),
        );
    }

    protected function lockJobNumberGeneration(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::select('select pg_advisory_xact_lock(?)', [422601]);
    }

    protected function nextJobNumber(): int
    {
        $latestJobNo = TechnicianJob::query()
            ->where('job_no', 'like', 'JOB-%')
            ->orderByDesc('id')
            ->value('job_no');

        if (! is_string($latestJobNo)) {
            return 1;
        }

        $latestNumber = (int) str($latestJobNo)->afterLast('-')->toString();

        return $latestNumber + 1;
    }

    protected function formatJobNumber(int $number): string
    {
        return sprintf('JOB-%04d', $number);
    }
}
