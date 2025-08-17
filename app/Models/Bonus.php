<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Bonus extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'domain',
        'description',
    ];

    public function goods(): BelongsToMany
    {
        return $this->belongsToMany(Good::class, 'goods_bonus')
            ->withPivot(['value'])
            ->withTimestamps();
    }
}
