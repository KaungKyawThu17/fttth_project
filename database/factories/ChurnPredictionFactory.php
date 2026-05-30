<?php

namespace Database\Factories;

use App\Models\ChurnPrediction;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ChurnPrediction>
 */
class ChurnPredictionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $customer = Customer::query()->create([
            'customer_code' => 'CUST-'.Str::upper(Str::random(8)),
            'name' => fake()->name(),
            'status' => Customer::STATUS_ACTIVE,
        ]);

        return [
            'customer_id' => $customer->getKey(),
            'customer_code' => $customer->customer_code,
            'complaints' => fake()->numberBetween(0, 12),
            'downtime_hours' => fake()->randomFloat(2, 0, 96),
            'resolution_time' => fake()->randomFloat(2, 0, 48),
            'duration_time' => fake()->numberBetween(1, 36),
            'description' => fake()->sentence(),
            'churn_prediction' => fake()->boolean(),
            'churn_probability' => fake()->randomFloat(4, 0, 1),
            'sentiment_label' => fake()->randomElement(['positive', 'neutral', 'negative']),
            'sentiment_score' => fake()->randomFloat(3, -1, 1),
            'predicted_at' => now(),
        ];
    }
}
