<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FleetCargo extends Model
{
    use HasFactory;

    protected $table = 'fleet_cargo';

    protected $fillable = [
        'fleet_id',
        'resource_type',
        'quantity',
    ];

    public function fleet()
    {
        return $this->belongsTo(Fleet::class);
    }
}
