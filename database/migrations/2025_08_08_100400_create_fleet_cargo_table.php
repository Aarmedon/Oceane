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
        Schema::create('fleet_cargo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fleet_id')->constrained('fleets')->onDelete('cascade');
            $table->string('resource_type'); // e.g., minerals, fuel, goods
            $table->string('resource_subtype')->nullable(); // optional granularity
            $table->bigInteger('quantity')->default(0);
            $table->timestamps();

            $table->index(['fleet_id']);
            $table->index(['resource_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fleet_cargo');
    }
};
