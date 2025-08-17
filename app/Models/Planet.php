<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Planet extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'star_system_id',
        'name',
        'position_in_system',
        'size',
        'type',
        'mineral_resources',
        'radiation_level',
        'temperature',
        'gravity',
        'atmosphere_type',
        'image_path',
        'is_colonized',
        'commander_id'
    ];
    
    /**
     * Get the star system this planet belongs to
     */
    public function starSystem()
    {
        return $this->belongsTo(StarSystem::class);
    }
    
    /**
     * Get the commander who controls this planet
     */
    public function commander()
    {
        return $this->belongsTo(Commander::class)->withDefault();
    }
    
    /**
     * Get the buildings on this planet
     */
    public function buildings()
    {
        return $this->hasMany(Building::class);
    }
    
    /**
     * Get the population of this planet
     */
    public function population()
    {
        return $this->hasOne(Population::class);
    }
    
    /**
     * Get the productions of this planet
     */
    public function productions()
    {
        return $this->hasMany(PlanetaryProduction::class);
    }
    
    /**
     * Get the natural goods produced by this planet
     */
    public function naturalGoods()
    {
        return $this->hasMany(NaturalGood::class);
    }
}
