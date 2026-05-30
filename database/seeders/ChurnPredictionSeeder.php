<?php

namespace Database\Seeders;

use App\Models\ChurnPrediction;
use App\Models\Customer;
use Illuminate\Database\Seeder;

class ChurnPredictionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Customer::query()
            ->orderBy('id')
            ->limit(15)
            ->get()
            ->each(function (Customer $customer): void {
                ChurnPrediction::query()->updateOrCreate(
                    ['customer_id' => $customer->getKey()],
                    [
                        'customer_code' => $customer->customer_code,
                        'complaints' => $customer->tickets()->count(),
                        'downtime_hours' => fake()->randomFloat(2, 0, 24),
                        'resolution_time' => fake()->randomFloat(2, 0, 12),
                        'duration_time' => fake()->numberBetween(1, 36),
                        'description' => fake()->sentence(),
                        'churn_prediction' => fake()->boolean(20),
                        'churn_probability' => fake()->randomFloat(4, 0, 1),
                        'sentiment_label' => fake()->randomElement(['positive', 'neutral', 'negative']),
                        'sentiment_score' => fake()->randomFloat(3, -1, 1),
                        'predicted_at' => now(),
                    ],
                );
            });
    }
}
