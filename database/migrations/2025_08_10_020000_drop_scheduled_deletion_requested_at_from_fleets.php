<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fleets', function (Blueprint $table) {
            if (Schema::hasColumn('fleets', 'scheduled_deletion_requested_at')) {
                $table->dropColumn('scheduled_deletion_requested_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fleets', function (Blueprint $table) {
            if (!Schema::hasColumn('fleets', 'scheduled_deletion_requested_at')) {
                $table->timestamp('scheduled_deletion_requested_at')->nullable()->after('scheduled_deletion_turn');
            }
        });
    }
};
