<?php

namespace App\Models;

use App\Services\TicketAssignmentService;
use App\Services\TicketCommentService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use InvalidArgumentException;

#[Fillable([
    'customer_id',
    'ticket_category_id',
    'technician_id',
    'created_by',
    'ticket_no',
    'subject',
    'description',
    'priority',
    'status',
    'reported_at',
    'assigned_at',
    'resolved_at',
    'closed_at',
    'resolution_note',
    'device_id',
])]
class Ticket extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_CANCELLED = 'cancelled';

    public const PRIORITY_LOW = 'low';

    public const PRIORITY_MEDIUM = 'medium';

    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_URGENT = 'urgent';

    /**
     * @var array<string, string>
     */
    protected $attributes = [
        'priority' => self::PRIORITY_MEDIUM,
        'status' => self::STATUS_OPEN,
    ];

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_OPEN => 'Open',
            self::STATUS_ASSIGNED => 'Assigned',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_CLOSED => 'Closed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function priorityOptions(): array
    {
        return [
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_MEDIUM => 'Medium',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_URGENT => 'Urgent',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'ticket_category_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class)
            ->orderBy('created_at')
            ->orderBy('id');
    }

    public function technicianJobs(): HasMany
    {
        return $this->hasMany(TechnicianJob::class);
    }

    public function activeTechnicianJob(): HasOne
    {
        return $this->hasOne(TechnicianJob::class)
            ->whereIn('status', TechnicianJob::activeStatuses())
            ->latestOfMany();
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isAssigned(): bool
    {
        return $this->status === self::STATUS_ASSIGNED;
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function canAssignTechnician(): bool
    {
        return in_array($this->status, [
            self::STATUS_OPEN,
            self::STATUS_ASSIGNED,
            self::STATUS_IN_PROGRESS,
        ], true);
    }

    public function assignToTechnician(int $technicianId, ?int $assignedByUserId = null): self
    {
        return app(TicketAssignmentService::class)->assign($this, $technicianId, $assignedByUserId);
    }

    public function addComment(
        string $comment,
        ?int $userId = null,
        ?int $technicianId = null
    ): TicketComment {
        return app(TicketCommentService::class)->addComment($this, $comment, $userId, $technicianId);
    }

    public function canMarkInProgress(): bool
    {
        return $this->isAssigned() && filled($this->technician_id);
    }

    public function canClose(): bool
    {
        return ! $this->isClosed() && ! $this->isCancelled();
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? Str::headline((string) $this->status);
    }

    public function priorityLabel(): string
    {
        return self::priorityOptions()[$this->priority] ?? Str::headline((string) $this->priority);
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            self::STATUS_ASSIGNED => 'info',
            self::STATUS_IN_PROGRESS => 'warning',
            self::STATUS_CLOSED => 'success',
            self::STATUS_CANCELLED => 'danger',
            default => 'gray',
        };
    }

    public function priorityColor(): string
    {
        return match ($this->priority) {
            self::PRIORITY_URGENT => 'danger',
            self::PRIORITY_HIGH => 'warning',
            self::PRIORITY_MEDIUM => 'info',
            default => 'gray',
        };
    }

    protected static function booted(): void
    {
        static::saving(function (Ticket $ticket): void {
            $ticket->normalizeLifecycleAttributes();
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reported_at' => 'datetime',
            'assigned_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    protected function normalizeLifecycleAttributes(): void
    {
        if (! $this->exists && blank($this->ticket_no)) {
            $this->ticket_no = self::generateTicketNumber();
        }

        if (blank($this->reported_at)) {
            $this->reported_at = now();
        }

        if (blank($this->priority)) {
            $this->priority = self::PRIORITY_MEDIUM;
        }

        if (blank($this->status)) {
            $this->status = self::STATUS_OPEN;
        }

        $this->guardLifecycleValues();
        $this->syncAssignmentAttributes();
        $this->syncCompletionAttributes();
    }

    protected function guardLifecycleValues(): void
    {
        if (! array_key_exists((string) $this->status, self::statusOptions())) {
            throw new InvalidArgumentException("Invalid ticket status [{$this->status}].");
        }

        if (! array_key_exists((string) $this->priority, self::priorityOptions())) {
            throw new InvalidArgumentException("Invalid ticket priority [{$this->priority}].");
        }
    }

    protected function syncAssignmentAttributes(): void
    {
        if (blank($this->technician_id) || ! $this->isOpen()) {
            return;
        }

        $this->status = self::STATUS_ASSIGNED;
        $this->assigned_at ??= now();
    }

    protected function syncCompletionAttributes(): void
    {
        if ($this->status === self::STATUS_CLOSED) {
            $this->resolved_at ??= now();
            $this->closed_at ??= now();
        }
    }

    protected static function generateTicketNumber(): string
    {
        do {
            $ticketNo = sprintf('TKT-%s-%s', now()->format('Ymd'), Str::upper(Str::random(6)));
        } while (self::query()->where('ticket_no', $ticketNo)->exists());

        return $ticketNo;
    }
}
