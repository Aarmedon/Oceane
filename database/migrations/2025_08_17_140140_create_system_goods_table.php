<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_goods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('star_system_id')->constrained('star_systems')->onDelete('cascade');
            $table->foreignId('commander_id')->constrained('commanders')->onDelete('cascade');
            $table->foreignId('good_id')->constrained('goods')->onDelete('cascade');
            $table->unsignedBigInteger('stock')->default(0);
            $table->integer('production_per_turn')->default(0);
            $table->integer('price')->nullable(); // optional system-level price for this good
            $table->integer('last_updated_turn')->nullable();
            $table->timestamps();

            $table->unique(['star_system_id', 'commander_id', 'good_id']);
            $table->index(['commander_id']);
            $table->index(['good_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_goods');
    }
};
