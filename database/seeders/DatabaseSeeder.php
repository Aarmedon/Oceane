<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Ensure at least one test user exists (optional in dev)
        if (!User::where('email', 'test@example.com')->exists()) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
        }

        // Seed Game Master accounts from GAMEMASTERS env
        $this->call(CreateGameMasterUserSeeder::class);

        // Core game data seeders
        $this->call([
            BonusesSeeder::class,
            GoodsSeeder::class,
            GoodsBonusSeeder::class,
            PlanetsFromSystemsSeeder::class,
        ]);
    }
}
