<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CombatReport extends Model
{
    use HasFactory;

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array
     */
    protected $fillable = [
        'commander_id',
        'turn_number',
        'location',
        'summary',
        'participants',
        'combat_rounds',
        'results',
        'is_read',
        'is_victory',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array
     */
    protected $casts = [
        'participants' => 'array',
        'combat_rounds' => 'array',
        'results' => 'array',
        'is_read' => 'boolean',
        'is_victory' => 'boolean',
        'turn_number' => 'integer',
    ];

    /**
     * Obtient le commandant associé à ce rapport.
     */
    public function commander()
    {
        return $this->belongsTo(Commander::class);
    }

    /**
     * Obtient un résumé court du rapport de combat.
     *
     * @return string
     */
    public function getShortSummary()
    {
        $outcome = $this->is_victory ? 'Victoire' : 'Défaite';
        return "Combat à {$this->location} - {$outcome}: " . substr($this->summary, 0, 80) . (strlen($this->summary) > 80 ? '...' : '');
    }

    /**
     * Obtient le nombre total de rounds de combat.
     *
     * @return int
     */
    public function getTotalRoundsAttribute()
    {
        return count($this->combat_rounds ?? []);
    }

    /**
     * Obtient le nombre de vaisseaux alliés impliqués dans le combat.
     *
     * @return int
     */
    public function getAlliedShipsCountAttribute()
    {
        $count = 0;
        foreach ($this->participants['allies'] ?? [] as $ally) {
            foreach ($ally['fleets'] ?? [] as $fleet) {
                $count += count($fleet['ships'] ?? []);
            }
        }
        return $count;
    }

    /**
     * Obtient le nombre de vaisseaux ennemis impliqués dans le combat.
     *
     * @return int
     */
    public function getEnemyShipsCountAttribute()
    {
        $count = 0;
        foreach ($this->participants['enemies'] ?? [] as $enemy) {
            foreach ($enemy['fleets'] ?? [] as $fleet) {
                $count += count($fleet['ships'] ?? []);
            }
        }
        return $count;
    }

    /**
     * Obtient le nombre de vaisseaux alliés perdus.
     *
     * @return int
     */
    public function getAlliedShipsLostAttribute()
    {
        return $this->results['allied_losses']['ships_destroyed'] ?? 0;
    }

    /**
     * Obtient le nombre de vaisseaux ennemis détruits.
     *
     * @return int
     */
    public function getEnemyShipsDestroyedAttribute()
    {
        return $this->results['enemy_losses']['ships_destroyed'] ?? 0;
    }

    /**
     * Calcule le ratio de pertes (pertes alliées / pertes ennemies).
     *
     * @return float|null
     */
    public function getLossRatioAttribute()
    {
        if ($this->enemy_ships_destroyed > 0) {
            return round($this->allied_ships_lost / $this->enemy_ships_destroyed, 2);
        }
        return null;
    }

    /**
     * Détermine si le combat était décisif (plus de 50% des vaisseaux d'un côté détruits).
     *
     * @return bool
     */
    public function wasDecisive()
    {
        $alliedLossPercentage = ($this->allied_ships_lost / $this->allied_ships_count) * 100;
        $enemyLossPercentage = ($this->enemy_ships_destroyed / $this->enemy_ships_count) * 100;
        
        return $alliedLossPercentage > 50 || $enemyLossPercentage > 50;
    }

    /**
     * Obtient la classe CSS pour le badge du rapport.
     *
     * @return string
     */
    public function getBadgeClass()
    {
        if (!$this->is_read) {
            return 'bg-warning text-dark';
        } elseif ($this->is_victory) {
            return 'bg-success';
        } else {
            return 'bg-danger';
        }
    }

    /**
     * Obtient l'icône FontAwesome pour le rapport.
     *
     * @return string
     */
    public function getReportIcon()
    {
        if (!$this->is_read) {
            return 'fa-exclamation-circle';
        } elseif ($this->is_victory) {
            return 'fa-trophy';
        } else {
            return 'fa-skull-crossbones';
        }
    }

    /**
     * Obtient le texte du résultat du combat.
     *
     * @return string
     */
    public function getOutcomeText()
    {
        if ($this->is_victory) {
            return 'Victoire';
        } else {
            return 'Défaite';
        }
    }

    /**
     * Obtient la classe CSS pour le texte du résultat.
     *
     * @return string
     */
    public function getOutcomeClass()
    {
        if ($this->is_victory) {
            return 'text-success';
        } else {
            return 'text-danger';
        }
    }
}
