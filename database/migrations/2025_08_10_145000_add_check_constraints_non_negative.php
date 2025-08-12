<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add CHECK constraints only for MySQL/MariaDB drivers
        $driver = DB::getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'])) {
            return;
        }

        // fleets.maintenance_cost >= 0
        try {
            $exists = DB::selectOne("SELECT COUNT(*) AS cnt FROM information_schema.table_constraints WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'fleets' AND CONSTRAINT_NAME = 'chk_fleets_maintenance_cost_nonneg'");
            if ((int)($exists->cnt ?? 0) === 0) {
                DB::statement("ALTER TABLE fleets ADD CONSTRAINT chk_fleets_maintenance_cost_nonneg CHECK (maintenance_cost >= 0)");
            }
        } catch (\Throwable $e) {
            // Some MariaDB/MySQL setups may not support CHECK or information_schema lookups as expected; ignore to keep migration idempotent
        }
        
        // Note: fleet_ship_stacks count_* columns are UNSIGNED already => inherently non-negative
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'])) {
            return;
        }

        try {
            // Drop CHECK constraint if it exists
            DB::statement("ALTER TABLE fleets DROP CONSTRAINT IF EXISTS chk_fleets_maintenance_cost_nonneg");
        } catch (\Throwable $e) {
            // Fallback for MySQL which may not support IF EXISTS on DROP CONSTRAINT for CHECK
            try {
                DB::statement("ALTER TABLE fleets DROP CONSTRAINT chk_fleets_maintenance_cost_nonneg");
            } catch (\Throwable $ignored) {
                // Ignore
            }
        }
    }
};
