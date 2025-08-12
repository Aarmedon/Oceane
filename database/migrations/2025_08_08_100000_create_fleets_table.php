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
        Schema::create('fleets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commander_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->foreignId('current_system_id')->nullable()->constrained('star_systems')->nullOnDelete();
            $table->foreignId('destination_system_id')->nullable()->constrained('star_systems')->nullOnDelete();
            $table->integer('position_x')->default(0);
            $table->integer('position_y')->default(0);
            $table->foreignId('galaxy_id')->constrained('galaxies')->onDelete('cascade');
            $table->string('status')->default('docked');
            $table->integer('arrival_turn')->nullable();
            // Not constrained because we may use config-based directives
            $table->unsignedInteger('directive_id')->nullable();
            $table->foreignId('hero_id')->nullable()->constrained('heroes')->nullOnDelete();
            $table->integer('morale')->default(100);
            $table->integer('experience')->default(0);
            $table->decimal('maintenance_cost', 12, 2)->default(0);
            $table->timestamps();

            $table->index(['status', 'arrival_turn']);
            $table->index(['current_system_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fleets');
    }
};
