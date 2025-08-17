<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('natural_goods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planet_id')->constrained('planets')->onDelete('cascade');
            $table->foreignId('good_id')->constrained('goods')->onDelete('cascade');
            $table->integer('production_per_turn')->default(0);
            $table->timestamps();

            $table->unique(['planet_id', 'good_id']);
            $table->index(['good_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('natural_goods');
    }
};
