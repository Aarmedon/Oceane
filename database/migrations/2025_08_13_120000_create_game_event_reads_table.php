<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_event_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_event_id')->constrained('game_events')->onDelete('cascade');
            $table->foreignId('commander_id')->constrained('commanders')->onDelete('cascade');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['game_event_id', 'commander_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_event_reads');
    }
};
