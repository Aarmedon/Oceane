<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'subtype',
        'cost',
        'size',
        'power_requirement',
        'technology_id',
        'required_technology_level',
        'special_characteristics',
        'weapon_speed',
        'shield_damage',
        'hull_damage',
        'ground_damage',
        'weapon_range',
        'reliability',
        'image_path',
    ];

    protected $casts = [
        'special_characteristics' => 'array',
        'cost' => 'decimal:2',
    ];

    public function technology()
    {
        return $this->belongsTo(Technology::class);
    }
}
