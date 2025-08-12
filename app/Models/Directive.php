<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Directive extends Model
{
    use HasFactory;

    protected $table = 'directives';

    public $incrementing = false; // IDs are predefined (0..9)
    protected $keyType = 'int';

    protected $fillable = [
        'id',
        'code',
        'name',
        'description',
    ];

    public function fleets()
    {
        return $this->hasMany(Fleet::class);
    }
}
