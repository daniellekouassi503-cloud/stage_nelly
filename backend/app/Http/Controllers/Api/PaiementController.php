<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Etudiant;
use App\Models\FicheVersement;
use App\Models\Tranche;
use App\Services\AccesScolaire;
use App\Services\DocumentPdfService;
use App\Services\JournalService;
use App\Services\PaiementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PaiementController extends Controller
{
    public function __construct(
        private readonly PaiementService $paiements,
        private readonly AccesScolaire $acces,
        private readonly DocumentPdfService $pdf,
        private readonly JournalService $journal,
    ) {}

    public function situation(Request $request): JsonResponse
    {
        return response()->json(['donnees' => $this->paiements->situation($this->etudiant($request))]);
    }

    public function tranches(): JsonResponse
    {
        return response()->json([
            'donnees' => Tranche::query()->with(['filiere', 'niveau', 'annee'])->orderBy('date_limite')->get(),
        ]);
    }

    public function enregistrerTranche(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'code_tranche' => ['required', 'string', 'max:20', 'unique:tranches,code_tranche'],
            'libelle_tranche' => ['required', 'string', 'max:50'],
            'montant_tranche' => ['required', 'integer', 'min:1'],
            'date_limite' => ['required', 'date'],
            'code_filiere' => ['required', 'exists:filieres,code_filiere'],
            'code_niveau' => ['required', 'exists:niveaux,code_niveau'],
            'code_annee' => ['required', 'exists:annees_scolaires,code_annee'],
        ]);

        $tranche = Tranche::query()->create($donnees);
        $this->journal->ecrire($request->user(), 'tranche.creation', "Tranche {$tranche->code_tranche} créée.", $request);

        return response()->json(['donnees' => $tranche], 201);
    }

    public function generer(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'code_tranche' => ['required', 'exists:tranches,code_tranche'],
            'matricule' => ['nullable', 'exists:etudiants,matricule'],
        ]);

        $fiche = $this->paiements->genererFiche($request->user(), $this->etudiant($request, $donnees['matricule'] ?? null), $donnees['code_tranche']);

        return response()->json([
            'message' => 'Fiche générée. Elle ne constitue pas un paiement.',
            'donnees' => $fiche->load('tranche'),
        ], 201);
    }

    public function fiches(Request $request): JsonResponse
    {
        $query = FicheVersement::query()->with(['etudiant.utilisateur', 'tranche', 'verificateur']);
        $utilisateur = $request->user();

        if ($utilisateur->role !== 'administrateur') {
            $matricules = $utilisateur->role === 'etudiant'
                ? [$utilisateur->etudiant?->matricule]
                : $this->acces->matriculesVisibles($utilisateur);
            $query->whereIn('matricule', array_filter($matricules));
        }

        return response()->json(['donnees' => $query->orderByDesc('date_generation')->get()]);
    }

    public function pdfFiche(Request $request, FicheVersement $fiche): Response
    {
        $this->autoriserFiche($request, $fiche);

        return $this->pdf->fiche($fiche);
    }

    public function recu(Request $request, FicheVersement $fiche): Response
    {
        $this->autoriserFiche($request, $fiche);
        if ($fiche->statut_paiement !== 'valide' || ! $fiche->numero_recu) {
            abort(403, 'Le reçu n’est disponible qu’après validation administrative.');
        }

        return $this->pdf->recu($fiche);
    }

    public function simuler(Request $request, FicheVersement $fiche): JsonResponse
    {
        $donnees = $request->validate([
            'confirmation' => ['required', 'string'],
        ]);

        $fiche = $this->paiements->simuler($request->user(), $fiche, $donnees['confirmation']);

        return response()->json([
            'message' => 'Justificatif simulé enregistré. Le paiement reste en attente de validation. Aucun argent n’a été encaissé.',
            'donnees' => $fiche,
        ]);
    }

    public function decider(Request $request, FicheVersement $fiche): JsonResponse
    {
        $donnees = $request->validate([
            'decision' => ['required', 'in:valide,rejete'],
            'motif_rejet' => ['nullable', 'string', 'max:255'],
            'date_paiement' => ['nullable', 'date'],
        ]);

        $fiche = $this->paiements->decider(
            $request->user(),
            $fiche,
            $donnees['decision'],
            $donnees['motif_rejet'] ?? null,
            $donnees['date_paiement'] ?? null
        );

        return response()->json(['message' => 'Décision enregistrée.', 'donnees' => $fiche]);
    }

    public function finances(): JsonResponse
    {
        return response()->json(['donnees' => $this->paiements->syntheseFinanciere()]);
    }

    private function etudiant(Request $request, ?string $matricule = null): Etudiant
    {
        $utilisateur = $request->user();
        $matricule ??= $request->string('matricule')->toString();

        if ($utilisateur->role === 'etudiant') {
            $propre = $utilisateur->etudiant?->matricule ?? '';
            if ($matricule !== '' && $matricule !== $propre) {
                abort(403, 'Vous ne pouvez pas consulter un autre étudiant.');
            }
            $matricule = $propre;
        }

        if ($matricule === '') {
            abort(422, 'Le matricule est obligatoire.');
        }

        $etudiant = Etudiant::query()->find($matricule);
        if (! $etudiant) {
            abort(404, 'Étudiant introuvable.');
        }

        $this->acces->exigerEtudiant($utilisateur, $etudiant);

        return $etudiant;
    }

    private function autoriserFiche(Request $request, FicheVersement $fiche): void
    {
        if (! $this->acces->peutVoirFiche($request->user(), $fiche)) {
            abort(403, 'Vous ne pouvez pas accéder à cette fiche.');
        }
    }
}
