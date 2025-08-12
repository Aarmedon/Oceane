<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fleet extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'commander_id',
        'name',
        'current_system_id',
        'destination_system_id',
        'position_x',
        'position_y',
        'galaxy_id',
        'status',
        'arrival_turn',
        'directive_id',
        'hero_id',
        'morale',
        'experience',
        'maintenance_cost',
        // scheduled deletion (MJ)
        'scheduled_for_deletion',
        'scheduled_deletion_turn',
    ];
    
    /**
     * Attribute casts
     */
    protected $casts = [
        'scheduled_for_deletion' => 'boolean',
    ];
    
    // Fleet status constants
    const STATUS_DOCKED = 'docked';
    const STATUS_MOVING = 'moving';
    const STATUS_COMBAT = 'combat';
    const STATUS_WAITING = 'waiting';
    
    /**
     * Get the commander who owns this fleet
     */
    public function commander()
    {
        return $this->belongsTo(Commander::class);
    }
    
    /**
     * Get the current star system where this fleet is located
     */
    public function currentSystem()
    {
        return $this->belongsTo(StarSystem::class, 'current_system_id')->withDefault();
    }
    
    /**
     * Get the destination star system where this fleet is headed
     */
    public function destinationSystem()
    {
        return $this->belongsTo(StarSystem::class, 'destination_system_id')->withDefault();
    }
    
    /**
     * Get the galaxy this fleet is in
     */
    public function galaxy()
    {
        return $this->belongsTo(Galaxy::class);
    }
    
    /**
     * Get the hero/leader assigned to this fleet
     */
    public function hero()
    {
        return $this->belongsTo(Hero::class)->withDefault();
    }
    
    /**
     * Get the ships in this fleet
     */
    public function ships()
    {
        return $this->hasMany(Ship::class);
    }

    /**
     * Get the ship stacks in this fleet (hybrid model)
     */
    public function shipStacks()
    {
        return $this->hasMany(FleetShipStack::class);
    }
    
    /**
     * Get the directive for this fleet
     */
    public function directive()
    {
        return $this->belongsTo(Directive::class)->withDefault();
    }
    
    /**
     * Get the cargo items in this fleet
     */
    public function cargo()
    {
        return $this->hasMany(FleetCargo::class);
    }

    /**
     * Ensure a directive is always set on creation.
     */
    protected static function booted()
    {
        static::creating(function (Fleet $fleet) {
            if (is_null($fleet->directive_id)) {
                $fleet->directive_id = config('oceane.directives.patrol', 0);
            }
        });
    }
}
