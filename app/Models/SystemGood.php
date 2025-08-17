<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemGood extends Model
{
    use HasFactory;

    protected $fillable = [
        'star_system_id',
        'commander_id',
        'good_id',
        'stock',
        'production_per_turn',
        'price',
        'last_updated_turn',
    ];

    public function starSystem()
    {
        return $this->belongsTo(StarSystem::class);
    }

    public function commander()
    {
        return $this->belongsTo(Commander::class);
    }

    public function good()
    {
        return $this->belongsTo(Good::class);
    }
}
