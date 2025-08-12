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
        Schema::create('commander_technologies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commander_id')->constrained('commanders')->onDelete('cascade');
            $table->foreignId('technology_id')->constrained('technologies')->onDelete('cascade');
            $table->integer('level')->default(0);
            $table->integer('research_progress')->default(0);
            $table->integer('research_total')->default(100);
            $table->boolean('is_researching')->default(false);
            $table->integer('research_completion_turn')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['commander_id', 'technology_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commander_technologies');
    }
};
