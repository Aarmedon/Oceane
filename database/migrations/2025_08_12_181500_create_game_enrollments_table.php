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
        Schema::create('game_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('commander_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type'); // 'join' | 'leave'
            $table->string('status')->default('pending'); // 'pending' | 'blocked' | 'processed' | 'rejected'
            $table->json('payload')->nullable(); // details like desired commander name, race, reason
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->unsignedInteger('processed_turn')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['status', 'type']);
            $table->index(['user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_enrollments');
    }
};
