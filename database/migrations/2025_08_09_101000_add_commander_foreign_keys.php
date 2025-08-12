<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add FK on star_systems.commander_id -> commanders.id (SET NULL)
        if (Schema::hasTable('star_systems') && Schema::hasColumn('star_systems', 'commander_id')) {
            Schema::table('star_systems', function (Blueprint $table) {
                try { $table->foreign('commander_id')->references('id')->on('commanders')->nullOnDelete(); } catch (\Throwable $e) {}
            });
        }

        // Add FK on planets.commander_id -> commanders.id (SET NULL)
        if (Schema::hasTable('planets') && Schema::hasColumn('planets', 'commander_id')) {
            Schema::table('planets', function (Blueprint $table) {
                try { $table->foreign('commander_id')->references('id')->on('commanders')->nullOnDelete(); } catch (\Throwable $e) {}
            });
        }

        // Add FK on alliances.founder_id -> commanders.id (RESTRICT default)
        if (Schema::hasTable('alliances') && Schema::hasColumn('alliances', 'founder_id')) {
            Schema::table('alliances', function (Blueprint $table) {
                try { $table->foreign('founder_id')->references('id')->on('commanders'); } catch (\Throwable $e) {}
            });
        }

        // Add FK on alliance_members.commander_id -> commanders.id (CASCADE)
        if (Schema::hasTable('alliance_members') && Schema::hasColumn('alliance_members', 'commander_id')) {
            Schema::table('alliance_members', function (Blueprint $table) {
                try { $table->foreign('commander_id')->references('id')->on('commanders')->cascadeOnDelete(); } catch (\Throwable $e) {}
            });
        }

        // Add FK on orders.commander_id -> commanders.id (RESTRICT default)
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'commander_id')) {
            Schema::table('orders', function (Blueprint $table) {
                try { $table->foreign('commander_id')->references('id')->on('commanders'); } catch (\Throwable $e) {}
            });
        }

        // Add FK on turn_reports.commander_id -> commanders.id (RESTRICT default)
        if (Schema::hasTable('turn_reports') && Schema::hasColumn('turn_reports', 'commander_id')) {
            Schema::table('turn_reports', function (Blueprint $table) {
                try { $table->foreign('commander_id')->references('id')->on('commanders'); } catch (\Throwable $e) {}
            });
        }

        // Add FK on ship_designs.creator_id -> commanders.id (RESTRICT default)
        if (Schema::hasTable('ship_designs') && Schema::hasColumn('ship_designs', 'creator_id')) {
            Schema::table('ship_designs', function (Blueprint $table) {
                try { $table->foreign('creator_id')->references('id')->on('commanders'); } catch (\Throwable $e) {}
            });
        }

        // Add FK on commanders.race_id -> races.id (RESTRICT default)
        if (Schema::hasTable('commanders') && Schema::hasColumn('commanders', 'race_id')) {
            Schema::table('commanders', function (Blueprint $table) {
                try { $table->foreign('race_id')->references('id')->on('races'); } catch (\Throwable $e) {}
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('turn_reports')) {
            Schema::table('turn_reports', function (Blueprint $table) {
                try { $table->dropForeign(['commander_id']); } catch (\Throwable $e) {}
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                try { $table->dropForeign(['commander_id']); } catch (\Throwable $e) {}
            });
        }

        if (Schema::hasTable('alliance_members')) {
            Schema::table('alliance_members', function (Blueprint $table) {
                try { $table->dropForeign(['commander_id']); } catch (\Throwable $e) {}
            });
        }

        if (Schema::hasTable('alliances')) {
            Schema::table('alliances', function (Blueprint $table) {
                try { $table->dropForeign(['founder_id']); } catch (\Throwable $e) {}
            });
        }

        if (Schema::hasTable('planets')) {
            Schema::table('planets', function (Blueprint $table) {
                try { $table->dropForeign(['commander_id']); } catch (\Throwable $e) {}
            });
        }

        if (Schema::hasTable('star_systems')) {
            Schema::table('star_systems', function (Blueprint $table) {
                try { $table->dropForeign(['commander_id']); } catch (\Throwable $e) {}
            });
        }

        if (Schema::hasTable('ship_designs')) {
            Schema::table('ship_designs', function (Blueprint $table) {
                try { $table->dropForeign(['creator_id']); } catch (\Throwable $e) {}
            });
        }

        if (Schema::hasTable('commanders')) {
            Schema::table('commanders', function (Blueprint $table) {
                try { $table->dropForeign(['race_id']); } catch (\Throwable $e) {}
            });
        }
    }
};
