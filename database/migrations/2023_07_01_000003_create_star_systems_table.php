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
        if (!Schema::hasTable('star_systems')) {
            Schema::create('star_systems', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sector_id')->constrained()->onDelete('cascade');
                $table->string('name');
                $table->integer('position_x');
                $table->integer('position_y');
                $table->integer('star_type');
                $table->boolean('is_controlled')->default(false);
                // Commander reference will be added as a foreign key in a later migration to avoid
                // cross-table creation order issues on MariaDB.
                $table->unsignedBigInteger('commander_id')->nullable();
                $table->index('commander_id');
                $table->integer('tax_rate')->default(0);
                $table->integer('budget_technology')->default(0);
                $table->integer('budget_special_services')->default(0);
                $table->integer('budget_counter_espionage')->default(0);
                $table->integer('policy_id')->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('star_systems');
    }
};
