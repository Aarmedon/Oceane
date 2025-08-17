<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Population extends Model
{
    use HasFactory;

    protected $fillable = [
        'planet_id',
        'population',
    ];

    public function planet()
    {
        return $this->belongsTo(Planet::class);
    }
}
