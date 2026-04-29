<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Semestre extends Model
{
    protected $table = 'semestres';

    protected $primaryKey = 'idSemestre';

    protected $fillable = ['idEcole'];

    public function ecole(): BelongsTo
    {
        return $this->belongsTo(Ecole::class, 'idEcole', 'idEcole');
    }

    public function cours(): HasMany
    {
        return $this->hasMany(Cours::class, 'idSemestre', 'idSemestre');
    }
}
