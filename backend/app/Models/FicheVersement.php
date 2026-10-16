<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FicheVersement extends Model
{
    protected $table = 'fiches_versement';

    protected $primaryKey = 'numero_fiche';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'numero_fiche',
        'date_generation',
        'statut_paiement',
        'date_paiement',
        'date_verification',
        'motif_rejet',
        'numero_recu',
        'matricule',
        'code_tranche',
        'id_utilisateur',
        'mode_justificatif',
        'reference_simulation',
        'soumis_le',
    ];

    protected function casts(): array
    {
        return [
            'date_generation' => 'datetime',
            'date_paiement' => 'date',
            'date_verification' => 'datetime',
            'soumis_le' => 'datetime',
        ];
    }

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(Etudiant::class, 'matricule', 'matricule');
    }

    public function tranche(): BelongsTo
    {
        return $this->belongsTo(Tranche::class, 'code_tranche', 'code_tranche');
    }

    public function verificateur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'id_utilisateur', 'id_utilisateur');
    }
}
