<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameEnrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'commander_id',
        'type',
        'status',
        'payload',
        'processed_by',
        'processed_at',
        'processed_turn',
        'note',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
        'processed_turn' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function commander(): BelongsTo
    {
        return $this->belongsTo(Commander::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
