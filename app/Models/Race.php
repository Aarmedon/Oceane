<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Race extends Model
{
    use HasFactory;
    
    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'bonuses',
        'penalties',
        'image_path',
        'is_playable',
    ];
    
    /**
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'bonuses' => 'array',
        'penalties' => 'array',
        'is_playable' => 'boolean',
    ];
    
    /**
     * Obtenir les commandants de cette race.
     */
    public function commanders(): HasMany
    {
        return $this->hasMany(Commander::class);
    }

    /**
     * Modificateurs (bonus/malus) associés à la race.
     */
    public function modifiers(): HasMany
    {
        return $this->hasMany(RaceModifier::class);
    }
}
