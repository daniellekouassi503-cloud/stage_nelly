<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Filiere extends Model
{
    protected $table = 'filieres';

    protected $primaryKey = 'code_filiere';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['code_filiere', 'libelle_filiere'];

    public function classes(): HasMany
    {
        return $this->hasMany(Classe::class, 'code_filiere', 'code_filiere');
    }
}
