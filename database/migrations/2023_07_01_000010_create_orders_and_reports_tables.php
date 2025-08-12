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
        // Table des ordres donnés par les joueurs
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            // Defer FK to commanders to avoid ordering issues on MariaDB
            $table->unsignedBigInteger('commander_id');
            $table->index('commander_id');
            $table->string('order_type'); // move, colonize, research, build, etc.
            $table->json('parameters'); // Paramètres spécifiques à l'ordre
            $table->integer('turn_submitted');
            $table->integer('turn_execution')->nullable(); // Pour les ordres programmés
            $table->boolean('is_processed')->default(false);
            $table->text('processing_error')->nullable();
            $table->timestamps();
        });
        
        // Table des rapports de tour pour les commandants
        Schema::create('turn_reports', function (Blueprint $table) {
            $table->id();
            // Defer FK to commanders to avoid ordering issues on MariaDB
            $table->unsignedBigInteger('commander_id');
            $table->index('commander_id');
            $table->integer('turn_number');
            $table->text('summary');
            $table->json('financial_report'); // Budget, revenus, dépenses
            $table->string('report_file_path')->nullable(); // Chemin vers le fichier de rapport complet
            $table->boolean('is_read')->default(false);
            $table->timestamps();
            
            $table->unique(['commander_id', 'turn_number']);
        });
        
        // Table des événements de jeu (combats, découvertes, etc.)
        Schema::create('game_events', function (Blueprint $table) {
            $table->id();
            $table->integer('turn_number');
            $table->string('event_type');
            $table->json('event_data');
            $table->json('involved_commanders'); // IDs des commandants impliqués
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });
        
        // Table des rapports de combat détaillés
        Schema::create('combat_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_event_id')->constrained()->onDelete('cascade');
            $table->json('participants'); // Détails des commandants et flottes impliqués
            $table->json('combat_rounds'); // Détails de chaque round de combat
            $table->json('results'); // Résultats (pertes, gains, etc.)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('combat_reports');
        Schema::dropIfExists('game_events');
        Schema::dropIfExists('turn_reports');
        Schema::dropIfExists('orders');
    }
};
