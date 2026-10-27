<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlanningComposition;
use App\Services\PlanningCompositionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlanningCompositionController extends Controller
{
    public function __construct(private readonly PlanningCompositionService $plannings) {}

    public function index(Request $request): JsonResponse
    {
        $lignes = $this->plannings->queryVisible($request->user())
            ->orderByDesc('date_debut')
            ->get()
            ->map(fn (PlanningComposition $planning) => $planning->resume())
            ->values();

        return response()->json(['donnees' => $lignes]);
    }

    public function store(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'titre' => ['required', 'string', 'max:180'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
            'code_filiere' => ['required', 'exists:filieres,code_filiere'],
            'code_niveau' => ['required', 'exists:niveaux,code_niveau'],
            'code_annee' => ['required', 'exists:annees_scolaires,code_annee'],
            'fichier' => ['required', 'file', 'mimes:pdf,png,jpg,jpeg,webp', 'max:'.config('plateforme.taille_max_ko')],
        ], [
            'fichier.mimes' => 'Le planning doit être une image (JPG, PNG, WEBP) ou un PDF.',
        ]);

        $planning = $this->plannings->publier($request->user(), $donnees, $request->file('fichier'));

        return response()->json([
            'message' => 'Planning de composition publié pour la filière et le niveau choisis.',
            'donnees' => $planning->resume(),
        ], 201);
    }

    public function fichier(Request $request, PlanningComposition $planning): StreamedResponse
    {
        if (! $this->plannings->peutVoir($request->user(), $planning)) {
            abort(403, 'Ce planning ne concerne pas votre filière ou votre niveau.');
        }

        if (! Storage::disk('plannings')->exists($planning->fichier_planning)) {
            abort(404, 'Fichier introuvable.');
        }

        return Storage::disk('plannings')->response(
            $planning->fichier_planning,
            $planning->nom_original,
            [
                'Content-Type' => $planning->mime_fichier ?: 'application/octet-stream',
                'Content-Disposition' => ($planning->estImage() ? 'inline' : 'attachment').'; filename="'.$planning->nom_original.'"',
            ]
        );
    }

    public function destroy(Request $request, PlanningComposition $planning): JsonResponse
    {
        $this->plannings->supprimer($request->user(), $planning);

        return response()->json(['message' => 'Planning retiré.']);
    }
}
