<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FleetShipStack extends Model
{
    use HasFactory;

    protected $fillable = [
        'fleet_id',
        'ship_design_id',
        'count_operational',
        'count_damaged',
        'count_destroyed',
        // 'damage_buckets', // optional JSON
    ];

    public function fleet()
    {
        return $this->belongsTo(Fleet::class);
    }

    public function shipDesign()
    {
        return $this->belongsTo(ShipDesign::class);
    }
}
