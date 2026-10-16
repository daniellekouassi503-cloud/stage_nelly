<?php

namespace App\Services;

use App\Models\Etudiant;
use App\Models\FicheVersement;
use App\Models\Inscription;
use App\Models\Tranche;
use App\Models\Utilisateur;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaiementService
{
    public function __construct(
        private readonly AccesScolaire $acces,
        private readonly NotificationService $notifications,
        private readonly JournalService $journal,
    ) {}

    public function situation(Etudiant $etudiant): array
    {
        $inscriptions = Inscription::query()
            ->with('classe', 'annee')
            ->where('matricule', $etudiant->matricule)
            ->get();

        $annees = [];
        $totalDu = 0;
        $totalPaye = 0;

        foreach ($inscriptions as $inscription) {
            $tranches = Tranche::query()
                ->where('code_filiere', $inscription->classe->code_filiere)
                ->where('code_niveau', $inscription->classe->code_niveau)
                ->where('code_annee', $inscription->code_annee)
                ->orderBy('date_limite')
                ->get();

            $lignes = [];
            $du = 0;
            $paye = 0;

            foreach ($tranches as $tranche) {
                $fiche = FicheVersement::query()
                    ->where('matricule', $etudiant->matricule)
                    ->where('code_tranche', $tranche->code_tranche)
                    ->whereIn('statut_paiement', ['en_attente_paiement', 'valide'])
                    ->latest('date_generation')
                    ->first();

                $du += $tranche->montant_tranche;
                if ($fiche?->statut_paiement === 'valide') {
                    $paye += $tranche->montant_tranche;
                }

                $lignes[] = [
                    'code_tranche' => $tranche->code_tranche,
                    'libelle_tranche' => $tranche->libelle_tranche,
                    'montant_tranche' => $tranche->montant_tranche,
                    'date_limite' => optional($tranche->date_limite)->toDateString(),
                    'statut' => $fiche->statut_paiement ?? 'non_demandee',
                    'numero_fiche' => $fiche->numero_fiche ?? null,
                    'mode_justificatif' => $fiche->mode_justificatif ?? null,
                    'numero_recu' => $fiche->numero_recu ?? null,
                    'simulation' => ($fiche->mode_justificatif ?? null) === 'simulation',
                ];
            }

            $totalDu += $du;
            $totalPaye += $paye;
            $annees[] = [
                'code_annee' => $inscription->code_annee,
                'libelle_annee' => $inscription->annee?->libelle_annee,
                'classe' => $inscription->classe->libelle_classe,
                'montant_du' => $du,
                'montant_paye' => $paye,
                'solde' => $du - $paye,
                'tranches' => $lignes,
            ];
        }

        return [
            'matricule' => $etudiant->matricule,
            'nom_complet' => $etudiant->utilisateur?->nomComplet(),
            'montant_du' => $totalDu,
            'montant_paye' => $totalPaye,
            'solde' => $totalDu - $totalPaye,
            'devise' => 'FCFA',
            'annees' => $annees,
            'avertissement' => 'Le téléchargement d’une fiche de versement ne signifie pas que la tranche est payée. Le paiement réel s’effectue à la banque. La simulation disponible ici n’encaisse aucun argent.',
        ];
    }

    public function genererFiche(Utilisateur $acteur, Etudiant $etudiant, string $codeTranche): FicheVersement
    {
        $this->acces->exigerEtudiant($acteur, $etudiant);
        if (! in_array($acteur->role, ['etudiant', 'parent', 'administrateur'], true)) {
            throw new AuthorizationException('Vous ne pouvez pas demander une fiche de versement.');
        }

        return DB::transaction(function () use ($acteur, $etudiant, $codeTranche) {
            $tranche = Tranche::query()->where('code_tranche', $codeTranche)->lockForUpdate()->first();
            if (! $tranche) {
                throw ValidationException::withMessages(['code_tranche' => 'Tranche introuvable.']);
            }

            $inscription = Inscription::query()
                ->with('classe')
                ->where('matricule', $etudiant->matricule)
                ->where('code_annee', $tranche->code_annee)
                ->first();

            if (! $inscription
                || $inscription->classe->code_filiere !== $tranche->code_filiere
                || $inscription->classe->code_niveau !== $tranche->code_niveau) {
                throw ValidationException::withMessages([
                    'code_tranche' => 'Cette tranche ne correspond pas à l’inscription de l’étudiant.',
                ]);
            }

            $existante = FicheVersement::query()
                ->where('matricule', $etudiant->matricule)
                ->where('code_tranche', $tranche->code_tranche)
                ->whereIn('statut_paiement', ['en_attente_paiement', 'valide'])
                ->lockForUpdate()
                ->first();

            if ($existante?->statut_paiement === 'valide') {
                throw ValidationException::withMessages([
                    'code_tranche' => 'Cette tranche est déjà réglée.',
                ]);
            }

            if ($existante) {
                return $existante;
            }

            $fiche = FicheVersement::query()->create([
                'numero_fiche' => $this->prochainNumero('FV'),
                'date_generation' => now(),
                'statut_paiement' => 'en_attente_paiement',
                'matricule' => $etudiant->matricule,
                'code_tranche' => $tranche->code_tranche,
                'mode_justificatif' => 'aucun',
            ]);

            $this->journal->ecrire($acteur, 'paiement.fiche', "Fiche {$fiche->numero_fiche} générée.", request());

            return $fiche->fresh('tranche');
        });
    }

    public function simuler(Utilisateur $acteur, FicheVersement $fiche, string $confirmation): FicheVersement
    {
        if (! config('plateforme.simulation_active')) {
            throw ValidationException::withMessages([
                'simulation' => 'La simulation de paiement est désactivée.',
            ]);
        }

        $this->acces->exigerEtudiant($acteur, $fiche->etudiant);
        if (! in_array($acteur->role, ['etudiant', 'parent', 'administrateur'], true)) {
            throw new AuthorizationException('Action non autorisée.');
        }

        if ($confirmation !== config('plateforme.simulation_confirmation')) {
            throw ValidationException::withMessages([
                'confirmation' => 'Saisissez exactement le mot SIMULATION. Un simple clic ne confirme pas le dépôt.',
            ]);
        }

        return DB::transaction(function () use ($acteur, $fiche) {
            $fiche = FicheVersement::query()->where('numero_fiche', $fiche->numero_fiche)->lockForUpdate()->firstOrFail();

            if ($fiche->statut_paiement !== 'en_attente_paiement') {
                throw ValidationException::withMessages([
                    'statut' => 'Cette fiche n’est plus en attente de paiement.',
                ]);
            }

            if ($fiche->reference_simulation) {
                throw ValidationException::withMessages([
                    'simulation' => 'Un justificatif simulé a déjà été déposé pour cette fiche.',
                ]);
            }

            $fiche->mode_justificatif = 'simulation';
            $fiche->reference_simulation = 'SIM-'.$fiche->numero_fiche;
            $fiche->soumis_le = now();
            $fiche->save();

            $this->journal->ecrire(
                $acteur,
                'paiement.simulation',
                "Justificatif simulé déposé pour {$fiche->numero_fiche}. Aucun encaissement.",
                request()
            );

            return $fiche->fresh('tranche');
        });
    }

    public function decider(Utilisateur $admin, FicheVersement $fiche, string $decision, ?string $motif, ?string $datePaiement): FicheVersement
    {
        if ($admin->role !== 'administrateur' || $admin->statut_compte !== 'valide') {
            throw new AuthorizationException('Seul un administrateur peut valider un paiement.');
        }

        return DB::transaction(function () use ($admin, $fiche, $decision, $motif, $datePaiement) {
            $fiche = FicheVersement::query()->where('numero_fiche', $fiche->numero_fiche)->lockForUpdate()->firstOrFail();

            if ($fiche->statut_paiement !== 'en_attente_paiement') {
                throw ValidationException::withMessages([
                    'statut' => 'Cette fiche a déjà été vérifiée.',
                ]);
            }

            if ($decision === 'rejete' && blank($motif)) {
                throw ValidationException::withMessages([
                    'motif_rejet' => 'Le motif de rejet est obligatoire.',
                ]);
            }

            if ($decision === 'valide' && blank($datePaiement)) {
                throw ValidationException::withMessages([
                    'date_paiement' => 'La date du paiement est obligatoire pour valider.',
                ]);
            }

            $fiche->statut_paiement = $decision === 'valide' ? 'valide' : 'rejete';
            $fiche->date_verification = now();
            $fiche->id_utilisateur = $admin->id_utilisateur;
            $fiche->motif_rejet = $decision === 'rejete' ? $motif : null;
            $fiche->date_paiement = $decision === 'valide' ? $datePaiement : null;

            if ($decision === 'valide' && $fiche->mode_justificatif === 'aucun') {
                $fiche->mode_justificatif = 'banque';
            }

            if ($decision === 'valide') {
                $fiche->numero_recu = $this->prochainNumero('RC');
            }

            $fiche->save();

            $etudiant = $fiche->etudiant()->with('parents.utilisateur')->first();
            $message = $decision === 'valide'
                ? "Paiement validé pour la fiche {$fiche->numero_fiche}. Le reçu est disponible."
                : "Paiement rejeté pour la fiche {$fiche->numero_fiche}. Motif : {$motif}";

            $ids = [$etudiant->id_utilisateur];
            foreach ($etudiant->parents as $parent) {
                if ($parent->utilisateur?->statut_compte === 'valide') {
                    $ids[] = $parent->utilisateur->id_utilisateur;
                }
            }
            $this->notifications->envoyerPlusieurs($ids, 'paiement', $message);
            $this->journal->ecrire($admin, 'paiement.decision', $message, request());

            return $fiche->fresh(['tranche', 'verificateur']);
        });
    }

    public function syntheseFinanciere(): array
    {
        $tranches = Tranche::query()->get();
        $du = 0;
        $encaisse = 0;
        $enAttente = 0;

        $inscriptions = Inscription::query()->with('classe')->get();
        foreach ($inscriptions as $inscription) {
            foreach ($tranches as $tranche) {
                if ($tranche->code_annee === $inscription->code_annee
                    && $tranche->code_filiere === $inscription->classe->code_filiere
                    && $tranche->code_niveau === $inscription->classe->code_niveau) {
                    $du += $tranche->montant_tranche;
                }
            }
        }

        $fiches = FicheVersement::query()->with('tranche')->get();
        foreach ($fiches as $fiche) {
            if ($fiche->statut_paiement === 'valide') {
                $encaisse += $fiche->tranche->montant_tranche;
            }
            if ($fiche->statut_paiement === 'en_attente_paiement') {
                $enAttente += $fiche->tranche->montant_tranche;
            }
        }

        return [
            'montant_du' => $du,
            'montant_encaisse' => $encaisse,
            'montant_en_attente' => $enAttente,
            'solde_restant' => $du - $encaisse,
            'devise' => 'FCFA',
            'fiches_validees' => $fiches->where('statut_paiement', 'valide')->count(),
            'fiches_rejetees' => $fiches->where('statut_paiement', 'rejete')->count(),
            'fiches_en_attente' => $fiches->where('statut_paiement', 'en_attente_paiement')->count(),
            'dont_simulations_en_attente' => $fiches
                ->where('statut_paiement', 'en_attente_paiement')
                ->where('mode_justificatif', 'simulation')
                ->count(),
            'passerelle_reelle' => false,
        ];
    }

    private function prochainNumero(string $prefixe): string
    {
        $annee = now()->format('Y');
        $base = $prefixe.'-'.$annee.'-';
        $colonne = $prefixe === 'RC' ? 'numero_recu' : 'numero_fiche';
        $dernier = FicheVersement::query()
            ->where($colonne, 'like', $base.'%')
            ->orderByDesc($colonne)
            ->value($colonne);

        $suivant = 1;
        if ($dernier && preg_match('/(\d+)$/', $dernier, $m)) {
            $suivant = ((int) $m[1]) + 1;
        }

        return $base.str_pad((string) $suivant, 5, '0', STR_PAD_LEFT);
    }
}
