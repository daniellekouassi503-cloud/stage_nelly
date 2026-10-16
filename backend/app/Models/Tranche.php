<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tranche extends Model
{
    protected $table = 'tranches';

    protected $primaryKey = 'code_tranche';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'code_tranche',
        'libelle_tranche',
        'montant_tranche',
        'date_limite',
        'code_filiere',
        'code_niveau',
        'code_annee',
    ];

    protected function casts(): array
    {
        return [
            'montant_tranche' => 'integer',
            'date_limite' => 'date',
        ];
    }

    public function filiere(): BelongsTo
    {
        return $this->belongsTo(Filiere::class, 'code_filiere', 'code_filiere');
    }

    public function niveau(): BelongsTo
    {
        return $this->belongsTo(Niveau::class, 'code_niveau', 'code_niveau');
    }

    public function annee(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class, 'code_annee', 'code_annee');
    }

    public function fiches(): HasMany
    {
        return $this->hasMany(FicheVersement::class, 'code_tranche', 'code_tranche');
    }
}
