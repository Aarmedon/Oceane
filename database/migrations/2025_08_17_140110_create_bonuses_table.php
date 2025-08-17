<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bonuses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('domain'); // e.g., stability, population_growth, revenue, maintenance, construction_points, tech_revenue
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['domain']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bonuses');
    }
};
