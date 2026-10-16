<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Signalement extends Model
{
    protected $table = 'signalements';

    protected $primaryKey = 'num_signalement';

    public $timestamps = false;

    protected $fillable = [
        'motif_signalement',
        'date_signalement',
        'statut_signalement',
        'reponse_enseignant',
        'matricule',
        'num_evaluation',
    ];

    protected function casts(): array
    {
        return ['date_signalement' => 'datetime'];
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class, 'num_evaluation', 'num_evaluation');
    }

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(Etudiant::class, 'matricule', 'matricule');
    }
}
