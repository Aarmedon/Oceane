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
        Schema::create('sectors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('galaxy_id')->constrained()->onDelete('cascade');
            $table->integer('sector_number');
            $table->string('name');
            $table->integer('position_x_start');
            $table->integer('position_y_start');
            $table->integer('position_x_end');
            $table->integer('position_y_end');
            $table->string('image_path')->nullable();
            $table->timestamps();
            
            $table->unique(['galaxy_id', 'sector_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sectors');
    }
};
