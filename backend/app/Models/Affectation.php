<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Affectation extends Model
{
    protected $table = 'enseigner';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['code_enseignant', 'code_matiere', 'code_classe'];

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(Enseignant::class, 'code_enseignant', 'code_enseignant');
    }

    public function matiere(): BelongsTo
    {
        return $this->belongsTo(Matiere::class, 'code_matiere', 'code_matiere');
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class, 'code_classe', 'code_classe');
    }
}
