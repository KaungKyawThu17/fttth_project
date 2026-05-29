<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

#[Fillable([
    'technician_job_id',
    'photo_path',
    'photo_type',
    'uploaded_at',
])]
class JobPhoto extends Model
{
    public const TYPE_ISSUE = 'issue';

    public const TYPE_BEFORE = 'before';

    public const TYPE_AFTER = 'after';

    public const TYPE_OTHER = 'other';

    /**
     * @return array<string, string>
     */
    public static function photoTypeOptions(): array
    {
        return [
            self::TYPE_ISSUE => 'Issue',
            self::TYPE_BEFORE => 'Before',
            self::TYPE_AFTER => 'After',
            self::TYPE_OTHER => 'Other',
        ];
    }

    public function technicianJob(): BelongsTo
    {
        return $this->belongsTo(TechnicianJob::class);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (blank($this->photo_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->photo_path);
    }

    public function isIssue(): bool
    {
        return $this->photo_type === self::TYPE_ISSUE;
    }

    public function isBefore(): bool
    {
        return $this->photo_type === self::TYPE_BEFORE;
    }

    public function isAfter(): bool
    {
        return $this->photo_type === self::TYPE_AFTER;
    }

    public function photoTypeLabel(): string
    {
        return self::photoTypeOptions()[$this->photo_type] ?? Str::headline((string) $this->photo_type);
    }

    protected static function booted(): void
    {
        static::creating(function (JobPhoto $jobPhoto): void {
            if ($jobPhoto->uploaded_at === null) {
                $jobPhoto->uploaded_at = now();
            }
        });

        static::saving(function (JobPhoto $jobPhoto): void {
            $jobPhoto->guardPhotoType();
        });

        static::deleting(function (JobPhoto $jobPhoto): void {
            $jobPhoto->deleteStoredFile();
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
        ];
    }

    protected function guardPhotoType(): void
    {
        if (! array_key_exists((string) $this->photo_type, self::photoTypeOptions())) {
            throw new InvalidArgumentException("Invalid job photo type [{$this->photo_type}].");
        }
    }

    protected function deleteStoredFile(): void
    {
        if (filled($this->photo_path)) {
            Storage::disk('public')->delete($this->photo_path);
        }
    }
}
