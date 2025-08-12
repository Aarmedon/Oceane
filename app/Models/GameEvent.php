<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameEvent extends Model
{
    use HasFactory;

    /**
     * Les attributs qui sont assignables en masse.
     *
     * Note: ce modèle reflète le schéma actuel de la migration
     * database/migrations/2023_07_01_000010_create_orders_and_reports_tables.php
     */
    protected $fillable = [
        'turn_number',
        'event_type',
        'event_data',
        'involved_commanders',
        'is_public',
    ];

    /**
     * Les attributs qui doivent être castés.
     */
    protected $casts = [
        'event_data' => 'array',
        'involved_commanders' => 'array',
        'is_public' => 'boolean',
        'turn_number' => 'integer',
    ];

    // Relations spécifiques (ex: participants) pourront être ajoutées si besoin

    // Des helpers d'affichage pourront être réintroduits plus tard côté UI si nécessaire
}
