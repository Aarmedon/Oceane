<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('map_sprites', function (Blueprint $table) {
            $table->id();
            $table->string('scope'); // 'system' | 'fleet'
            $table->string('owner_category'); // 'self' | 'ally' | 'enemy' | 'neutral' | 'unknown' | 'any'
            $table->integer('size_min')->nullable(); // fleets only
            $table->integer('size_max')->nullable(); // fleets only
            $table->integer('priority')->default(100);
            $table->string('image_path');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['scope', 'owner_category']);
            $table->index(['scope', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('map_sprites');
    }
};
