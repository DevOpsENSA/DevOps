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
        'idAdmin',
        'idFiliere',
        'idSemestre',
        'file_path',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'idAdmin', 'idAdmin');
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
