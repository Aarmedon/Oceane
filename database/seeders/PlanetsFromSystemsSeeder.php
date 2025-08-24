<?php

namespace Database\Seeders;

use App\Models\Commander;
use App\Models\Planet;
use App\Models\StarSystem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanetsFromSystemsSeeder extends Seeder
{
    /**
     * Seed planets for each star system, ensuring systems with commander_id
     * receive at least one colonized planet owned by that commander.
     */
    public function run(): void
    {
        // Do not reseed if planets already exist
        if (Planet::query()->exists()) {
            $this->command?->warn('Planets table is not empty; skipping PlanetsFromSystemsSeeder.');
            return;
        }

        $systems = StarSystem::query()
            ->with(['sector:id,galaxy_id'])
            ->orderBy('id')
            ->get(['id','name','star_type','position_x','position_y','commander_id','sector_id']);

        $batch = [];
        $now = now();

        foreach ($systems as $s) {
            // Deterministic count per system: 3..5 planets
            $count = 3 + (($s->id % 3));

            for ($i = 1; $i <= $count; $i++) {
                $name = sprintf('%s %s', (string) $s->name, $this->romanNumeral($i));

                // Basic attributes derived from system/star_type and index
                $size = 3 + (($s->id + $i) % 8);              // 3..10
                $type = 1 + (($s->star_type + $i) % 6);       // 1..6
                $minerals = ($s->id * 13 + $i * 7) % 101;     // 0..100
                $radiation = ($s->id * 17 + $i * 5) % 101;    // 0..100
                $temperature = -80 + (($s->id * 3 + $i * 11) % 361) - 100; // ~ -180..+80 approx
                $gravity = 5 + (($s->id + $i * 2) % 20);      // 5..24
                $atmo = ($s->id + $i) % 6;                    // 0..5

                $isColonized = false;
                $ownerId = null;
                if ($i === 1 && !is_null($s->commander_id)) {
                    // If the star system has an owner, colonize the first planet for that commander
                    $ownerId = (int) $s->commander_id;
                    // Only set if commander exists to avoid FK issues
                    if (Commander::query()->where('id', $ownerId)->exists()) {
                        $isColonized = true;
                    } else {
                        $ownerId = null;
                    }
                }

                $batch[] = [
                    'star_system_id' => (int) $s->id,
                    'name' => $name,
                    'position_in_system' => $i,
                    'size' => $size,
                    'type' => $type,
                    'mineral_resources' => $minerals,
                    'radiation_level' => $radiation,
                    'temperature' => $temperature,
                    'gravity' => $gravity,
                    'atmosphere_type' => $atmo,
                    'image_path' => null,
                    'is_colonized' => $isColonized,
                    'commander_id' => $ownerId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // Bulk insert in chunks
        foreach (array_chunk($batch, 1000) as $chunk) {
            DB::table('planets')->insert($chunk);
        }

        $this->command?->info('PlanetsFromSystemsSeeder: seeded '.count($batch).' planets.');
    }

    private function romanNumeral(int $n): string
    {
        $map = [
            'M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400,
            'C' => 100,  'XC' => 90,  'L' => 50,  'XL' => 40,
            'X' => 10,   'IX' => 9,   'V' => 5,   'IV' => 4,
            'I' => 1,
        ];
        $res = '';
        foreach ($map as $roman => $int) {
            while ($n >= $int) { $res .= $roman; $n -= $int; }
        }
        return $res !== '' ? $res : 'I';
    }
}
