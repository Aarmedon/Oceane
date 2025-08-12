<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipDesign extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'creator_id',
        'size',
        'base_cost',
        'construction_time',
        'max_hull_points',
        'max_shield_points',
        'power_generation',
        'cargo_capacity',
        'maintenance_cost',
        'royalties_rate',
        'is_public',
        'is_stackable',
        'combat_power',
        'image_path',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_stackable' => 'boolean',
        'base_cost' => 'decimal:2',
        'maintenance_cost' => 'decimal:2',
        'royalties_rate' => 'decimal:2',
    ];

    public function creator()
    {
        return $this->belongsTo(Commander::class, 'creator_id');
    }

    public function components()
    {
        return $this->belongsToMany(ShipComponent::class, 'ship_design_components')
            ->withPivot(['quantity'])
            ->withTimestamps();
    }

    public function ships()
    {
        return $this->hasMany(Ship::class);
    }

    public function stacks()
    {
        return $this->hasMany(FleetShipStack::class);
    }
}
