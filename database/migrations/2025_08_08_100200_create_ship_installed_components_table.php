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
        Schema::create('ship_installed_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ship_id')->constrained('ships')->onDelete('cascade');
            $table->foreignId('ship_component_id')->constrained('ship_components')->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->string('status')->default('operational');
            $table->timestamps();

            $table->unique(['ship_id', 'ship_component_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ship_installed_components');
    }
};
