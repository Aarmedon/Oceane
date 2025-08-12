<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TurnReport extends Model
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
        'summary',
        'financial_report',
        'planetary_activities',
        'fleet_activities',
        'research_activities',
        'diplomatic_activities',
        'is_read',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array
     */
    protected $casts = [
        'financial_report' => 'array',
        'planetary_activities' => 'array',
        'fleet_activities' => 'array',
        'research_activities' => 'array',
        'diplomatic_activities' => 'array',
        'is_read' => 'boolean',
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
     * Obtient un résumé court du rapport.
     *
     * @return string
     */
    public function getShortSummary()
    {
        return "Rapport du tour {$this->turn_number}: " . substr($this->summary, 0, 100) . (strlen($this->summary) > 100 ? '...' : '');
    }

    /**
     * Calcule le bilan financier total.
     *
     * @return int
     */
    public function getTotalBalanceAttribute()
    {
        $income = array_sum($this->financial_report['income'] ?? []);
        $expenses = array_sum($this->financial_report['expenses'] ?? []);
        
        return $income - $expenses;
    }

    /**
     * Vérifie si le bilan est positif.
     *
     * @return bool
     */
    public function isBalancePositive()
    {
        return $this->total_balance >= 0;
    }

    /**
     * Obtient le nombre de planètes avec activité.
     *
     * @return int
     */
    public function getActivePlanetsCount()
    {
        return count($this->planetary_activities ?? []);
    }

    /**
     * Obtient le nombre de flottes avec activité.
     *
     * @return int
     */
    public function getActiveFleetsCount()
    {
        return count($this->fleet_activities ?? []);
    }

    /**
     * Obtient le nombre de recherches actives.
     *
     * @return int
     */
    public function getActiveResearchCount()
    {
        return count($this->research_activities ?? []);
    }

    /**
     * Obtient le nombre d'activités diplomatiques.
     *
     * @return int
     */
    public function getDiplomaticActivitiesCount()
    {
        return count($this->diplomatic_activities ?? []);
    }

    /**
     * Vérifie si le rapport contient des alertes importantes.
     *
     * @return bool
     */
    public function hasImportantAlerts()
    {
        // Vérifier le bilan financier négatif
        if ($this->total_balance < 0) {
            return true;
        }
        
        // Vérifier les alertes dans les activités planétaires
        foreach ($this->planetary_activities ?? [] as $activity) {
            if (isset($activity['alert']) && $activity['alert']) {
                return true;
            }
        }
        
        // Vérifier les alertes dans les activités de flotte
        foreach ($this->fleet_activities ?? [] as $activity) {
            if (isset($activity['alert']) && $activity['alert']) {
                return true;
            }
        }
        
        return false;
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
        } elseif ($this->hasImportantAlerts()) {
            return 'bg-danger';
        } else {
            return 'bg-info';
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
        } elseif ($this->hasImportantAlerts()) {
            return 'fa-exclamation-triangle';
        } else {
            return 'fa-clipboard-check';
        }
    }
}
