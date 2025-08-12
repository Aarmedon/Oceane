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
        Schema::create('planets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('star_system_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->integer('position_in_system');
            $table->integer('size');
            $table->integer('type');
            $table->integer('mineral_resources');
            $table->integer('radiation_level');
            $table->integer('temperature');
            $table->integer('gravity');
            $table->integer('atmosphere_type');
            $table->string('image_path')->nullable();
            $table->boolean('is_colonized')->default(false);
            // Commander reference added later to avoid FK order issues on MariaDB
            $table->unsignedBigInteger('commander_id')->nullable();
            $table->index('commander_id');
            $table->timestamps();
            
            $table->unique(['star_system_id', 'position_in_system']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planets');
    }
};
