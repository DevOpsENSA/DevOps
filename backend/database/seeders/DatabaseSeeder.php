<?php

namespace Database\Seeders;

use App\Models\Ecole;
use App\Models\Filiere;
use App\Models\Semestre;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Lessons domain reference data (idempotent).
        $ecole = Ecole::firstOrCreate(['nomEcole' => 'ENSA']);

        Filiere::firstOrCreate(
            ['nomFiliere' => 'GI'],
            ['idEcole' => $ecole->idEcole],
        );

        foreach (range(1, 6) as $idSemestre) {
            Semestre::firstOrCreate(
                ['idSemestre' => $idSemestre],
                ['idEcole' => $ecole->idEcole],
            );
        }
    }
}
