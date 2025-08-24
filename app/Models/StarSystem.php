<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StarSystem extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'sector_id',
        'name',
        'position_x',
        'position_y',
        'star_type',
        'map_icon_path',
        'is_controlled',
        'commander_id',
        'tax_rate',
        'budget_technology',
        'budget_special_services',
        'budget_counter_espionage',
        'policy_id'
    ];
    
    /**
     * Get the sector this system belongs to
     */
    public function sector()
    {
        return $this->belongsTo(Sector::class);
    }
    
    /**
     * Get the planets in this system
     */
    public function planets()
    {
        return $this->hasMany(Planet::class);
    }
    
    /**
     * Get the commander who controls this system
     */
    public function commander()
    {
        return $this->belongsTo(Commander::class)->withDefault();
    }
    
    /**
     * Get the fleets in this system
     */
    public function fleets()
    {
        return $this->hasMany(Fleet::class, 'current_system_id');
    }
    
    /**
     * Get the per-commander goods state for this system
     */
    public function systemGoods()
    {
        return $this->hasMany(SystemGood::class);
    }
}
