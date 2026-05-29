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
        if (! Schema::hasTable('technician_jobs')) {
            return;
        }

        $columns = array_values(array_filter(
            $this->coordinateColumns(),
            fn (string $column): bool => Schema::hasColumn('technician_jobs', $column),
        ));

        if ($columns === []) {
            return;
        }

        Schema::table('technician_jobs', function (Blueprint $table) use ($columns): void {
            $table->dropColumn($columns);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('technician_jobs')) {
            return;
        }

        Schema::table('technician_jobs', function (Blueprint $table): void {
            if (! Schema::hasColumn('technician_jobs', 'check_in_latitude')) {
                $table->decimal('check_in_latitude', 10, 7)->nullable();
            }

            if (! Schema::hasColumn('technician_jobs', 'check_in_longitude')) {
                $table->decimal('check_in_longitude', 10, 7)->nullable();
            }

            if (! Schema::hasColumn('technician_jobs', 'check_out_latitude')) {
                $table->decimal('check_out_latitude', 10, 7)->nullable();
            }

            if (! Schema::hasColumn('technician_jobs', 'check_out_longitude')) {
                $table->decimal('check_out_longitude', 10, 7)->nullable();
            }
        });
    }

    /**
     * @return array<int, string>
     */
    private function coordinateColumns(): array
    {
        return [
            'check_in_latitude',
            'check_in_longitude',
            'check_out_latitude',
            'check_out_longitude',
        ];
    }
};
