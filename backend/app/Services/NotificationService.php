<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Utilisateur;

class NotificationService
{
    public function envoyer(int $idUtilisateur, string $type, string $message): void
    {
        Notification::query()->create([
            'id_utilisateur' => $idUtilisateur,
            'type_notification' => $type,
            'message' => mb_substr($message, 0, 255),
            'date_envoi' => now(),
            'statut_lecture' => 'non_lue',
        ]);
    }

    public function envoyerPlusieurs(iterable $identifiants, string $type, string $message): void
    {
        $deja = [];
        foreach ($identifiants as $id) {
            $id = (int) $id;
            if ($id === 0 || isset($deja[$id])) {
                continue;
            }
            $deja[$id] = true;
            $this->envoyer($id, $type, $message);
        }
    }

    public function nonLues(Utilisateur $utilisateur): int
    {
        return Notification::query()
            ->where('id_utilisateur', $utilisateur->id_utilisateur)
            ->where('statut_lecture', 'non_lue')
            ->count();
    }
}
