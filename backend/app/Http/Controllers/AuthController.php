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
            'mail' => ['required', 'email', 'max:255', 'unique:comptes,mail'],
            'password' => ['required', 'string', 'min:6', 'max:255'],
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
            'message' => 'Registration successful.',
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
        ]);

        $compte = Compte::with('etudiant')->where('mail', $validated['mail'])->first();

        if (! $compte || ! Hash::check($validated['password'], $compte->password)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        return response()->json([
            'message' => 'Login successful.',
            'data' => [
                'mail' => $compte->mail,
                'status' => $compte->status,
                'nom' => $compte->etudiant?->nom,
                'prenom' => $compte->etudiant?->prenom,
            ],
        ]);
    }
}
