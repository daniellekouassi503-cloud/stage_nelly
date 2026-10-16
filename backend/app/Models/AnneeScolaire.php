<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnneeScolaire extends Model
{
    protected $table = 'annees_scolaires';

    protected $primaryKey = 'code_annee';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['code_annee', 'libelle_annee', 'est_courante'];

    protected function casts(): array
    {
        return ['est_courante' => 'boolean'];
    }

    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class, 'code_annee', 'code_annee');
    }
}
