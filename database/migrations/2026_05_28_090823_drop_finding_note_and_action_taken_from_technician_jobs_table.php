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
        Schema::table('technician_jobs', function (Blueprint $table) {
            $table->dropColumn(['finding_note', 'action_taken']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('technician_jobs', function (Blueprint $table) {
            $table->text('finding_note')->nullable();
            $table->text('action_taken')->nullable();
        });
    }
};
