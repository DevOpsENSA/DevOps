<?php

namespace App\Http\Controllers;

use App\Models\Compte;
use App\Models\Cours;
use App\Models\Ecole;
use App\Models\Filiere;
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
                'message' => 'Compte administrateur introuvable.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'nomFiliere' => ['required_without:idFiliere', 'nullable', 'string', 'max:255'],
            'idFiliere' => ['required_without:nomFiliere', 'nullable', 'integer', 'exists:filieres,idFiliere'],
            'idSemestre' => ['required', 'integer', 'exists:semestres,idSemestre'],
            'lesson_file' => ['nullable', 'file', 'max:10240'],
            'lesson_url' => ['nullable', 'url', 'max:2048'],
        ], [
            'name.required' => 'Le titre est obligatoire.',
            'idSemestre.required' => 'Le semestre est obligatoire.',
            'idSemestre.exists' => 'Semestre introuvable.',
            'nomFiliere.required_without' => 'La filière est obligatoire.',
            'idFiliere.exists' => 'Filière introuvable.',
            'lesson_url.url' => 'Le lien fourni n\'est pas valide.',
            'lesson_file.max' => 'Le fichier dépasse la taille autorisée (10 Mo).',
        ]);

        if (! $request->hasFile('lesson_file') && empty($validated['lesson_url'])) {
            return response()->json([
                'message' => 'Ajoutez un fichier ou un lien Google Drive.',
            ], 422);
        }

        $idFiliere = $validated['idFiliere'] ?? null;

        if (! $idFiliere) {
            $idFiliere = $this->resolveFiliereByName(trim($validated['nomFiliere']));
            if (! $idFiliere) {
                return response()->json([
                    'message' => 'Aucune école configurée. Impossible de créer la filière.',
                ], 422);
            }
        }

        $storedPath = $request->hasFile('lesson_file')
            ? $request->file('lesson_file')->store('lessons', 'public')
            : null;

        $cours = Cours::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'idEtudiant' => $adminCompte->idEtudiant,
            'idFiliere' => $idFiliere,
            'idSemestre' => $validated['idSemestre'],
            'file_path' => $storedPath,
            'lesson_url' => $validated['lesson_url'] ?? null,
        ]);

        return response()->json([
            'message' => 'Leçon publiée avec succès.',
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

    /**
     * Resolve a filière by its name (case-insensitive).
     * Auto-creates it (linked to the first available école) when missing.
     * Returns null only when no école exists at all.
     */
    private function resolveFiliereByName(string $name): ?int
    {
        $filiere = Filiere::whereRaw('LOWER("nomFiliere") = ?', [mb_strtolower($name)])->first();
        if ($filiere) {
            return (int) $filiere->idFiliere;
        }

        $idEcole = Ecole::query()->value('idEcole');
        if (! $idEcole) {
            return null;
        }

        $filiere = Filiere::create([
            'nomFiliere' => $name,
            'idEcole' => $idEcole,
        ]);

        return (int) $filiere->idFiliere;
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
