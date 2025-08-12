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
        Schema::create('heroes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commander_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('type', ['hero', 'governor'])->default('hero');
            $table->integer('combat_skill')->default(1);
            $table->integer('diplomacy_skill')->default(1);
            $table->integer('management_skill')->default(1);
            $table->integer('experience')->default(0);
            $table->integer('level')->default(1);
            $table->string('assignment')->nullable();
            $table->integer('maintenance_cost')->default(50);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('heroes');
    }
};
