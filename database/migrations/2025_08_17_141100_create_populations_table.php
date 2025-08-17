<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('populations')) {
            Schema::create('populations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('planet_id')->constrained()->onDelete('cascade');
                $table->integer('population')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('populations');
    }
};
