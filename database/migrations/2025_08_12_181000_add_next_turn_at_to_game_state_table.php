<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('game_state', function (Blueprint $table) {
            $table->timestamp('next_turn_at')->nullable()->after('last_resolved_at');
        });

        // Initialize next_turn_at for existing row
        if (DB::table('game_state')->count() > 0) {
            DB::table('game_state')->update([
                'next_turn_at' => now()->addHours(config('oceane.game.hours_between_turns', 24)),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_state', function (Blueprint $table) {
            $table->dropColumn('next_turn_at');
        });
    }
};
