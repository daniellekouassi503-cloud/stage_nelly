<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParentTuteur extends Model
{
    protected $table = 'parents';

    protected $primaryKey = 'code_parent';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'code_parent',
        'id_utilisateur',
    ];

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'id_utilisateur', 'id_utilisateur');
    }

    public function enfants(): BelongsToMany
    {
        return $this->belongsToMany(Etudiant::class, 'rattacher', 'code_parent', 'matricule')
            ->withPivot('lien_parente');
    }

    public function demandes(): HasMany
    {
        return $this->hasMany(DemandeRattachement::class, 'code_parent', 'code_parent');
    }
}
