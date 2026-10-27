<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Etudiant;
use App\Models\Evaluation;
use App\Models\Inscription;
use App\Models\Signalement;
use App\Services\AccesScolaire;
use App\Services\EvaluationService;
use App\Services\JournalService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EvaluationController extends Controller
{
    public function __construct(
        private readonly EvaluationService $evaluations,
        private readonly AccesScolaire $acces,
        private readonly JournalService $journal,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $utilisateur = $request->user();
        $query = Evaluation::query()->with(['matiere', 'classe', 'enseignant.utilisateur', 'annee'])->withCount('notes');

        if ($utilisateur->role === 'enseignant') {
            $query->where('code_enseignant', $utilisateur->enseignant?->code_enseignant);
        } elseif ($utilisateur->role === 'etudiant') {
            $query->whereIn('code_classe', $this->classesDe($utilisateur->etudiant?->matricule));
        } elseif ($utilisateur->role === 'parent') {
            $matricules = $this->acces->matriculesVisibles($utilisateur);
            $classes = Inscription::query()->whereIn('matricule', $matricules)->pluck('code_classe');
            $query->whereIn('code_classe', $classes);
        }

        $pourEleve = in_array($utilisateur->role, ['etudiant', 'parent'], true);
        $lignes = $query->orderBy('date_evaluation', $pourEleve ? 'asc' : 'desc')->get();

        return response()->json([
            'donnees' => $pourEleve
                ? $lignes->map(fn (Evaluation $evaluation) => $this->presenterProgramme($evaluation))->values()
                : $lignes,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'type_evaluation' => ['required', 'in:devoir,interrogation'],
            'date_evaluation' => ['required', 'date'],
            'semestre' => ['required', 'integer', 'in:1,2'],
            'code_matiere' => ['required', 'exists:matieres,code_matiere'],
            'code_classe' => ['required', 'exists:classes,code_classe'],
            'code_annee' => ['required', 'exists:annees_scolaires,code_annee'],
            'code_enseignant' => ['nullable', 'exists:enseignants,code_enseignant'],
        ]);

        $code = $donnees['code_enseignant'] ?? $request->user()->enseignant?->code_enseignant;
        if (! $code) {
            throw new AuthorizationException(
                'Choisissez l’enseignant de cette matière, ou affectez-le d’abord dans Personnes → Affectations.'
            );
        }

        if ($request->user()->role === 'enseignant' && $code !== $request->user()->enseignant?->code_enseignant) {
            throw new AuthorizationException('Vous ne pouvez créer une évaluation que pour vos affectations.');
        }

        if (! $this->evaluations->enseignantAffecte($code, $donnees['code_matiere'], $donnees['code_classe'])) {
            throw new AuthorizationException('Cet enseignant n’est pas affecté à cette matière dans cette classe.');
        }

        $evaluation = Evaluation::query()->create([
            ...$donnees,
            'code_enseignant' => $code,
            'statut_publication' => 'brouillon',
        ]);

        $this->journal->ecrire($request->user(), 'evaluation.creation', "Évaluation {$evaluation->num_evaluation} créée en brouillon.", $request);

        return response()->json(['donnees' => $evaluation->load('matiere', 'classe')], 201);
    }

    public function notes(Request $request, Evaluation $evaluation): JsonResponse
    {
        $this->voir($request, $evaluation);
        $publieeSeule = ! $this->acces->peutGererEvaluation($request->user(), $evaluation);

        if ($publieeSeule && ! $evaluation->estPubliee()) {
            abort(404, 'Évaluation introuvable.');
        }

        $inscrits = Inscription::query()
            ->with('etudiant.utilisateur')
            ->where('code_annee', $evaluation->code_annee)
            ->where('code_classe', $evaluation->code_classe)
            ->get();

        $notes = $evaluation->notes()->get()->keyBy('matricule');
        $matricules = $this->acces->matriculesVisibles($request->user());

        $lignes = $inscrits->map(function (Inscription $inscription) use ($notes, $request, $matricules) {
            if (in_array($request->user()->role, ['etudiant', 'parent'], true)
                && ! in_array($inscription->matricule, $matricules, true)) {
                return null;
            }

            return [
                'matricule' => $inscription->matricule,
                'nom_complet' => $inscription->etudiant?->utilisateur?->nomComplet(),
                'note' => isset($notes[$inscription->matricule]) ? (string) $notes[$inscription->matricule]->note : null,
            ];
        })->filter()->values();

        return response()->json([
            'donnees' => [
                'evaluation' => $evaluation->load('matiere', 'classe'),
                'notes' => $lignes,
            ],
        ]);
    }

    public function enregistrerNotes(Request $request, Evaluation $evaluation): JsonResponse
    {
        $donnees = $request->validate([
            'notes' => ['required', 'array'],
            'notes.*.matricule' => ['required', 'string'],
            'notes.*.note' => ['nullable', 'numeric', 'between:0,20'],
        ]);

        $evaluation = $this->evaluations->enregistrerNotes($request->user(), $evaluation, $donnees['notes']);

        return response()->json(['message' => 'Notes enregistrées.', 'donnees' => $evaluation]);
    }

    public function soumettre(Request $request, Evaluation $evaluation): JsonResponse
    {
        return response()->json([
            'message' => 'Évaluation soumise. Elle n’est pas encore visible des étudiants.',
            'donnees' => $this->evaluations->soumettre($request->user(), $evaluation),
        ]);
    }

    public function publier(Request $request, Evaluation $evaluation): JsonResponse
    {
        return response()->json([
            'message' => 'Notes publiées.',
            'donnees' => $this->evaluations->publier($request->user(), $evaluation),
        ]);
    }

    public function signaler(Request $request, Evaluation $evaluation): JsonResponse
    {
        $donnees = $request->validate([
            'motif' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $signalement = $this->evaluations->signaler(
            $request->user(),
            $evaluation,
            (string) $request->user()->etudiant?->matricule,
            $donnees['motif']
        );

        return response()->json(['message' => 'Signalement envoyé.', 'donnees' => $signalement], 201);
    }

    public function signalements(Request $request): JsonResponse
    {
        $query = Signalement::query()->with(['evaluation.matiere', 'evaluation.classe', 'etudiant.utilisateur']);
        $utilisateur = $request->user();

        if ($utilisateur->role === 'enseignant') {
            $query->whereHas('evaluation', fn ($q) => $q->where('code_enseignant', $utilisateur->enseignant?->code_enseignant));
        } elseif ($utilisateur->role === 'etudiant') {
            $query->where('matricule', $utilisateur->etudiant?->matricule);
        } elseif ($utilisateur->role === 'parent') {
            $query->whereIn('matricule', $this->acces->matriculesVisibles($utilisateur))
                ->whereHas('evaluation', fn ($q) => $q->where('statut_publication', 'publiee'));
        }

        return response()->json(['donnees' => $query->orderByDesc('date_signalement')->get()]);
    }

    public function traiter(Request $request, Signalement $signalement): JsonResponse
    {
        $donnees = $request->validate([
            'reponse' => ['required', 'string', 'min:3'],
            'note' => ['nullable', 'numeric', 'between:0,20'],
        ]);

        $resultat = $this->evaluations->traiterSignalement(
            $request->user(),
            $signalement,
            $donnees['reponse'],
            isset($donnees['note']) ? (string) $donnees['note'] : null
        );

        return response()->json(['message' => 'Signalement traité.', 'donnees' => $resultat]);
    }

    private function voir(Request $request, Evaluation $evaluation): void
    {
        if (! $this->acces->evaluationVisible($request->user(), $evaluation)
            && ! $this->acces->peutGererEvaluation($request->user(), $evaluation)) {
            abort(404, 'Évaluation introuvable.');
        }
    }

    private function classesDe(?string $matricule)
    {
        if (! $matricule) {
            return [];
        }

        return Inscription::query()->where('matricule', $matricule)->pluck('code_classe');
    }

    private function presenterProgramme(Evaluation $evaluation): array
    {
        return [
            'num_evaluation' => $evaluation->num_evaluation,
            'type_evaluation' => $evaluation->type_evaluation,
            'date_evaluation' => optional($evaluation->date_evaluation)->toDateString(),
            'semestre' => $evaluation->semestre,
            'statut_affichage' => $evaluation->estPubliee() ? 'notes_publiees' : 'programme',
            'matiere' => $evaluation->matiere?->libelle_matiere,
            'classe' => $evaluation->classe?->libelle_classe,
            'enseignant' => $evaluation->enseignant?->utilisateur?->nomComplet(),
            'code_annee' => $evaluation->code_annee,
        ];
    }
}
