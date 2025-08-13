<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameEventRead extends Model
{
    use HasFactory;

    protected $table = 'game_event_reads';

    protected $fillable = [
        'game_event_id',
        'commander_id',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(GameEvent::class, 'game_event_id');
    }

    public function commander()
    {
        return $this->belongsTo(Commander::class, 'commander_id');
    }
}
