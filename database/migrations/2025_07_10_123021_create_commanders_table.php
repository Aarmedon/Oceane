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
        if (!Schema::hasTable('commanders')) {
            Schema::create('commanders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                // Defer FK to races to avoid ordering issues on MariaDB
                $table->unsignedBigInteger('race_id');
                $table->index('race_id');
                $table->string('name');
                $table->integer('credits')->default(1000);
                $table->integer('reputation')->default(0);
                $table->foreignId('capital_system_id')->nullable()->constrained('star_systems');
                $table->text('description')->nullable();
                $table->string('avatar_path')->nullable();
                $table->integer('created_turn')->default(1);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commanders');
    }
};
