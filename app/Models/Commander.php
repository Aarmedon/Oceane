<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Commander extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'name',
        'race_id',
        'credits',
        'reputation',
        'capital_system_id',
        'description',
        'avatar_path',
        'created_turn'
    ];
    
    /**
     * Get the user that owns this commander
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get the race of this commander
     */
    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class);
    }
    
    /**
     * Get the star systems controlled by this commander
     */
    public function starSystems(): HasMany
    {
        return $this->hasMany(StarSystem::class);
    }
    
    /**
     * Get the planets controlled by this commander
     */
    public function planets(): HasMany
    {
        return $this->hasMany(Planet::class);
    }
    
    /**
     * Get the fleets owned by this commander
     */
    public function fleets(): HasMany
    {
        return $this->hasMany(Fleet::class);
    }
    
    /**
     * Get the alliances this commander is a member of
     */
    public function alliances()
    {
        return $this->belongsToMany(Alliance::class, 'alliance_members')
            ->withPivot(['role', 'joined_at'])
            ->withTimestamps();
    }
    
    /**
     * Get the technologies this commander has researched
     */
    public function technologies()
    {
        return $this->belongsToMany(Technology::class, 'commander_technologies')
            ->withPivot(['level', 'research_progress', 'research_total', 'is_researching', 'research_completion_turn', 'completed_at'])
            ->withTimestamps();
    }
    
    /**
     * Get the heroes/leaders under this commander's control
     */
    public function heroes(): HasMany
    {
        return $this->hasMany(Hero::class);
    }
    
    /**
     * Get the orders submitted by this commander
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
    
    /**
     * Get the capital system of this commander
     */
    public function capitalSystem(): BelongsTo
    {
        return $this->belongsTo(StarSystem::class, 'capital_system_id')->withDefault();
    }
}
