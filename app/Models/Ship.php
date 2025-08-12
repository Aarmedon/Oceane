<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ship extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'fleet_id',
        'ship_design_id',
        'name',
        'hull_points',
        'max_hull_points',
        'shield_points',
        'max_shield_points',
        'experience',
        'damage_level',
        'status'
    ];
    
    // Ship status constants
    const STATUS_OPERATIONAL = 'operational';
    const STATUS_DAMAGED = 'damaged';
    const STATUS_CRITICAL = 'critical';
    const STATUS_DESTROYED = 'destroyed';
    
    /**
     * Get the fleet this ship belongs to
     */
    public function fleet()
    {
        return $this->belongsTo(Fleet::class);
    }
    
    /**
     * Get the ship design/blueprint for this ship
     */
    public function shipDesign()
    {
        return $this->belongsTo(ShipDesign::class);
    }
    
    /**
     * Get the ship components installed on this ship
     */
    public function components()
    {
        return $this->belongsToMany(ShipComponent::class, 'ship_installed_components')
            ->withPivot(['quantity', 'status'])
            ->withTimestamps();
    }
    
    /**
     * Calculate the ship's current combat power
     */
    public function getCombatPowerAttribute()
    {
        // Combat power is affected by hull integrity, components and experience
        $hullIntegrity = $this->hull_points / $this->max_hull_points;
        $baseShipPower = $this->shipDesign->combat_power;
        $experienceBonus = 1 + ($this->experience / 100);
        
        return round($baseShipPower * $hullIntegrity * $experienceBonus);
    }
    
    /**
     * Calculate repair costs for this ship
     */
    public function getRepairCostAttribute()
    {
        if ($this->hull_points >= $this->max_hull_points) {
            return 0;
        }
        
        $damagePercentage = 1 - ($this->hull_points / $this->max_hull_points);
        return round($this->shipDesign->base_cost * $damagePercentage * 0.5);
    }
}
