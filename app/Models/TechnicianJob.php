<?php

namespace App\Models;

use App\Services\TicketCommentService;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

#[Fillable([
    'ticket_id',
    'customer_id',
    'technician_id',
    'job_no',
    'job_type',
    'status',
    'scheduled_date',
    'estimated_arrival_at',
    'started_at',
    'completed_at',
])]
class TechnicianJob extends Model
{
    public const TYPE_COMPLAINT_CHECK = 'complaint_check';

    public const TYPE_MAINTENANCE = 'maintenance';

    public const TYPE_REPAIR = 'repair';

    public const TYPE_ROUTER_REPLACEMENT = 'router_replacement';

    public const TYPE_FIBER_CHECK = 'fiber_check';

    public const TYPE_SIGNAL_CHECK = 'signal_check';

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * @var array<string, string>
     */
    protected $attributes = [
        'job_type' => self::TYPE_COMPLAINT_CHECK,
        'status' => self::STATUS_ASSIGNED,
    ];

    /**
     * @return array<string, string>
     */
    public static function jobTypeOptions(): array
    {
        return [
            self::TYPE_COMPLAINT_CHECK => 'Complaint Check',
            self::TYPE_MAINTENANCE => 'Maintenance',
            self::TYPE_REPAIR => 'Repair',
            self::TYPE_ROUTER_REPLACEMENT => 'Router Replacement',
            self::TYPE_FIBER_CHECK => 'Fiber Check',
            self::TYPE_SIGNAL_CHECK => 'Signal Check',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_ASSIGNED => 'Assigned',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function activeStatuses(): array
    {
        return [
            self::STATUS_ASSIGNED,
            self::STATUS_IN_PROGRESS,
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', self::activeStatuses());
    }

    public function scopeAssigned(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ASSIGNED);
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopeForTechnician(Builder $query, int $technicianId): Builder
    {
        return $query->where('technician_id', $technicianId);
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('scheduled_date', now()->toDateString());
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(JobPhoto::class);
    }

    public function isAssigned(): bool
    {
        return $this->status === self::STATUS_ASSIGNED;
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function canStart(): bool
    {
        return $this->isAssigned();
    }

    public function canComplete(): bool
    {
        return $this->isInProgress();
    }

    public function canCancel(): bool
    {
        return ! $this->isCompleted() && ! $this->isCancelled();
    }

    public function startJob(): self
    {
        return $this->startJobWithEstimatedArrival();
    }

    public function startJobWithEstimatedArrival(mixed $estimatedArrivalAt = null): self
    {
        return DB::transaction(function () use ($estimatedArrivalAt): self {
            $job = self::query()
                ->whereKey($this->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $job->authorizeJobAction('start');

            if (! $job->isAssigned()) {
                throw new DomainException('Only assigned jobs can be started.');
            }

            $ticket = Ticket::query()
                ->whereKey($job->ticket_id)
                ->lockForUpdate()
                ->firstOrFail();

            $jobData = [
                'status' => self::STATUS_IN_PROGRESS,
                'started_at' => now(),
            ];

            if ($estimatedArrivalAt !== null) {
                $jobData['estimated_arrival_at'] = $estimatedArrivalAt;
            }

            $job->forceFill($jobData)->save();

            $ticket->forceFill([
                'status' => Ticket::STATUS_IN_PROGRESS,
            ])->save();

            $job->addTicketComment($ticket, 'Technician started the job.');

            return $job->refresh()->load(['customer', 'technician', 'ticket']);
        }, attempts: 3);
    }

    public function completeJob(?string $comment = null): self
    {
        $comment = filled($comment) ? trim($comment) : null;

        return DB::transaction(function () use ($comment): self {
            $job = self::query()
                ->whereKey($this->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $job->authorizeJobAction('complete');

            if ($job->isCancelled()) {
                throw new DomainException('Cancelled jobs cannot be completed.');
            }

            if ($job->isCompleted()) {
                throw new DomainException('Completed jobs cannot be completed again.');
            }

            if (! $job->isInProgress()) {
                throw new DomainException('Only in-progress jobs can be completed.');
            }

            $ticket = Ticket::query()
                ->whereKey($job->ticket_id)
                ->lockForUpdate()
                ->firstOrFail();

            $jobData = [
                'status' => self::STATUS_COMPLETED,
                'completed_at' => now(),
            ];

            $job->forceFill($jobData)->save();

            $ticketData = [
                'status' => Ticket::STATUS_CLOSED,
                'resolved_at' => now(),
                'closed_at' => now(),
            ];

            if (filled($comment)) {
                $ticketData['resolution_note'] = $comment;
            }

            $ticket->forceFill($ticketData)->save();

            if (filled($comment)) {
                $job->addTicketComment($ticket, $comment);
            }

            $job->addTicketComment($ticket, 'Technician completed the job and closed the ticket.');

            return $job->refresh()->load(['customer', 'technician', 'ticket']);
        }, attempts: 3);
    }

    public function cancelJob(?string $reason = null): self
    {
        return DB::transaction(function () use ($reason): self {
            $job = self::query()
                ->whereKey($this->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $job->authorizeJobAction('cancel');

            if ($job->isCompleted() || $job->isCancelled()) {
                throw new DomainException('Completed or cancelled jobs cannot be cancelled.');
            }

            $ticket = Ticket::query()
                ->whereKey($job->ticket_id)
                ->lockForUpdate()
                ->firstOrFail();

            $job->forceFill([
                'status' => self::STATUS_CANCELLED,
            ])->save();

            $comment = 'Technician job cancelled.';

            if (filled($reason)) {
                $comment .= " Reason: {$reason}";
            }

            $job->addTicketComment($ticket, $comment);

            return $job->refresh()->load(['customer', 'technician', 'ticket']);
        }, attempts: 3);
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? Str::headline((string) $this->status);
    }

    public function jobTypeLabel(): string
    {
        return self::jobTypeOptions()[$this->job_type] ?? Str::headline((string) $this->job_type);
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            self::STATUS_IN_PROGRESS => 'warning',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_CANCELLED => 'danger',
            default => 'info',
        };
    }

    protected static function booted(): void
    {
        static::saving(function (TechnicianJob $technicianJob): void {
            $technicianJob->guardJobValues();
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'estimated_arrival_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected function guardJobValues(): void
    {
        if (! array_key_exists((string) $this->job_type, self::jobTypeOptions())) {
            throw new InvalidArgumentException("Invalid technician job type [{$this->job_type}].");
        }

        if (! array_key_exists((string) $this->status, self::statusOptions())) {
            throw new InvalidArgumentException("Invalid technician job status [{$this->status}].");
        }
    }

    protected function addTicketComment(Ticket $ticket, string $comment): void
    {
        app(TicketCommentService::class)->addCommentForCurrentUser($ticket, $comment);
    }

    /**
     * @throws AuthorizationException
     */
    protected function authorizeJobAction(string $ability): void
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $user->can($ability, $this)) {
            throw new AuthorizationException('You are not allowed to perform this technician job action.');
        }
    }
}
