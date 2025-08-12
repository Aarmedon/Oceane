<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Galaxy extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'size_x',
        'size_y',
        'image_path'
    ];
    
    /**
     * Get the sectors in this galaxy
     */
    public function sectors()
    {
        return $this->hasMany(Sector::class);
    }
    
    /**
     * Get the galactic portals in this galaxy
     */
    public function galacticPortals()
    {
        return $this->hasMany(GalacticPortal::class, 'origin_galaxy_id');
    }
}
