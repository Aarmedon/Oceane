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
        Schema::create('game_state', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('current_turn')->default(1);
            $table->timestamp('last_resolved_at')->nullable();
            $table->timestamps();
        });

        // Seed a single row
        DB::table('game_state')->insert([
            'current_turn' => config('oceane.game.current_turn', 1),
            'last_resolved_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_state');
    }
};
