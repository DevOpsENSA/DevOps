<?php

namespace App\Http\Controllers;

use App\Models\Cours;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LessonController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'idAdmin' => ['required', 'integer', 'exists:admins,idAdmin'],
            'idFiliere' => ['required', 'integer', 'exists:filieres,idFiliere'],
            'idSemestre' => ['required', 'integer', 'exists:semestres,idSemestre'],
            'lesson_file' => ['required', 'file', 'max:10240'],
        ]);

        $storedPath = $request->file('lesson_file')->store('lessons', 'public');

        $cours = Cours::create([
            'name' => $validated['name'],
            'idAdmin' => $validated['idAdmin'],
            'idFiliere' => $validated['idFiliere'],
            'idSemestre' => $validated['idSemestre'],
            'file_path' => $storedPath,
        ]);

        return response()->json([
            'message' => 'Lesson uploaded successfully.',
            'data' => $this->formatLesson($cours->load(['admin', 'filiere', 'semestre'])),
        ], 201);
    }

    public function recent(Request $request): JsonResponse
    {
        $limit = (int) $request->query('limit', 10);
        $safeLimit = min(max($limit, 1), 50);

        $lessons = Cours::with(['admin', 'filiere', 'semestre'])
            ->latest()
            ->limit($safeLimit)
            ->get()
            ->map(fn (Cours $cours) => $this->formatLesson($cours));

        return response()->json(['data' => $lessons]);
    }

    public function show(int $idCours): JsonResponse
    {
        $cours = Cours::with(['admin', 'filiere', 'semestre'])->findOrFail($idCours);

        return response()->json([
            'data' => $this->formatLesson($cours),
        ]);
    }

    private function formatLesson(Cours $cours): array
    {
        return [
            'idCours' => $cours->idCours,
            'name' => $cours->name,
            'idAdmin' => $cours->idAdmin,
            'adminName' => $cours->admin?->name,
            'idFiliere' => $cours->idFiliere,
            'filiereName' => $cours->filiere?->nomFiliere,
            'idSemestre' => $cours->idSemestre,
            'fileUrl' => Storage::disk('public')->url($cours->file_path),
            'createdAt' => $cours->created_at,
        ];
    }
}
