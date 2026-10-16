<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Utilisateur extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'utilisateurs';

    protected $primaryKey = 'id_utilisateur';

    public $timestamps = false;

    protected $authPasswordName = 'mot_de_passe';

    protected $fillable = [
        'nom_utilisateur',
        'prenoms_utilisateur',
        'email',
        'telephone_utilisateur',
        'mot_de_passe',
        'role',
        'statut_compte',
        'date_creation',
    ];

    protected $hidden = [
        'mot_de_passe',
    ];

    protected function casts(): array
    {
        return [
            'mot_de_passe' => 'hashed',
            'date_creation' => 'datetime',
        ];
    }

    public function etudiant(): HasOne
    {
        return $this->hasOne(Etudiant::class, 'id_utilisateur', 'id_utilisateur');
    }

    public function enseignant(): HasOne
    {
        return $this->hasOne(Enseignant::class, 'id_utilisateur', 'id_utilisateur');
    }

    public function parentRattache(): HasOne
    {
        return $this->hasOne(ParentTuteur::class, 'id_utilisateur', 'id_utilisateur');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'id_utilisateur', 'id_utilisateur');
    }

    public function nomComplet(): string
    {
        return trim($this->prenoms_utilisateur.' '.$this->nom_utilisateur);
    }

    public function resume(): array
    {
        return [
            'id_utilisateur' => $this->id_utilisateur,
            'nom' => $this->nom_utilisateur,
            'prenoms' => $this->prenoms_utilisateur,
            'nom_complet' => $this->nomComplet(),
            'email' => $this->email,
            'telephone' => $this->telephone_utilisateur,
            'role' => $this->role,
            'statut_compte' => $this->statut_compte,
            'matricule' => $this->etudiant?->matricule,
            'code_enseignant' => $this->enseignant?->code_enseignant,
            'code_parent' => $this->parentRattache?->code_parent,
            'enfants' => $this->parentRattache?->enfants
                ->map(fn ($enfant) => [
                    'matricule' => $enfant->matricule,
                    'nom_complet' => $enfant->utilisateur?->nomComplet(),
                ])->values()->all() ?? [],
        ];
    }
}
