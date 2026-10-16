<?php

namespace App\Services;

use App\Models\JournalActivite;
use App\Models\Utilisateur;
use Illuminate\Http\Request;

class JournalService
{
    public function ecrire(?Utilisateur $utilisateur, string $action, string $description, ?Request $request = null): void
    {
        JournalActivite::query()->create([
            'id_utilisateur' => $utilisateur?->id_utilisateur,
            'action' => $action,
            'description' => mb_substr($description, 0, 255),
            'adresse_ip' => $request?->ip(),
            'date_action' => now(),
        ]);
    }
}
