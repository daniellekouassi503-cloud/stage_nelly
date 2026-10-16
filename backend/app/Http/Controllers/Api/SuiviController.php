<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JournalActivite;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SuiviController extends Controller
{
    public function notifications(Request $request): JsonResponse
    {
        $lignes = Notification::query()
            ->where('id_utilisateur', $request->user()->id_utilisateur)
            ->orderByDesc('date_envoi')
            ->limit(100)
            ->get();

        return response()->json(['donnees' => $lignes]);
    }

    public function lire(Request $request, Notification $notification): JsonResponse
    {
        if ($notification->id_utilisateur !== $request->user()->id_utilisateur) {
            abort(403, 'Cette notification ne vous appartient pas.');
        }

        $notification->statut_lecture = 'lue';
        $notification->save();

        return response()->json(['donnees' => $notification]);
    }

    public function toutLire(Request $request): JsonResponse
    {
        Notification::query()
            ->where('id_utilisateur', $request->user()->id_utilisateur)
            ->where('statut_lecture', 'non_lue')
            ->update(['statut_lecture' => 'lue']);

        return response()->json(['message' => 'Notifications marquées comme lues.']);
    }

    public function journal(): JsonResponse
    {
        $lignes = JournalActivite::query()->with('utilisateur')->orderByDesc('date_action')->limit(200)->get()
            ->map(fn (JournalActivite $journal) => [
                'id_journal' => $journal->id_journal,
                'action' => $journal->action,
                'description' => $journal->description,
                'date_action' => optional($journal->date_action)->toDateTimeString(),
                'auteur' => $journal->utilisateur?->nomComplet(),
            ]);

        return response()->json(['donnees' => $lignes]);
    }
}
