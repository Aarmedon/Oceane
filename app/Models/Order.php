<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array
     */
    protected $fillable = [
        'commander_id',
        'order_type',
        'parameters',
        'turn_submitted',
        'turn_execution',
        'turn_processed',
        'is_processed',
        'processing_error',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array
     */
    protected $casts = [
        'parameters' => 'array',
        'is_processed' => 'boolean',
        'turn_submitted' => 'integer',
        'turn_execution' => 'integer',
        'turn_processed' => 'integer',
    ];

    /**
     * Obtient le commandant qui a émis cet ordre.
     */
    public function commander()
    {
        return $this->belongsTo(Commander::class);
    }

    /**
     * Obtient une description courte de l'ordre.
     *
     * @return string
     */
    public function getShortDescription()
    {
        switch ($this->order_type) {
            case 'move':
                return "Déplacement de la flotte {$this->parameters['fleet_name']} vers {$this->parameters['destination_system_name']}";
            
            case 'colonize':
                return "Colonisation de {$this->parameters['planet_name']} par {$this->parameters['ship_name']}";
            
            case 'research':
                return "Recherche de {$this->parameters['technology_name']} niveau {$this->parameters['target_level']}";
            
            case 'build':
                $type = $this->parameters['build_type'] === 'building' ? 'bâtiment' : 'vaisseau';
                return "Construction de {$type} {$this->parameters['name']} à {$this->parameters['location_name']}";
            
            case 'diplomatic':
                $typeLabels = [
                    'alliance_proposal' => 'Proposition d\'alliance',
                    'peace_proposal' => 'Proposition de paix',
                    'trade_proposal' => 'Proposition commerciale',
                    'war_declaration' => 'Déclaration de guerre'
                ];
                $type = $typeLabels[$this->parameters['diplomatic_type']] ?? $this->parameters['diplomatic_type'];
                return "{$type} envers {$this->parameters['target_commander_name']}";
            
            default:
                return "Ordre de type {$this->order_type}";
        }
    }

    /**
     * Obtient une description détaillée de l'ordre.
     *
     * @return string
     */
    public function getDetailedDescription()
    {
        switch ($this->order_type) {
            case 'move':
                return "Déplacement de la flotte {$this->parameters['fleet_name']} du système {$this->parameters['origin_system_name']} vers le système {$this->parameters['destination_system_name']}. Distance estimée: {$this->parameters['distance']} parsecs. Durée estimée: {$this->parameters['estimated_turns']} tour(s).";
            
            case 'colonize':
                return "Colonisation de la planète {$this->parameters['planet_name']} dans le système {$this->parameters['system_name']} par le vaisseau {$this->parameters['ship_name']}. Habitabilité: {$this->parameters['habitability']}%. Temps d'établissement: {$this->parameters['establishment_time']} tour(s).";
            
            case 'research':
                return "Recherche de la technologie {$this->parameters['technology_name']} du niveau {$this->parameters['current_level']} au niveau {$this->parameters['target_level']}. Points requis: {$this->parameters['required_points']}. Durée estimée: {$this->parameters['estimated_turns']} tour(s).";
            
            case 'build':
                if ($this->parameters['build_type'] === 'building') {
                    return "Construction du bâtiment {$this->parameters['name']} (niveau {$this->parameters['level']}) sur {$this->parameters['location_name']}. Durée estimée: {$this->parameters['estimated_turns']} tour(s).";
                } else {
                    return "Construction du vaisseau {$this->parameters['name']} (classe {$this->parameters['ship_class']}) à {$this->parameters['location_name']}. Durée estimée: {$this->parameters['estimated_turns']} tour(s).";
                }
            
            case 'diplomatic':
                $typeLabels = [
                    'alliance_proposal' => 'Proposition d\'alliance',
                    'peace_proposal' => 'Proposition de paix',
                    'trade_proposal' => 'Proposition commerciale',
                    'war_declaration' => 'Déclaration de guerre'
                ];
                $type = $typeLabels[$this->parameters['diplomatic_type']] ?? $this->parameters['diplomatic_type'];
                return "{$type} envers {$this->parameters['target_commander_name']} pour une durée de {$this->parameters['duration']} tours.";
            
            default:
                return "Ordre de type {$this->order_type}";
        }
    }

    /**
     * Obtient le statut de l'ordre sous forme de texte.
     *
     * @return string
     */
    public function getStatusText()
    {
        if ($this->processing_error) {
            return 'Échoué';
        } elseif ($this->is_processed) {
            return 'Traité';
        } else {
            return 'En attente';
        }
    }

    /**
     * Obtient la classe CSS pour le badge de statut.
     *
     * @return string
     */
    public function getStatusBadgeClass()
    {
        if ($this->processing_error) {
            return 'bg-danger';
        } elseif ($this->is_processed) {
            return 'bg-success';
        } else {
            return 'bg-primary';
        }
    }

    /**
     * Obtient l'icône FontAwesome pour le type d'ordre.
     *
     * @return string
     */
    public function getOrderTypeIcon()
    {
        switch ($this->order_type) {
            case 'move':
                return 'fa-route';
            case 'colonize':
                return 'fa-flag';
            case 'research':
                return 'fa-flask';
            case 'build':
                return 'fa-hammer';
            case 'diplomatic':
                return 'fa-handshake';
            default:
                return 'fa-clipboard-list';
        }
    }

    /**
     * Obtient la classe CSS pour la couleur de fond du type d'ordre.
     *
     * @return string
     */
    public function getOrderTypeColorClass()
    {
        switch ($this->order_type) {
            case 'move':
                return 'bg-info';
            case 'colonize':
                return 'bg-success';
            case 'research':
                return 'bg-warning';
            case 'build':
                return 'bg-primary';
            case 'diplomatic':
                return 'bg-secondary';
            default:
                return 'bg-dark';
        }
    }
}
