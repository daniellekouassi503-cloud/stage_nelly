<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmploiDuTemps extends Model
{
    protected $table = 'emplois_du_temps';

    protected $primaryKey = 'id_seance';

    public $timestamps = false;

    protected $fillable = [
        'code_classe',
        'code_annee',
        'jour_semaine',
        'heure_debut',
        'heure_fin',
        'code_matiere',
        'libelle_matiere',
        'enseignant',
        'salle',
        'provisoire',
    ];

    protected function casts(): array
    {
        return [
            'provisoire' => 'boolean',
            'jour_semaine' => 'integer',
        ];
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class, 'code_classe', 'code_classe');
    }
}
