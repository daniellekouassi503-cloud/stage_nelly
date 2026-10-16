<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportCours;
use App\Services\AccesScolaire;
use App\Services\CompteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupportController extends Controller
{
    public function __construct(
        private readonly CompteService $comptes,
        private readonly AccesScolaire $acces,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = SupportCours::query()->with(['matiere', 'filiere', 'niveau', 'enseignant.utilisateur']);
        $utilisateur = $request->user();

        if ($utilisateur->role === 'enseignant') {
            $code = $utilisateur->enseignant?->code_enseignant;
            $query->where('code_enseignant', $code);
        } elseif (in_array($utilisateur->role, ['etudiant', 'parent'], true)) {
            $matricules = $this->acces->matriculesVisibles($utilisateur);
            $query->whereExists(function ($sous) use ($matricules) {
                $sous->selectRaw('1')
                    ->from('s_inscrire')
                    ->join('classes', 'classes.code_classe', '=', 's_inscrire.code_classe')
                    ->whereIn('s_inscrire.matricule', $matricules)
                    ->whereColumn('classes.code_filiere', 'supports_cours.code_filiere')
                    ->whereColumn('classes.code_niveau', 'supports_cours.code_niveau');
            });
        }

        return response()->json(['donnees' => $query->orderByDesc('date_depot')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $extensions = implode(',', config('plateforme.fichiers_autorises'));
        $donnees = $request->validate([
            'titre' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'code_matiere' => ['required', 'exists:matieres,code_matiere'],
            'code_filiere' => ['required', 'exists:filieres,code_filiere'],
            'code_niveau' => ['required', 'exists:niveaux,code_niveau'],
            'code_enseignant' => ['nullable', 'exists:enseignants,code_enseignant'],
            'fichier' => ['required', 'file', 'mimes:'.$extensions, 'max:'.config('plateforme.taille_max_ko')],
        ]);

        $support = $this->comptes->deposerSupport($request->user(), $donnees, $request->file('fichier'));

        return response()->json([
            'message' => 'Support publié.',
            'donnees' => $support->load('matiere', 'filiere', 'niveau'),
        ], 201);
    }

    public function telecharger(Request $request, SupportCours $support): StreamedResponse
    {
        if (! $this->acces->peutVoirSupport($request->user(), $support)) {
            abort(403, 'Ce support ne vous est pas destiné.');
        }

        if (! Storage::disk('supports')->exists($support->fichier_support)) {
            abort(404, 'Fichier introuvable.');
        }

        return Storage::disk('supports')->download($support->fichier_support, $support->nom_original);
    }
}
