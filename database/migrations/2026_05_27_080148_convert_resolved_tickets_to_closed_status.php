<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('tickets')) {
            return;
        }

        DB::table('tickets')
            ->where('status', 'resolved')
            ->orderBy('id')
            ->select(['id', 'resolved_at', 'closed_at'])
            ->get()
            ->each(function (object $ticket): void {
                $closedAt = $ticket->closed_at ?? $ticket->resolved_at ?? now();

                DB::table('tickets')
                    ->where('id', $ticket->id)
                    ->update([
                        'status' => 'closed',
                        'resolved_at' => $ticket->resolved_at ?? $closedAt,
                        'closed_at' => $closedAt,
                        'updated_at' => now(),
                    ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
