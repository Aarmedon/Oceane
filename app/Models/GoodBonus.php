<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodBonus extends Model
{
    use HasFactory;

    protected $table = 'goods_bonus';

    protected $fillable = [
        'good_id',
        'bonus_id',
        'value',
    ];

    public function good()
    {
        return $this->belongsTo(Good::class);
    }

    public function bonus()
    {
        return $this->belongsTo(Bonus::class);
    }
}
