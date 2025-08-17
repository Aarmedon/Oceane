<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_bonus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('good_id')->constrained('goods')->onDelete('cascade');
            $table->foreignId('bonus_id')->constrained('bonuses')->onDelete('cascade');
            $table->integer('value'); // bonus value associated to the good for this bonus type
            $table->timestamps();

            $table->unique(['good_id', 'bonus_id']);
            $table->index(['bonus_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_bonus');
    }
};
