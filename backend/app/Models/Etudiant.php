<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Etudiant extends Model
{
    protected $table = 'etudiants';

    protected $fillable = ['nom', 'prenom'];

    public function comptes(): HasMany
    {
        return $this->hasMany(Compte::class, 'idEtudiant', 'id');
    }
}
