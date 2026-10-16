<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evaluation extends Model
{
    protected $table = 'evaluations';

    protected $primaryKey = 'num_evaluation';

    public $timestamps = false;

    protected $fillable = [
        'type_evaluation',
        'date_evaluation',
        'semestre',
        'statut_publication',
        'date_publication',
        'code_matiere',
        'code_classe',
        'code_enseignant',
        'code_annee',
    ];

    protected function casts(): array
    {
        return [
            'date_evaluation' => 'date',
            'date_publication' => 'datetime',
            'semestre' => 'integer',
        ];
    }

    public function matiere(): BelongsTo
    {
        return $this->belongsTo(Matiere::class, 'code_matiere', 'code_matiere');
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class, 'code_classe', 'code_classe');
    }

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(Enseignant::class, 'code_enseignant', 'code_enseignant');
    }

    public function annee(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class, 'code_annee', 'code_annee');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class, 'num_evaluation', 'num_evaluation');
    }

    public function estPubliee(): bool
    {
        return $this->statut_publication === 'publiee';
    }
}
