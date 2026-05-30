<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('churn_predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('customer_code');
            $table->unsignedInteger('complaints')->default(0);
            $table->decimal('downtime_hours', 10, 2)->default(0);
            $table->decimal('resolution_time', 10, 2)->default(0);
            $table->unsignedSmallInteger('duration_time')->default(0);
            $table->text('description');
            $table->boolean('churn_prediction');
            $table->decimal('churn_probability', 5, 4);
            $table->string('sentiment_label', 50);
            $table->decimal('sentiment_score', 6, 3);
            $table->timestamp('predicted_at');
            $table->timestamps();

            $table->unique('customer_id');
            $table->index('customer_code');
            $table->index('predicted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('churn_predictions');
    }
};
