<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $defaultId = (int) config('oceane.directives.patrol', 0);
        // Backfill nulls just in case
        DB::table('fleets')->whereNull('directive_id')->update(['directive_id' => $defaultId]);

        // Enforce NOT NULL + DEFAULT for MySQL/MariaDB only (portable guard)
        $driver = DB::getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'])) {
            DB::statement("ALTER TABLE `" . DB::getTablePrefix() . "fleets` MODIFY `directive_id` INT UNSIGNED NOT NULL DEFAULT {$defaultId}");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'])) {
            DB::statement("ALTER TABLE `" . DB::getTablePrefix() . "fleets` MODIFY `directive_id` INT UNSIGNED NULL");
        }
    }
};
