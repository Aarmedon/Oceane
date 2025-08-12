<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Hero extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'commander_id',
        'name',
        'type',
        'combat_skill',
        'diplomacy_skill',
        'management_skill',
        'experience',
        'level',
        'assignment',
        'maintenance_cost'
    ];
    
    /**
     * Get the commander this hero belongs to
     */
    public function commander(): BelongsTo
    {
        return $this->belongsTo(Commander::class);
    }
}
