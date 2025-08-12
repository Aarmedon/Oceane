<?php

// Script pour ajouter des races à la base de données
// À exécuter avec: php add_races.php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Race;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Vérifier si la table races existe
if (!Schema::hasTable('races')) {
    echo "La table 'races' n'existe pas.\n";
    exit(1);
}

// Vérifier les colonnes existantes
$columns = Schema::getColumnListing('races');
echo "Colonnes existantes dans la table 'races': " . implode(', ', $columns) . "\n";

// Ajouter des races de base
$races = [
    [
        'id' => 1,
        'name' => 'Humains',
    ],
    [
        'id' => 2,
        'name' => 'Zorg',
    ],
    [
        'id' => 3,
        'name' => 'Eldari',
    ],
    [
        'id' => 4,
        'name' => 'Nexus',
    ],
];

foreach ($races as $raceData) {
    // Vérifier si la race existe déjà
    $existingRace = DB::table('races')->where('id', $raceData['id'])->first();
    
    if ($existingRace) {
        echo "La race '{$raceData['name']}' existe déjà.\n";
    } else {
        // Insérer la race
        DB::table('races')->insert($raceData);
        echo "La race '{$raceData['name']}' a été ajoutée.\n";
    }
}

echo "Opération terminée.\n";
