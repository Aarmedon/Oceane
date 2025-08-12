<?php

namespace Database\Seeders;

use App\Models\Commander;
use App\Models\Directive;
use App\Models\Fleet;
use App\Models\FleetCargo;
use App\Models\Race;
use App\Models\Ship;
use App\Models\ShipComponent;
use App\Models\ShipDesign;
use App\Models\StarSystem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class TestGameDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Races pool (idempotent, avoid unique name conflicts)
        $canonicalRaces = ['Humains','Zyriens','Khel','Vorlans','Tauris','Nerathi'];
        foreach ($canonicalRaces as $rName) {
            Race::firstOrCreate(
                ['name' => $rName],
                [
                    'slug' => Str::slug($rName),
                    'description' => 'Race '.$rName,
                    'is_playable' => true,
                    'image_path' => null,
                ]
            );
        }
        // pick 4 random races from existing
        $races = Race::inRandomOrder()->take(4)->get();
        $this->command?->info('Races ready: '.Race::count());

        // 2) Directives pool: ensure seeded from config (idempotent)
        $directives = Directive::all();
        if ($directives->isEmpty()) {
            $map = config('oceane.directives', []);
            $now = now();
            foreach ($map as $code => $id) {
                Directive::updateOrCreate(
                    ['id' => (int) $id],
                    [
                        'code' => (string) $code,
                        'name' => Str::title(str_replace('_', ' ', (string) $code)),
                        'description' => null,
                        'updated_at' => $now,
                    ]
                );
            }
            $directives = Directive::all();
        }
        $this->command?->info('Directives ready: '.$directives->count());

        // 3) Star systems pool
        $systems = StarSystem::factory()->count(12)->create();
        $this->command?->info('Star systems created: '.$systems->count());

        // 4) Commanders (create persisted with race_id to satisfy NOT NULL FKs)
        $raceIds = $races->pluck('id')->all();
        $commanders = collect();
        foreach (range(1, 5) as $i) {
            $commanders->push(
                Commander::factory()->create([
                    'race_id' => Arr::random($raceIds),
                ])
            );
        }
        $this->command?->info('Commanders created: '.$commanders->count());

        // 5) Ship designs pool
        $designs = collect();
        foreach (range(1, 6) as $i) {
            $designs->push(ShipDesign::factory()->create([
                'creator_id' => Arr::random($commanders->pluck('id')->all()),
            ]));
        }
        $this->command?->info('Ship designs created: '.$designs->count());

        // 6) Ship components pool
        $components = ShipComponent::factory()->count(10)->create();
        $this->command?->info('Ship components created: '.$components->count());

        // 7) Fleets + ships + cargo
        foreach ($commanders as $commander) {
            $fleetCount = random_int(2, 4);
            for ($f = 0; $f < $fleetCount; $f++) {
                $currentSystem = $systems->random();
                $destSystem = random_int(0, 1) ? $systems->random() : null;
                try {
                    $fleet = Fleet::factory()->create([
                        'commander_id' => $commander->id,
                        'current_system_id' => $currentSystem->id,
                        'destination_system_id' => $destSystem?->id,
                        'galaxy_id' => $currentSystem->sector->galaxy_id,
                        'directive_id' => $directives->random()->id,
                        'status' => Arr::random([
                            Fleet::STATUS_DOCKED,
                            Fleet::STATUS_MOVING,
                            Fleet::STATUS_WAITING,
                        ]),
                    ]);
                } catch (\Throwable $e) {
                    $this->command?->error('Fleet create failed for commander '.$commander->id.' at system '.$currentSystem->id.' -> '.$e->getMessage());
                    throw $e;
                }

                // Cargo: 0-3 entries
                $resources = ['metal','crystal','fuel','food'];
                foreach (range(1, random_int(0, 3)) as $ci) {
                    FleetCargo::create([
                        'fleet_id' => $fleet->id,
                        'resource_type' => Arr::random($resources),
                        'quantity' => random_int(50, 2000),
                    ]);
                }

                // Ships: 3-8 per fleet
                $shipCount = random_int(3, 8);
                for ($s = 0; $s < $shipCount; $s++) {
                    $design = $designs->random();
                    $maxHull = $design->max_hull_points;
                    $maxShield = $design->max_shield_points;
                    try {
                        $ship = Ship::factory()->create([
                            'fleet_id' => $fleet->id,
                            'ship_design_id' => $design->id,
                            'max_hull_points' => $maxHull,
                            'max_shield_points' => $maxShield,
                            'hull_points' => (int) max(1, $maxHull * (random_int(60, 100) / 100)),
                            'shield_points' => $maxShield ? (int) max(0, $maxShield * (random_int(30, 100) / 100)) : 0,
                        ]);
                    } catch (\Throwable $e) {
                        $this->command?->error('Ship create failed for fleet '.$fleet->id.' design '.$design->id.' -> '.$e->getMessage());
                        throw $e;
                    }

                    // Install 1-3 components per ship via pivot
                    $installed = $components->random(random_int(1, 3));
                    $installedItems = $installed instanceof \Illuminate\Support\Collection ? $installed : collect([$installed]);
                    foreach ($installedItems as $comp) {
                        $ship->components()->attach($comp->id, [
                            'quantity' => random_int(1, 4),
                            'status' => 'operational',
                        ]);
                    }
                }
            }
        }

        $this->command?->info('Test game data seeded: races, directives, systems, commanders, fleets, ships, components, cargo.');
    }
}
