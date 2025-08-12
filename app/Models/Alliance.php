<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alliance extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'description',
        'type',
        'is_secret',
        'founder_id',
        'funds',
        'logo_path',
        'diplomacy_status'
    ];
    
    // Alliance types
    const TYPE_DEMOCRATIC = 0;
    const TYPE_AUTOCRATIC = 1;
    const TYPE_ANARCHIC = 2;
    
    /**
     * Get the founder of this alliance
     */
    public function founder()
    {
        return $this->belongsTo(Commander::class, 'founder_id');
    }
    
    /**
     * Get the members of this alliance
     */
    public function members()
    {
        return $this->belongsToMany(Commander::class, 'alliance_members')
            ->withPivot(['role', 'joined_at'])
            ->withTimestamps();
    }
    
    /**
     * Get the alliance pacts this alliance has with other alliances
     */
    public function pacts()
    {
        return $this->hasMany(AlliancePact::class, 'alliance_id');
    }
    
    /**
     * Get the diplomatic relations this alliance has with other alliances
     */
    public function diplomaticRelations()
    {
        return $this->hasMany(DiplomaticRelation::class, 'alliance1_id');
    }
    
    /**
     * Get the alliance announcements
     */
    public function announcements()
    {
        return $this->hasMany(AllianceAnnouncement::class)->orderBy('created_at', 'desc');
    }
    
    /**
     * Get the membership fee based on alliance type
     */
    public function getMembershipFeeAttribute()
    {
        if ($this->type == self::TYPE_DEMOCRATIC) {
            return 500;
        } else if ($this->type == self::TYPE_AUTOCRATIC) {
            return 1000;
        } else {
            return 250; // Anarchic alliances have lower membership fees
        }
    }
    
    /**
     * Calculate the creation cost based on alliance type and secrecy
     */
    public static function getCreationCost($type, $isSecret)
    {
        $baseCost = 1000;
        
        if ($isSecret) {
            $baseCost = 2000;
        }
        
        if ($type == self::TYPE_AUTOCRATIC) {
            $baseCost += 500;
        } else if ($type == self::TYPE_ANARCHIC) {
            $baseCost -= 250;
        }
        
        return $baseCost;
    }
}
