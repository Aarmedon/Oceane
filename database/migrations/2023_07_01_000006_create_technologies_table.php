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
        Schema::create('technologies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->string('category');
            $table->decimal('base_research_cost', 10, 2);
            $table->decimal('level_multiplier', 5, 2)->default(1.5);
            $table->integer('max_level')->default(10);
            $table->foreignId('prerequisite_technology_id')->nullable()->constrained('technologies')->onDelete('set null');
            $table->integer('prerequisite_level')->default(1);
            $table->string('image_path')->nullable();
            $table->string('type')->default('simple');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technologies');
    }
};
