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
        // Align legacy races schema with the new model expectations safely/idempotently
        if (!Schema::hasTable('races')) {
            return; // Nothing to do if table doesn't exist (shouldn't happen)
        }

        // Ensure required columns exist
        if (!Schema::hasColumn('races', 'slug')) {
            Schema::table('races', function (Blueprint $table) {
                $table->string('slug')->after('name');
            });
        }

        if (!Schema::hasColumn('races', 'description')) {
            Schema::table('races', function (Blueprint $table) {
                $table->text('description')->nullable()->after('slug');
            });
        }

        if (!Schema::hasColumn('races', 'bonuses')) {
            Schema::table('races', function (Blueprint $table) {
                $table->json('bonuses')->nullable()->after('description');
            });
        }

        if (!Schema::hasColumn('races', 'penalties')) {
            Schema::table('races', function (Blueprint $table) {
                $table->json('penalties')->nullable()->after('bonuses');
            });
        }

        if (!Schema::hasColumn('races', 'image_path')) {
            Schema::table('races', function (Blueprint $table) {
                $table->string('image_path')->nullable()->after('penalties');
            });
        }

        if (!Schema::hasColumn('races', 'is_playable')) {
            Schema::table('races', function (Blueprint $table) {
                $table->boolean('is_playable')->default(true)->after('image_path');
            });
        }

        // Add unique constraints where possible (ignore if data conflicts or already exists)
        try {
            Schema::table('races', function (Blueprint $table) {
                $table->unique('name');
            });
        } catch (\Throwable $e) {}

        try {
            Schema::table('races', function (Blueprint $table) {
                $table->unique('slug');
            });
        } catch (\Throwable $e) {}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('races')) {
            return;
        }

        // Drop unique constraints if present (best-effort)
        try {
            Schema::table('races', function (Blueprint $table) {
                $table->dropUnique(['name']);
            });
        } catch (\Throwable $e) {}

        try {
            Schema::table('races', function (Blueprint $table) {
                $table->dropUnique(['slug']);
            });
        } catch (\Throwable $e) {}

        // Optionally drop added columns (best-effort; keep data if unsupported)
        foreach (['is_playable', 'image_path', 'penalties', 'bonuses', 'description', 'slug'] as $col) {
            if (Schema::hasColumn('races', $col)) {
                try {
                    Schema::table('races', function (Blueprint $table) use ($col) {
                        $table->dropColumn($col);
                    });
                } catch (\Throwable $e) {}
            }
        }
    }
};
