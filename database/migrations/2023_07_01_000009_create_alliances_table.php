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
        Schema::create('alliances', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->integer('type'); // 0=démocratique, 1=autocratique, 2=anarchique
            $table->boolean('is_secret')->default(false);
            // Defer FK to commanders to avoid ordering issues on MariaDB
            $table->unsignedBigInteger('founder_id');
            $table->index('founder_id');
            $table->decimal('funds', 12, 2)->default(0);
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });
        
        Schema::create('alliance_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alliance_id')->constrained()->onDelete('cascade');
            // Defer FK to commanders, keep cascade semantics for later migration
            $table->unsignedBigInteger('commander_id');
            $table->index('commander_id');
            $table->string('role')->default('member'); // leader, officer, diplomat, member
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();
            
            $table->unique(['alliance_id', 'commander_id']);
        });
        
        Schema::create('alliance_pacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alliance1_id')->constrained('alliances');
            $table->foreignId('alliance2_id')->constrained('alliances');
            $table->string('type'); // non-aggression, trade, military, full
            $table->text('terms')->nullable();
            $table->timestamp('signed_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            $table->unique(['alliance1_id', 'alliance2_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alliance_pacts');
        Schema::dropIfExists('alliance_members');
        Schema::dropIfExists('alliances');
    }
};
