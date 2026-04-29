<?php

namespace App\Http\Controllers;

use App\Models\Cours;
use App\Models\Compte;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LessonController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        /** @var Compte|null $adminCompte */
        $adminCompte = $request->attributes->get('admin_compte');

        if (! $adminCompte) {
            return response()->json([
                'message' => 'Admin account not found from request context.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'idFiliere' => ['required', 'integer', 'exists:filieres,idFiliere'],
            'idSemestre' => ['required', 'integer', 'exists:semestres,idSemestre'],
            'lesson_file' => ['nullable', 'file', 'max:10240'],
            'lesson_url' => ['nullable', 'url', 'max:2048'],
        ]);

        if (! $request->hasFile('lesson_file') && empty($validated['lesson_url'])) {
            return response()->json([
                'message' => 'Provide either lesson_file or lesson_url.',
            ], 422);
        }

        $storedPath = $request->hasFile('lesson_file')
            ? $request->file('lesson_file')->store('lessons', 'public')
            : null;

        $cours = Cours::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'idEtudiant' => $adminCompte->idEtudiant,
            'idFiliere' => $validated['idFiliere'],
            'idSemestre' => $validated['idSemestre'],
            'file_path' => $storedPath,
            'lesson_url' => $validated['lesson_url'] ?? null,
        ]);

        return response()->json([
            'message' => 'Lesson uploaded successfully.',
            'data' => $this->formatLesson($cours->load(['etudiant', 'filiere', 'semestre'])),
        ], 201);
    }

    public function recent(Request $request): JsonResponse
    {
        $limit = (int) $request->query('limit', 10);
        $safeLimit = min(max($limit, 1), 50);

        $lessons = Cours::with(['etudiant', 'filiere', 'semestre'])
            ->latest()
            ->limit($safeLimit)
            ->get()
            ->map(fn (Cours $cours) => $this->formatLesson($cours));

        return response()->json(['data' => $lessons]);
    }

    public function show(int $idCours): JsonResponse
    {
        $cours = Cours::with(['etudiant', 'filiere', 'semestre'])->findOrFail($idCours);

        return response()->json([
            'data' => $this->formatLesson($cours),
        ]);
    }

    private function formatLesson(Cours $cours): array
    {
        return [
            'idCours' => $cours->idCours,
            'name' => $cours->name,
            'description' => $cours->description,
            'idEtudiant' => $cours->idEtudiant,
            'uploaderName' => trim(($cours->etudiant?->nom ?? '').' '.($cours->etudiant?->prenom ?? '')),
            'idFiliere' => $cours->idFiliere,
            'filiereName' => $cours->filiere?->nomFiliere,
            'idSemestre' => $cours->idSemestre,
            'fileUrl' => $cours->lesson_url ?: ($cours->file_path ? Storage::disk('public')->url($cours->file_path) : null),
            'createdAt' => $cours->created_at,
        ];
    }
}
