<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sector extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'galaxy_id',
        'sector_number',
        'name',
        'position_x_start',
        'position_y_start',
        'position_x_end',
        'position_y_end',
        'image_path'
    ];
    
    /**
     * Get the galaxy this sector belongs to
     */
    public function galaxy()
    {
        return $this->belongsTo(Galaxy::class);
    }
    
    /**
     * Get the star systems in this sector
     */
    public function starSystems()
    {
        return $this->hasMany(StarSystem::class);
    }
}
