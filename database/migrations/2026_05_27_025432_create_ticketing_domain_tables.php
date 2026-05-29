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
        if (! Schema::hasTable('customers')) {
            Schema::create('customers', function (Blueprint $table): void {
                $table->id();
                $table->string('package_id')->nullable();
                $table->string('customer_code')->unique();
                $table->string('name');
                $table->string('phone')->nullable();
                $table->string('secondary_phone')->nullable();
                $table->string('email')->nullable();
                $table->text('address')->nullable();
                $table->string('township')->nullable();
                $table->string('city')->nullable();
                $table->string('status', 50)->default('active')->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('ticket_categories')) {
            Schema::create('ticket_categories', function (Blueprint $table): void {
                $table->id();
                $table->string('name')->unique();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('technicians')) {
            Schema::create('technicians', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')
                    ->nullable()
                    ->unique()
                    ->constrained()
                    ->nullOnDelete();
                $table->string('name');
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->text('address')->nullable();
                $table->string('status', 50)->default('active')->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('tickets')) {
            Schema::create('tickets', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('customer_id')
                    ->constrained()
                    ->restrictOnDelete();
                $table->foreignId('ticket_category_id')
                    ->constrained()
                    ->restrictOnDelete();
                $table->foreignId('technician_id')
                    ->nullable()
                    ->constrained()
                    ->nullOnDelete();
                $table->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->string('ticket_no')->unique();
                $table->string('subject', 150);
                $table->text('description');
                $table->string('priority', 50)->default('medium')->index();
                $table->string('status', 50)->default('open')->index();
                $table->timestamp('reported_at')->nullable();
                $table->timestamp('assigned_at')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->text('resolution_note')->nullable();
                $table->timestamps();

                $table->index(['customer_id', 'status']);
                $table->index(['technician_id', 'status']);
                $table->index(['reported_at', 'status']);
            });
        }

        if (! Schema::hasTable('technician_jobs')) {
            Schema::create('technician_jobs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('ticket_id')
                    ->constrained()
                    ->cascadeOnDelete();
                $table->foreignId('customer_id')
                    ->constrained()
                    ->restrictOnDelete();
                $table->foreignId('technician_id')
                    ->constrained()
                    ->restrictOnDelete();
                $table->string('job_no')->unique();
                $table->string('job_type', 50)->default('complaint_check')->index();
                $table->string('status', 50)->default('assigned')->index();
                $table->date('scheduled_date')->nullable()->index();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->decimal('check_in_latitude', 10, 7)->nullable();
                $table->decimal('check_in_longitude', 10, 7)->nullable();
                $table->decimal('check_out_latitude', 10, 7)->nullable();
                $table->decimal('check_out_longitude', 10, 7)->nullable();
                $table->text('finding_note')->nullable();
                $table->text('action_taken')->nullable();
                $table->timestamps();

                $table->index(['ticket_id', 'status']);
                $table->index(['technician_id', 'status']);
                $table->index(['status', 'completed_at']);
            });
        }

        if (! Schema::hasTable('ticket_comments')) {
            Schema::create('ticket_comments', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('ticket_id')
                    ->constrained()
                    ->cascadeOnDelete();
                $table->foreignId('user_id')
                    ->nullable()
                    ->constrained()
                    ->nullOnDelete();
                $table->foreignId('technician_id')
                    ->nullable()
                    ->constrained()
                    ->nullOnDelete();
                $table->text('comment');
                $table->boolean('is_internal')->default(true)->index();
                $table->timestamps();

                $table->index(['ticket_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('job_photos')) {
            Schema::create('job_photos', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('technician_job_id')
                    ->constrained()
                    ->cascadeOnDelete();
                $table->string('photo_path');
                $table->string('photo_type', 50)->default('other')->index();
                $table->timestamp('uploaded_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_photos');
        Schema::dropIfExists('ticket_comments');
        Schema::dropIfExists('technician_jobs');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('technicians');
        Schema::dropIfExists('ticket_categories');
        Schema::dropIfExists('customers');
    }
};
