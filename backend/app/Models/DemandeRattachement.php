<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemandeRattachement extends Model
{
    protected $table = 'demandes_rattachement';

    protected $primaryKey = 'id_demande';

    public $timestamps = false;

    protected $fillable = [
        'code_parent',
        'matricule',
        'lien_parente',
        'statut',
        'date_demande',
    ];

    public function parentTuteur(): BelongsTo
    {
        return $this->belongsTo(ParentTuteur::class, 'code_parent', 'code_parent');
    }

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(Etudiant::class, 'matricule', 'matricule');
    }
}
