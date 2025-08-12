<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ships', function (Blueprint $table) {
            // Add composite index (fleet_id, ship_design_id)
            $indexName1 = 'ships_fleet_design_index';
            $exists1 = DB::select("SELECT COUNT(1) AS cnt FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'ships' AND index_name = ?", [$indexName1]);
            if (empty($exists1) || $exists1[0]->cnt == 0) {
                $table->index(['fleet_id', 'ship_design_id'], $indexName1);
            }

            // Add composite index (fleet_id, status)
            $indexName2 = 'ships_fleet_status_index';
            $exists2 = DB::select("SELECT COUNT(1) AS cnt FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'ships' AND index_name = ?", [$indexName2]);
            if (empty($exists2) || $exists2[0]->cnt == 0) {
                $table->index(['fleet_id', 'status'], $indexName2);
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
