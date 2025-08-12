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
        Schema::create('ships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fleet_id')->constrained('fleets')->onDelete('cascade');
            $table->foreignId('ship_design_id')->constrained('ship_designs')->onDelete('cascade');
            $table->string('name');
            $table->integer('hull_points');
            $table->integer('max_hull_points');
            $table->integer('shield_points')->default(0);
            $table->integer('max_shield_points')->default(0);
            $table->integer('experience')->default(0);
            $table->integer('damage_level')->default(0);
            $table->string('status')->default('operational');
            $table->timestamps();

            $table->index(['fleet_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ships');
    }
};
