<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanningComposition extends Model
{
    protected $table = 'plannings_composition';

    protected $primaryKey = 'code_planning';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'code_planning',
        'titre_planning',
        'date_debut',
        'date_fin',
        'fichier_planning',
        'nom_original',
        'mime_fichier',
        'date_publication',
        'code_filiere',
        'code_niveau',
        'code_annee',
        'id_utilisateur',
    ];

    protected $hidden = ['fichier_planning'];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
            'date_publication' => 'datetime',
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

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'id_utilisateur', 'id_utilisateur');
    }

    public function estImage(): bool
    {
        $mime = (string) $this->mime_fichier;
        $nom = strtolower((string) $this->nom_original);

        return str_starts_with($mime, 'image/')
            || preg_match('/\.(png|jpe?g|webp|gif)$/', $nom) === 1;
    }

    public function resume(): array
    {
        return [
            'code_planning' => $this->code_planning,
            'titre_planning' => $this->titre_planning,
            'date_debut' => optional($this->date_debut)->toDateString(),
            'date_fin' => optional($this->date_fin)->toDateString(),
            'nom_original' => $this->nom_original,
            'est_image' => $this->estImage(),
            'date_publication' => optional($this->date_publication)->toDateTimeString(),
            'code_filiere' => $this->code_filiere,
            'code_niveau' => $this->code_niveau,
            'code_annee' => $this->code_annee,
            'filiere' => $this->filiere?->libelle_filiere,
            'niveau' => $this->niveau?->libelle_niveau,
            'annee' => $this->annee?->libelle_annee,
        ];
    }
}
