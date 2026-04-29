<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Compte extends Model
{
    protected $table = 'comptes';

    protected $primaryKey = 'mail';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['mail', 'password', 'idEtudiant', 'status'];

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(Etudiant::class, 'idEtudiant', 'id');
    }
}
