<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fleets', function (Blueprint $table) {
            if (!Schema::hasColumn('fleets', 'scheduled_for_deletion')) {
                $table->boolean('scheduled_for_deletion')->default(false)->after('maintenance_cost');
            }
            if (!Schema::hasColumn('fleets', 'scheduled_deletion_turn')) {
                $table->unsignedInteger('scheduled_deletion_turn')->nullable()->after('scheduled_for_deletion');
            }
            if (!Schema::hasColumn('fleets', 'scheduled_deletion_requested_at')) {
                $table->timestamp('scheduled_deletion_requested_at')->nullable()->after('scheduled_deletion_turn');
            }
            $table->index(['scheduled_for_deletion']);
        });
    }

    public function down(): void
    {
        Schema::table('fleets', function (Blueprint $table) {
            if (Schema::hasColumn('fleets', 'scheduled_deletion_requested_at')) {
                $table->dropColumn('scheduled_deletion_requested_at');
            }
            if (Schema::hasColumn('fleets', 'scheduled_deletion_turn')) {
                $table->dropColumn('scheduled_deletion_turn');
            }
            if (Schema::hasColumn('fleets', 'scheduled_for_deletion')) {
                $table->dropColumn('scheduled_for_deletion');
            }
        });
    }
};
