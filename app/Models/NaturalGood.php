<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NaturalGood extends Model
{
    use HasFactory;

    protected $fillable = [
        'planet_id',
        'good_id',
        'production_per_turn',
    ];

    public function planet()
    {
        return $this->belongsTo(Planet::class);
    }

    public function good()
    {
        return $this->belongsTo(Good::class);
    }
}
