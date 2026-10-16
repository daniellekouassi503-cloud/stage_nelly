<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportCours extends Model
{
    protected $table = 'supports_cours';

    protected $primaryKey = 'code_support';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'code_support',
        'titre_support',
        'description_support',
        'fichier_support',
        'nom_original',
        'date_depot',
        'code_enseignant',
        'code_matiere',
        'code_filiere',
        'code_niveau',
    ];

    protected $hidden = ['fichier_support'];

    protected function casts(): array
    {
        return ['date_depot' => 'datetime'];
    }

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(Enseignant::class, 'code_enseignant', 'code_enseignant');
    }

    public function matiere(): BelongsTo
    {
        return $this->belongsTo(Matiere::class, 'code_matiere', 'code_matiere');
    }

    public function filiere(): BelongsTo
    {
        return $this->belongsTo(Filiere::class, 'code_filiere', 'code_filiere');
    }

    public function niveau(): BelongsTo
    {
        return $this->belongsTo(Niveau::class, 'code_niveau', 'code_niveau');
    }
}
