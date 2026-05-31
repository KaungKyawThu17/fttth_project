<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'customer_id',
    'onu_serial_number',
    'onu_model',
    'mac_address',
    'router_model',
    'installation_date',
    'status',
])]
class Device extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function statusLabel(): string
    {
        return Str::headline($this->status);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'installation_date' => 'date',
        ];
    }
}
