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
        // Legacy migration superseded by 2025_07_10_125810_create_races_table.php
        // No-op to avoid duplicate 'races' table creation with conflicting schema.
        if (Schema::hasTable('races')) {
            return;
        }
        // Intentionally left blank: table will be created by newer migration.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: let the newer migration handle dropping if needed.
    }
};

