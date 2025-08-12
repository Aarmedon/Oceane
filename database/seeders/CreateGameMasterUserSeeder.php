<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CreateGameMasterUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $gmList = (string) env('GAMEMASTERS', '');
        $emails = collect(explode(',', $gmList))
            ->map(fn ($e) => strtolower(trim($e)))
            ->filter()
            ->unique()
            ->values();

        if ($emails->isEmpty()) {
            $this->command?->warn('No GAMEMASTERS specified in .env. Skipping GM user creation.');
            return;
        }

        foreach ($emails as $email) {
            $name = 'Game Master';
            $tempPassword = 'Temp#Oceane2025';

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make($tempPassword), // only on create
                    'email_verified_at' => now(),
                ]
            );

            if (! $user->wasRecentlyCreated) {
                // Do not overwrite existing password; ensure verified and name present
                $user->forceFill([
                    'name' => $user->name ?: $name,
                    'email_verified_at' => $user->email_verified_at ?: now(),
                ])->save();
                $this->command?->info("GM user ensured: {$email} (password unchanged)");
            } else {
                $this->command?->info("GM user created: {$email} / {$tempPassword}");
            }
        }
    }
}
