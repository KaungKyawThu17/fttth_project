<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'user_id',
    'package_id',
    'customer_code',
    'name',
    'phone',
    'secondary_phone',
    'email',
    'address',
    'township',
    'city',
    'status',
])]
class Customer extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_TERMINATED = 'terminated';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function technicianJobs(): HasMany
    {
        return $this->hasMany(TechnicianJob::class);
    }

    public function churnPrediction(): HasOne
    {
        return $this->hasOne(ChurnPrediction::class);
    }
}
