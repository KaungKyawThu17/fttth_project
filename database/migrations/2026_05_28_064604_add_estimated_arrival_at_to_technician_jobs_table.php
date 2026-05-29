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
        if (! Schema::hasTable('technician_jobs') || Schema::hasColumn('technician_jobs', 'estimated_arrival_at')) {
            return;
        }

        Schema::table('technician_jobs', function (Blueprint $table): void {
            $table->timestamp('estimated_arrival_at')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('technician_jobs') || ! Schema::hasColumn('technician_jobs', 'estimated_arrival_at')) {
            return;
        }

        Schema::table('technician_jobs', function (Blueprint $table): void {
            $table->dropColumn('estimated_arrival_at');
        });
    }
};
