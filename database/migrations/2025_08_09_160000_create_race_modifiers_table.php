<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('race_modifiers')) {
            Schema::create('race_modifiers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('race_id')->constrained('races')->cascadeOnDelete();
                $table->string('key', 100);
                $table->decimal('value', 8, 3); // e.g., -0.25, 1.10, 5.000
                $table->timestamps();

                $table->unique(['race_id', 'key']);
                $table->index(['key']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('race_modifiers');
    }
};
