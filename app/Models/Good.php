<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Good extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'category',
        'unit',
        'base_price',
        'is_tradeable',
        'description',
    ];

    public function naturalGoods(): HasMany
    {
        return $this->hasMany(NaturalGood::class);
    }

    public function systemGoods(): HasMany
    {
        return $this->hasMany(SystemGood::class);
    }

    public function bonuses(): BelongsToMany
    {
        return $this->belongsToMany(Bonus::class, 'goods_bonus')
            ->withPivot(['value'])
            ->withTimestamps();
    }
}
