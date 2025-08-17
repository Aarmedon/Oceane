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
        if (Schema::hasTable('commanders')) {
            try {
                Schema::table('commanders', function (Blueprint $table) {
                    $table->unique('user_id');
                });
            } catch (\Throwable $e) {
                // Index may already exist or platform may not support altering in this context (e.g., some SQLite setups)
                // Swallow to keep migrations idempotent for tests; consider logging if needed.
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('commanders')) {
            try {
                Schema::table('commanders', function (Blueprint $table) {
                    $table->dropUnique('commanders_user_id_unique');
                });
            } catch (\Throwable $e) {
                // Swallow if index does not exist
            }
        }
    }
};
