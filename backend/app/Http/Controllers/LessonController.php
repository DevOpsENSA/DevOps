<?php

namespace App\Http\Controllers;

use App\Models\Compte;
use App\Models\Cours;
use App\Models\Ecole;
use App\Models\Filiere;
use App\Models\Semestre;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LessonController extends Controller
{
    public function store(Request $request): JsonResponse
    {


    //kind of hard to understand
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
            'nomFiliere' => ['required', 'string', 'max:255'],
            'idSemestre' => ['required', 'integer', 'between:1,6'],
            'lesson_file' => ['nullable', 'file', 'max:10240'],
            'lesson_url' => ['nullable', 'url', 'max:2048'],
        ], [
            'name.required' => 'Le titre est obligatoire.',
            'idSemestre.required' => 'Le semestre est obligatoire.',
            'idSemestre.between' => 'Semestre invalide.',
            'nomFiliere.required' => 'La filière est obligatoire.',
            'lesson_url.url' => 'Le lien fourni n\'est pas valide.',
            'lesson_file.max' => 'Le fichier dépasse la taille autorisée (10 Mo).',
        ]);

        // Either an uploaded file or a Drive link is required.
        if (! $request->hasFile('lesson_file') && empty($validated['lesson_url'])) {
            return response()->json([
                'message' => 'Ajoutez un fichier ou un lien Google Drive.',
            ], 422);
        }

        // Resolve (and self-heal) the referenced école / filière / semestre so the
        // non-nullable foreign keys on `cours` can never blow up with a 500.
        $idEcole = $this->resolveEcoleId();
        $idFiliere = $this->resolveFiliereByName(trim($validated['nomFiliere']), $idEcole);
        $idSemestre = $this->resolveSemestreId((int) $validated['idSemestre'], $idEcole);

        $storedPath = $request->hasFile('lesson_file')
            ? $request->file('lesson_file')->store('lessons', 'public')
            : null;

        $cours = Cours::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'idEtudiant' => $adminCompte->idEtudiant,
            'idFiliere' => $idFiliere,
            'idSemestre' => $idSemestre,
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
     * Return the first école id, creating a default one when none exists.
     */
    private function resolveEcoleId(): int
    {
        return (int) (Ecole::query()->value('idEcole')
            ?? Ecole::create(['nomEcole' => 'ENSA'])->idEcole);
    }

    /**
     * Resolve a filière by its name (case-insensitive),
     * auto-creating it under the given école when missing.
     */
    private function resolveFiliereByName(string $name, int $idEcole): int
    {
        $filiere = Filiere::whereRaw('LOWER("nomFiliere") = ?', [mb_strtolower($name)])->first();
        if ($filiere) {
            return (int) $filiere->idFiliere;
        }

        return (int) Filiere::create([
            'nomFiliere' => $name,
            'idEcole' => $idEcole,
        ])->idFiliere;
    }

    /**
     * Ensure the semestre row (1-6) exists, creating it under the given
     * école when missing so the cours foreign key always resolves.
     */
    private function resolveSemestreId(int $idSemestre, int $idEcole): int
    {
        if (! Semestre::whereKey($idSemestre)->exists()) {
            Semestre::create([
                'idSemestre' => $idSemestre,
                'idEcole' => $idEcole,
            ]);
        }

        return $idSemestre;
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
