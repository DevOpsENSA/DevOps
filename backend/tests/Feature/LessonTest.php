<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Cours;
use App\Models\Etudiant;
use App\Models\Semestre;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LessonTest extends TestCase
{
    use RefreshDatabase;

    // Helper : créer un cours de test
    private function createCours(): Cours
    {
        $etudiant = Etudiant::create([
            'nom'    => 'Dupont',
            'prenom' => 'Jean'
        ]);

        $semestre = Semestre::create([
            'nomSemestre' => 'S1'
        ]);

        return Cours::create([
            'name'       => 'Cours de test',
            'idEtudiant' => $etudiant->id,
            'idSemestre' => $semestre->idSemestre,
            'lesson_url' => 'https://drive.google.com/test',
        ]);
    }

    // ══════════════════════════════
    // TESTS RECENT → /api/lessons/recent
    // ══════════════════════════════

    // TEST 1 : Récupérer les leçons récentes
    public function test_can_get_recent_lessons()
    {
        $response = $this->getJson('/api/lessons/recent');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data']);
    }

    // TEST 2 : Limit est respectée
    public function test_recent_lessons_respects_limit()
    {
        $response = $this->getJson('/api/lessons/recent?limit=5');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data']);
    }

    // ══════════════════════════════
    // TESTS SHOW → /api/lessons/{idCours}
    // ══════════════════════════════

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
