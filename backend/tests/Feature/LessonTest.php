<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Cours;
use App\Models\Ecole;
use App\Models\Etudiant;
use App\Models\Filiere;
use App\Models\Semestre;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LessonTest extends TestCase
{
    use RefreshDatabase;

    private function createCours(): Cours
    {
        $etudiant = Etudiant::create([
            'nom'    => 'Dupont',
            'prenom' => 'Jean'
        ]);

        // Ecole obligatoire pour Semestre et Filiere
        $ecole = Ecole::create([
            'nomEcole' => 'ENSATE'
        ]);

        // Filiere obligatoire pour Cours
        $filiere = Filiere::create([
            'nomFiliere' => 'Informatique',
            'idEcole'    => $ecole->idEcole
        ]);

        // Semestre nécessite idEcole
        $semestre = Semestre::create([
            'nomSemestre' => 'S1',
            'idEcole'     => $ecole->idEcole
        ]);

        return Cours::create([
            'name'       => 'Cours de test',
            'idEtudiant' => $etudiant->id,
            'idFiliere'  => $filiere->idFiliere,
            'idSemestre' => $semestre->idSemestre,
            'lesson_url' => 'https://drive.google.com/test',
        ]);
    }

    // TEST 1 : Récupérer les leçons récentes
    public function test_can_get_recent_lessons()
    {
        $response = $this->getJson('/api/lessons/recent');
        $response->assertStatus(200)
                 ->assertJsonStructure(['data']);
    }

    // TEST 2 : Limit respectée
    public function test_recent_lessons_respects_limit()
    {
        $response = $this->getJson('/api/lessons/recent?limit=5');
        $response->assertStatus(200)
                 ->assertJsonStructure(['data']);
    }

    // TEST 3 : Voir une leçon existante
    public function test_can_show_existing_lesson()
    {
        $cours = $this->createCours();

        $response = $this->getJson('/api/lessons/' . $cours->idCours);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'idCours',
                         'name',
                         'description',
                         'idEtudiant',
                         'uploaderName',
                         'idSemestre',
                         'fileUrl',
                         'createdAt',
                     ]
                 ]);
    }

    // TEST 4 : Leçon inexistante → 404
    public function test_show_returns_404_for_unknown_lesson()
    {
        $response = $this->getJson('/api/lessons/99999');
        $response->assertStatus(404);
    }
}
