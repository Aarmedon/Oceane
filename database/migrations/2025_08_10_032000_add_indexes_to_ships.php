<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();
        Schema::table('ships', function (Blueprint $table) use ($driver) {
            $indexName1 = 'ships_fleet_design_index';
            $indexName2 = 'ships_fleet_status_index';

            if (in_array($driver, ['mysql', 'mariadb'])) {
                // MySQL/MariaDB: check existence via information_schema
                $exists1 = DB::select("SELECT COUNT(1) AS cnt FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'ships' AND index_name = ?", [$indexName1]);
                if (empty($exists1) || ($exists1[0]->cnt ?? 0) == 0) {
                    $table->index(['fleet_id', 'ship_design_id'], $indexName1);
                }

                $exists2 = DB::select("SELECT COUNT(1) AS cnt FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'ships' AND index_name = ?", [$indexName2]);
                if (empty($exists2) || ($exists2[0]->cnt ?? 0) == 0) {
                    $table->index(['fleet_id', 'status'], $indexName2);
                }
            } else {
                // Other drivers (e.g., SQLite): just create indexes (fresh DB in tests)
                try { $table->index(['fleet_id', 'ship_design_id'], $indexName1); } catch (\Throwable $e) {}
                try { $table->index(['fleet_id', 'status'], $indexName2); } catch (\Throwable $e) {}
            }
        });
    }

    public function down(): void
    {
        Schema::table('ships', function (Blueprint $table) {
            $table->dropIndex('ships_fleet_design_index');
            $table->dropIndex('ships_fleet_status_index');
        });
    }
};
