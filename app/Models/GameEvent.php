<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class GameEvent extends Model
{
    use HasFactory;

    /**
     * Les attributs qui sont assignables en masse.
     *
     * Note: ce modèle reflète le schéma actuel de la migration
     * database/migrations/2023_07_01_000010_create_orders_and_reports_tables.php
     */
    protected $fillable = [
        'turn_number',
        'event_type',
        'event_data',
        'involved_commanders',
        'is_public',
    ];

    /**
     * Les attributs qui doivent être castés.
     */
    protected $casts = [
        'event_data' => 'array',
        'involved_commanders' => 'array',
        'is_public' => 'boolean',
        'turn_number' => 'integer',
    ];

    /**
     * Appended attributes for views (computed from event_data or reads table)
     */
    protected $appends = [
        'title',
        'description',
        'importance',
        'type',
        'read',
        'planet_id',
        'fleet_id',
        'technology_id',
        'related_commander_id',
        'consequences',
        'possible_actions',
        'additional_data',
    ];

    /**
     * Relationship: read receipts for this event
     */
    public function reads()
    {
        return $this->hasMany(GameEventRead::class);
    }

    /**
     * Derived attribute: human-friendly type alias of event_type
     */
    public function getTypeAttribute(): string
    {
        return $this->event_type;
    }

    /**
     * Derived attribute: title from event_data or default per type
     */
    public function getTitleAttribute(): string
    {
        $data = $this->event_data ?? [];
        if (!empty($data['title'])) {
            return (string) $data['title'];
        }
        return $this->defaultTitle();
    }

    /**
     * Derived attribute: description (may contain HTML)
     */
    public function getDescriptionAttribute(): string
    {
        $data = $this->event_data ?? [];
        if (!empty($data['description'])) {
            return (string) $data['description'];
        }
        // Fallback simple description based on type
        return $this->defaultDescription();
    }

    /**
     * Derived attribute: importance from 1..5
     */
    public function getImportanceAttribute(): int
    {
        $data = $this->event_data ?? [];
        if (isset($data['importance']) && is_numeric($data['importance'])) {
            return max(1, min(5, (int) $data['importance']));
        }
        // Default per type
        return static::defaultImportanceForType($this->event_type);
    }

    /**
     * Derived attribute: read state for the authenticated commander's perspective
     */
    public function getReadAttribute(): bool
    {
        $user = Auth::user();
        if (!$user || !$user->relationLoaded('commander') && !$user->commander) {
            return false;
        }
        $commanderId = $user->commander->id;
        // Avoid extra queries if relation is eager loaded
        if ($this->relationLoaded('reads')) {
            return $this->reads->where('commander_id', $commanderId)->isNotEmpty();
        }
        return GameEventRead::where('game_event_id', $this->id)
            ->where('commander_id', $commanderId)
            ->exists();
    }

    // Associated entity IDs derived from event_data
    public function getPlanetIdAttribute(): ?int { return $this->event_data['planet_id'] ?? null; }
    public function getFleetIdAttribute(): ?int { return $this->event_data['fleet_id'] ?? null; }
    public function getTechnologyIdAttribute(): ?int { return $this->event_data['technology_id'] ?? null; }
    public function getRelatedCommanderIdAttribute(): ?int { return $this->event_data['related_commander_id'] ?? ($this->event_data['commander_id'] ?? null); }
    public function getConsequencesAttribute(): array { return $this->event_data['consequences'] ?? []; }
    public function getPossibleActionsAttribute(): array { return $this->event_data['possible_actions'] ?? []; }
    public function getAdditionalDataAttribute(): array { return $this->event_data['additional_data'] ?? []; }

    // UI helper methods
    public function getBadgeClass(): string
    {
        return match ($this->importance) {
            5 => 'badge bg-danger',
            4 => 'badge bg-warning text-dark',
            3 => 'badge bg-info',
            2 => 'badge bg-secondary',
            default => 'badge bg-secondary',
        };
    }

    public function getIconClass(): string
    {
        return static::iconForType($this->event_type);
    }

    public function getImportanceText(): string
    {
        return match ($this->importance) {
            5 => 'Critique',
            4 => 'Important',
            3 => 'Modéré',
            2 => 'Faible',
            default => 'Mineur',
        };
    }

    public function getShortDescription(): string
    {
        // Strip tags and limit length
        return Str::limit(strip_tags($this->description), 140);
    }

    public static function getTypeText(string $type): string
    {
        return static::typeLabel($type);
    }

    public function getTypeTextAttribute(): string
    {
        return static::typeLabel($this->event_type);
    }

    // Related entity helpers used by views
    public function getPlanet(): ?Planet
    {
        return $this->planet_id ? Planet::find($this->planet_id) : null;
    }

    public function getFleet(): ?Fleet
    {
        return $this->fleet_id ? Fleet::find($this->fleet_id) : null;
    }

    public function getTechnology(): ?Technology
    {
        return $this->technology_id ? Technology::find($this->technology_id) : null;
    }

    public function getRelatedCommander(): ?Commander
    {
        return $this->related_commander_id ? Commander::find($this->related_commander_id) : null;
    }

    // ===== Internal helpers =====
    protected function defaultTitle(): string
    {
        $d = $this->event_data ?? [];
        return match ($this->event_type) {
            'player_joined' => 'Nouveau commandant: ' . ($d['commander_name'] ?? '#'.$d['commander_id'] ?? 'Inconnu'),
            'player_left' => 'Départ d\'un commandant: ' . ($d['commander_name'] ?? '#'.$d['commander_id'] ?? 'Inconnu'),
            'space_combat' => 'Combat spatial',
            'planet_colonization' => 'Colonisation planétaire',
            'technology_discovery' => 'Découverte technologique',
            'diplomatic_event' => 'Événement diplomatique',
            default => 'Événement du jeu',
        };
    }

    protected function defaultDescription(): string
    {
        $d = $this->event_data ?? [];
        return match ($this->event_type) {
            'player_joined' => sprintf("Le commandant %s a rejoint la partie.", $d['commander_name'] ?? ('#'.$d['commander_id'] ?? 'inconnu')),
            'player_left' => sprintf("Le commandant %s a quitté la partie.", $d['commander_name'] ?? ('#'.$d['commander_id'] ?? 'inconnu')),
            'space_combat' => 'Un combat spatial a eu lieu.',
            'planet_colonization' => 'Une nouvelle planète a été colonisée.',
            'technology_discovery' => 'Une technologie a été découverte.',
            'diplomatic_event' => 'Un événement diplomatique a été enregistré.',
            default => 'Un événement s\'est produit.',
        };
    }

    protected static function defaultImportanceForType(string $type): int
    {
        return match ($type) {
            'space_combat' => 5,
            'planet_colonization' => 4,
            'technology_discovery' => 3,
            'diplomatic_event' => 3,
            'player_joined', 'player_left' => 2,
            default => 2,
        };
    }

    protected static function iconForType(string $type): string
    {
        return match ($type) {
            'space_combat' => 'fa-crosshairs',
            'planet_colonization' => 'fa-globe',
            'technology_discovery' => 'fa-flask',
            'diplomatic_event' => 'fa-handshake',
            'player_joined' => 'fa-user-plus',
            'player_left' => 'fa-user-minus',
            default => 'fa-bell',
        };
    }

    protected static function typeLabel(string $type): string
    {
        return match ($type) {
            'space_combat' => 'Combat spatial',
            'planet_colonization' => 'Colonisation',
            'technology_discovery' => 'Découverte technologique',
            'diplomatic_event' => 'Diplomatie',
            'player_joined' => 'Nouveau joueur',
            'player_left' => 'Départ joueur',
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }
}
