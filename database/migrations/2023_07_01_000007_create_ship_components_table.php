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
        Schema::create('ship_components', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->string('type'); // arme, moteur, autre
            $table->string('subtype')->nullable(); // Pour les armes: combat spatial, mixte, combat planétaire
            $table->decimal('cost', 10, 2);
            $table->integer('size');
            $table->integer('power_requirement');
            $table->foreignId('technology_id')->constrained();
            $table->integer('required_technology_level')->default(1);
            
            // Caractéristiques spéciales
            $table->json('special_characteristics')->nullable();
            
            // Caractéristiques des armes
            $table->integer('weapon_speed')->nullable();
            $table->integer('shield_damage')->nullable();
            $table->integer('hull_damage')->nullable();
            $table->integer('ground_damage')->nullable();
            $table->integer('weapon_range')->nullable();
            $table->integer('reliability')->nullable();
            
            $table->string('image_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ship_components');
    }
};
