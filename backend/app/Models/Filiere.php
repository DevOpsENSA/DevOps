<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Filiere extends Model
{
    protected $table = 'filieres';

    protected $primaryKey = 'idFiliere';

    protected $fillable = ['nomFiliere', 'idEcole'];

    public function ecole(): BelongsTo
    {
        return $this->belongsTo(Ecole::class, 'idEcole', 'idEcole');
    }

    public function cours(): HasMany
    {
        return $this->hasMany(Cours::class, 'idFiliere', 'idFiliere');
    }
}
