<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameState extends Model
{
    protected $table = 'game_state';

    protected $fillable = [
        'current_turn',
        'last_resolved_at',
        'next_turn_at',
    ];

    /**
     * Attribute casting
     */
    protected $casts = [
        'last_resolved_at' => 'datetime',
        'next_turn_at' => 'datetime',
        'current_turn' => 'integer',
    ];
}
