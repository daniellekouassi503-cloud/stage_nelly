<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Niveau extends Model
{
    protected $table = 'niveaux';

    protected $primaryKey = 'code_niveau';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['code_niveau', 'libelle_niveau'];

    public function classes(): HasMany
    {
        return $this->hasMany(Classe::class, 'code_niveau', 'code_niveau');
    }
}
