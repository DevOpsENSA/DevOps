<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Compte;
use App\Models\Etudiant;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthTest extends TestCase
{
    use RefreshDatabase; // Reset la DB avant chaque test

    // ══════════════════════════════
    // TESTS REGISTER → /api/auth/register
    // ══════════════════════════════

    // TEST 1 : Register réussi
    public function test_user_can_register()
    {
        $response = $this->postJson('/api/auth/register', [
            'nom'      => 'Dupont',
            'prenom'   => 'Jean',
            'mail'     => 'jean.dupont@etu.uae.ac.ma',
            'password' => 'password123',
        ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'message' => 'Inscription réussie.',
                     'data' => [
                         'mail'   => 'jean.dupont@etu.uae.ac.ma',
                         'status' => 'student',
                     ]
                 ]);
    }

    // TEST 2 : Register avec email invalide (pas @etu.uae.ac.ma)
    public function test_register_fails_with_invalid_email_domain()
    {
        $response = $this->postJson('/api/auth/register', [
            'nom'      => 'Dupont',
            'prenom'   => 'Jean',
            'mail'     => 'jean.dupont@gmail.com', // ← mauvais domaine
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['mail']);
    }

    // TEST 3 : Register avec email déjà utilisé
    public function test_register_fails_with_duplicate_email()
    {
        // Créer un compte existant
        $etudiant = Etudiant::create(['nom' => 'Dupont', 'prenom' => 'Jean']);
        Compte::create([
            'mail'       => 'jean.dupont@etu.uae.ac.ma',
            'password'   => bcrypt('password123'),
            'idEtudiant' => $etudiant->id,
            'status'     => 'student',
        ]);

        // Essayer de créer avec le même email
        $response = $this->postJson('/api/auth/register', [
            'nom'      => 'Autre',
            'prenom'   => 'User',
            'mail'     => 'jean.dupont@etu.uae.ac.ma',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['mail']);
    }

    // TEST 4 : Register sans les champs obligatoires
    public function test_register_fails_with_missing_fields()
    {
        $response = $this->postJson('/api/auth/register', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['nom', 'prenom', 'mail', 'password']);
    }

    // ══════════════════════════════
    // TESTS LOGIN → /api/auth/login
    // ══════════════════════════════

    // TEST 5 : Login réussi
    public function test_user_can_login()
    {
        // Créer un compte réel
        $etudiant = Etudiant::create(['nom' => 'Dupont', 'prenom' => 'Jean']);
        Compte::create([
            'mail'       => 'jean.dupont@etu.uae.ac.ma',
            'password'   => bcrypt('password123'),
            'idEtudiant' => $etudiant->id,
            'status'     => 'student',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'mail'     => 'jean.dupont@etu.uae.ac.ma',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Connexion réussie.',
                     'data' => [
                         'mail'   => 'jean.dupont@etu.uae.ac.ma',
                         'status' => 'student',
                         'nom'    => 'Dupont',
                         'prenom' => 'Jean',
                     ]
                 ]);
    }

    // TEST 6 : Login mauvais mot de passe
    public function test_login_fails_with_wrong_password()
    {
        $etudiant = Etudiant::create(['nom' => 'Dupont', 'prenom' => 'Jean']);
        Compte::create([
            'mail'       => 'jean.dupont@etu.uae.ac.ma',
            'password'   => bcrypt('password123'),
            'idEtudiant' => $etudiant->id,
            'status'     => 'student',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'mail'     => 'jean.dupont@etu.uae.ac.ma',
            'password' => 'mauvais_password',
        ]);

        $response->assertStatus(401)
                 ->assertJson([
                     'message' => 'Email ou mot de passe incorrect.',
                 ]);
    }

    // TEST 7 : Login email inexistant
    public function test_login_fails_with_unknown_email()
    {
        $response = $this->postJson('/api/auth/login', [
            'mail'     => 'inconnu@etu.uae.ac.ma',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
                 ->assertJson([
                     'message' => 'Email ou mot de passe incorrect.',
                 ]);
    }
}
