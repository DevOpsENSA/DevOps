<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ecole extends Model
{
    protected $table = 'ecoles';

    protected $primaryKey = 'idEcole';

    protected $fillable = ['nomEcole'];

    public function filieres(): HasMany
    {
        return $this->hasMany(Filiere::class, 'idEcole', 'idEcole');
    }

    public function semestres(): HasMany
    {
        return $this->hasMany(Semestre::class, 'idEcole', 'idEcole');
    }
}
