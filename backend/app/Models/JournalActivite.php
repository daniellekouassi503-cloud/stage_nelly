<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalActivite extends Model
{
    protected $table = 'journaux_activite';

    protected $primaryKey = 'id_journal';

    public $timestamps = false;

    protected $fillable = [
        'id_utilisateur',
        'action',
        'description',
        'adresse_ip',
        'date_action',
    ];

    protected function casts(): array
    {
        return ['date_action' => 'datetime'];
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'id_utilisateur', 'id_utilisateur');
    }
}
