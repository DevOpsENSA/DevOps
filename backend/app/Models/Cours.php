<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cours extends Model
{
    protected $table = 'cours';

    protected $primaryKey = 'idCours';

    protected $fillable = [
        'name',
        'description',
        'idEtudiant',
        'idFiliere',
        'idSemestre',
        'file_path',
        'lesson_url',
    ];

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(Etudiant::class, 'idEtudiant', 'id');
    }

    public function filiere(): BelongsTo
    {
        return $this->belongsTo(Filiere::class, 'idFiliere', 'idFiliere');
    }

    public function semestre(): BelongsTo
    {
        return $this->belongsTo(Semestre::class, 'idSemestre', 'idSemestre');
    }
}
