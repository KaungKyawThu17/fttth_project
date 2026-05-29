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
        if (! Schema::hasTable('technicians')) {
            return;
        }

        Schema::table('technicians', function (Blueprint $table): void {
            if (! Schema::hasColumn('technicians', 'user_id')) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->unique()
                    ->constrained()
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('technicians') || ! Schema::hasColumn('technicians', 'user_id')) {
            return;
        }

        Schema::table('technicians', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
