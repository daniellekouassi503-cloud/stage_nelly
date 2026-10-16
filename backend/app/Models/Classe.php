<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classe extends Model
{
    protected $table = 'classes';

    protected $primaryKey = 'code_classe';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['code_classe', 'libelle_classe', 'code_filiere', 'code_niveau'];

    public function filiere(): BelongsTo
    {
        return $this->belongsTo(Filiere::class, 'code_filiere', 'code_filiere');
    }

    public function niveau(): BelongsTo
    {
        return $this->belongsTo(Niveau::class, 'code_niveau', 'code_niveau');
    }

    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class, 'code_classe', 'code_classe');
    }
}
