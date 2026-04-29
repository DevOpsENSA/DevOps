<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Admin extends Model
{
    protected $table = 'admins';

    protected $primaryKey = 'idAdmin';

    protected $fillable = ['name'];

    public function cours(): HasMany
    {
        return $this->hasMany(Cours::class, 'idAdmin', 'idAdmin');
    }
}
