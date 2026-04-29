<?php

namespace App\Http\Controllers;

use App\Models\Compte;
use App\Models\Etudiant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'mail' => [
                'required',
                'email',
                'max:255',
                'regex:/^[a-zA-Z0-9._%+-]+@etu\.uae\.ac\.ma$/',
                'unique:comptes,mail',
            ],
            'password' => ['required', 'string', 'min:6', 'max:255'],
        ], [
            'nom.required' => 'Le nom est obligatoire.',
            'prenom.required' => 'Le prénom est obligatoire.',
            'mail.required' => 'L\'email est obligatoire.',
            'mail.email' => 'Email invalide.',
            'mail.regex' => 'Email invalide. Utilisez votre adresse @etu.uae.ac.ma.',
            'mail.unique' => 'Cet email est déjà utilisé.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.min' => 'Le mot de passe doit contenir au moins 6 caractères.',
        ]);

        $compte = DB::transaction(function () use ($validated) {
            $etudiant = Etudiant::create([
                'nom' => $validated['nom'],
                'prenom' => $validated['prenom'],
            ]);

            return Compte::create([
                'mail' => $validated['mail'],
                'password' => Hash::make($validated['password']),
                'idEtudiant' => $etudiant->id,
                'status' => 'student',
            ]);
        });

        return response()->json([
            'message' => 'Inscription réussie.',
            'data' => [
                'mail' => $compte->mail,
                'status' => $compte->status,
            ],
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mail' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'mail.required' => 'L\'email est obligatoire.',
            'mail.email' => 'Email invalide.',
            'password.required' => 'Le mot de passe est obligatoire.',
        ]);

        $compte = Compte::with('etudiant')->where('mail', $validated['mail'])->first();

        if (! $compte || ! Hash::check($validated['password'], $compte->password)) {
            return response()->json([
                'message' => 'Email ou mot de passe incorrect.',
            ], 401);
        }

        return response()->json([
            'message' => 'Connexion réussie.',
            'data' => [
                'mail' => $compte->mail,
                'status' => $compte->status,
                'nom' => $compte->etudiant?->nom,
                'prenom' => $compte->etudiant?->prenom,
            ],
        ]);
    }
}
