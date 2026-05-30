<?php

namespace App\Models;

use Database\Factories\ChurnPredictionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'customer_id',
    'customer_code',
    'complaints',
    'downtime_hours',
    'resolution_time',
    'duration_time',
    'description',
    'churn_prediction',
    'churn_probability',
    'sentiment_label',
    'sentiment_score',
    'predicted_at',
])]
class ChurnPrediction extends Model
{
    /** @use HasFactory<ChurnPredictionFactory> */
    use HasFactory;

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'complaints' => 'integer',
            'downtime_hours' => 'decimal:2',
            'resolution_time' => 'decimal:2',
            'duration_time' => 'integer',
            'churn_prediction' => 'boolean',
            'churn_probability' => 'decimal:4',
            'sentiment_score' => 'decimal:3',
            'predicted_at' => 'datetime',
        ];
    }
}
