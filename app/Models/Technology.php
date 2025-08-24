<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Technology extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'description',
        'category',
        'base_research_cost',
        'level_multiplier',
        'max_level',
        'prerequisite_technology_id',
        'prerequisite_level',
        'image_path',
        'type'
    ];
    
    // Technology categories
    const CATEGORY_PROPULSION = 'propulsion';
    const CATEGORY_WEAPONS = 'weapons';
    const CATEGORY_SHIELDS = 'shields';
    const CATEGORY_CONSTRUCTION = 'construction';
    const CATEGORY_BIOLOGY = 'biology';
    const CATEGORY_ECONOMY = 'economy';
    const CATEGORY_SPECIAL = 'special';
    const CATEGORY_SENSORS = 'sensors';
    
    // Technology types
    const TYPE_SIMPLE = 'simple';
    const TYPE_BUILDING = 'building';
    const TYPE_SHIP_COMPONENT = 'ship_component';
    
    /**
     * Get the prerequisite technology
     */
    public function prerequisiteTechnology()
    {
        return $this->belongsTo(Technology::class, 'prerequisite_technology_id')->withDefault();
    }
    
    /**
     * Get the dependent technologies (those that require this one)
     */
    public function dependentTechnologies()
    {
        return $this->hasMany(Technology::class, 'prerequisite_technology_id');
    }
    
    /**
     * Get the commanders who have researched this technology
     */
    public function commanders()
    {
        return $this->belongsToMany(Commander::class, 'commander_technologies')
            ->withPivot(['level', 'research_progress', 'completed_at'])
            ->withTimestamps();
    }
    
    /**
     * Get the ship components unlocked by this technology
     */
    public function shipComponents()
    {
        return $this->hasMany(ShipComponent::class);
    }
    
    /**
     * Get the buildings unlocked by this technology
     */
    public function buildings()
    {
        return $this->hasMany(BuildingType::class);
    }
    
    /**
     * Calculate research cost for a specific level
     */
    public function getResearchCostForLevel($level)
    {
        if ($level <= 0 || $level > $this->max_level) {
            return 0;
        }
        
        return $this->base_research_cost * pow($this->level_multiplier, $level - 1);
    }
}
