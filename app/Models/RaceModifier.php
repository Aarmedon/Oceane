<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RaceModifier extends Model
{
    use HasFactory;

    protected $fillable = [
        'race_id',
        'key',
        'value',
    ];

    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class);
    }
}
