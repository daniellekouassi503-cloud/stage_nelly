<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Etudiant extends Model
{
    protected $table = 'etudiants';

    protected $primaryKey = 'matricule';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'matricule',
        'nom_parent',
        'telephone_parent',
        'id_utilisateur',
    ];

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'id_utilisateur', 'id_utilisateur');
    }

    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class, 'matricule', 'matricule');
    }

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(ParentTuteur::class, 'rattacher', 'matricule', 'code_parent')
            ->withPivot('lien_parente');
    }

    public function fiches(): HasMany
    {
        return $this->hasMany(FicheVersement::class, 'matricule', 'matricule');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class, 'matricule', 'matricule');
    }
}
