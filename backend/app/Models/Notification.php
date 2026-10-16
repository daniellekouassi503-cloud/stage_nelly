<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $table = 'notifications';

    protected $primaryKey = 'num_notification';

    public $timestamps = false;

    protected $fillable = [
        'type_notification',
        'message',
        'date_envoi',
        'statut_lecture',
        'id_utilisateur',
    ];

    protected function casts(): array
    {
        return ['date_envoi' => 'datetime'];
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'id_utilisateur', 'id_utilisateur');
    }
}
