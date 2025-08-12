<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fleet_ship_stacks')) {
            Schema::create('fleet_ship_stacks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('fleet_id')->constrained('fleets')->onDelete('cascade');
                $table->foreignId('ship_design_id')->constrained('ship_designs')->onDelete('cascade');
                $table->unsignedInteger('count_operational')->default(0);
                $table->unsignedInteger('count_damaged')->default(0);
                $table->unsignedInteger('count_destroyed')->default(0);
                // Option: $table->json('damage_buckets')->nullable();
                $table->timestamps();

                $table->index(['fleet_id']);
                $table->index(['fleet_id', 'ship_design_id']);
                $table->unique(['fleet_id', 'ship_design_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_ship_stacks');
    }
};
