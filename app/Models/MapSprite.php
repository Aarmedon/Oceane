<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MapSprite extends Model
{
    use HasFactory;

    protected $fillable = [
        'scope',            // 'system' | 'fleet'
        'owner_category',   // 'self' | 'ally' | 'enemy' | 'neutral' | 'unknown' | 'any'
        'size_min',         // nullable, fleets only
        'size_max',         // nullable, fleets only
        'priority',
        'image_path',
        'is_active',
    ];
}
