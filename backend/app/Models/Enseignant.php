<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enseignant extends Model
{
    protected $table = 'enseignants';

    protected $primaryKey = 'code_enseignant';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'code_enseignant',
        'id_utilisateur',
    ];

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'id_utilisateur', 'id_utilisateur');
    }

    public function affectations(): HasMany
    {
        return $this->hasMany(Affectation::class, 'code_enseignant', 'code_enseignant');
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class, 'code_enseignant', 'code_enseignant');
    }

    public function supports(): HasMany
    {
        return $this->hasMany(SupportCours::class, 'code_enseignant', 'code_enseignant');
    }
}
