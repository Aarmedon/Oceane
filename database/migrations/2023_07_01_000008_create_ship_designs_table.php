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
        if (!Schema::hasTable('ship_designs')) {
            Schema::create('ship_designs', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                // Defer FK to commanders to avoid ordering issues on MariaDB
                $table->unsignedBigInteger('creator_id');
                $table->index('creator_id');
                $table->integer('size');
                $table->decimal('base_cost', 12, 2);
                $table->integer('construction_time');
                $table->integer('max_hull_points');
                $table->integer('max_shield_points');
                $table->integer('power_generation');
                $table->integer('cargo_capacity');
                $table->decimal('maintenance_cost', 8, 2);
                $table->decimal('royalties_rate', 5, 2)->default(0);
                $table->boolean('is_public')->default(false);
                $table->integer('combat_power')->default(0);
                $table->string('image_path')->nullable();
                $table->timestamps();
            });
        }
        
        // Table pivot pour les composants d'un design
        if (!Schema::hasTable('ship_design_components')) {
            Schema::create('ship_design_components', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ship_design_id')->constrained()->onDelete('cascade');
                $table->foreignId('ship_component_id')->constrained()->onDelete('cascade');
                $table->integer('quantity')->default(1);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ship_design_components');
        Schema::dropIfExists('ship_designs');
    }
};
