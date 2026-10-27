<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Evaluation;
use App\Models\FicheVersement;
use App\Models\Filiere;
use App\Models\Inscription;
use App\Models\JournalActivite;
use App\Models\Matiere;
use App\Models\Niveau;
use App\Models\Notification;
use App\Models\SupportCours;
use App\Models\Signalement;
use App\Models\Utilisateur;
use App\Services\AccesScolaire;
use App\Services\EvaluationService;
use App\Services\PaiementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DashboardController extends Controller
{
    public function index(Request $request, PaiementService $paiements, EvaluationService $evaluations, AccesScolaire $acces): JsonResponse
    {
        $utilisateur = $request->user()->load(['etudiant.utilisateur', 'enseignant', 'parentRattache']);
        $nonLues = Notification::query()
            ->where('id_utilisateur', $utilisateur->id_utilisateur)
            ->where('statut_lecture', 'non_lue')
            ->count();

        $cartes = match ($utilisateur->role) {
            'administrateur' => $this->cartesAdmin(),
            'enseignant' => $this->cartesEnseignant($utilisateur),
            'etudiant' => $this->cartesEtudiant($utilisateur, $paiements),
            'parent' => $this->cartesParent($utilisateur),
            default => [],
        };

        return response()->json([
            'donnees' => [
                'utilisateur' => $utilisateur->resume(),
                'notifications_non_lues' => $nonLues,
                'cartes' => $cartes,
                'finances' => $utilisateur->role === 'administrateur' ? $paiements->syntheseFinanciere() : null,
                'annee_courante' => AnneeScolaire::query()->where('est_courante', true)->first(),
                'detail' => $this->detail($utilisateur, $paiements, $evaluations, $acces),
            ],
        ]);
    }

    private function detail(Utilisateur $utilisateur, PaiementService $paiements, EvaluationService $evaluations, AccesScolaire $acces): array
    {
        return match ($utilisateur->role) {
            'administrateur' => $this->detailAdmin($utilisateur),
            'enseignant' => $this->detailEnseignant($utilisateur),
            'etudiant' => $this->detailEtudiant($utilisateur, $evaluations, $acces),
            'parent' => $this->detailParent($utilisateur, $paiements, $evaluations),
            default => [],
        };
    }

    private function detailAdmin(Utilisateur $utilisateur): array
    {
        $activites = JournalActivite::query()
            ->with('utilisateur')
            ->orderByDesc('date_action')
            ->limit(6)
            ->get()
            ->map(fn (JournalActivite $ligne) => [
                'id' => $ligne->id_journal,
                'action' => $ligne->action,
                'description' => $ligne->description,
                'date' => optional($ligne->date_action)->toDateTimeString(),
                'auteur' => $ligne->utilisateur?->nomComplet(),
            ]);

        $paiements = FicheVersement::query()
            ->with(['etudiant.utilisateur', 'tranche'])
            ->where('statut_paiement', 'en_attente_paiement')
            ->whereNotNull('soumis_le')
            ->orderByDesc('soumis_le')
            ->limit(5)
            ->get()
            ->map(fn (FicheVersement $fiche) => [
                'numero_fiche' => $fiche->numero_fiche,
                'matricule' => $fiche->matricule,
                'etudiant' => $fiche->etudiant?->utilisateur?->nomComplet(),
                'tranche' => $fiche->tranche?->libelle_tranche,
                'montant' => $fiche->tranche?->montant_tranche,
                'mode' => $fiche->mode_justificatif,
            ]);

        return [
            'activites' => $activites,
            'paiements' => $paiements,
            'notifications' => $this->notificationsUtilisateur($utilisateur),
        ];
    }

    private function detailEnseignant(Utilisateur $utilisateur): array
    {
        $code = $utilisateur->enseignant?->code_enseignant;
        $affectations = $utilisateur->enseignant?->affectations()->with(['matiere', 'classe'])->get() ?? collect();

        $aCompleter = Evaluation::query()
            ->with(['matiere', 'classe'])
            ->where('code_enseignant', $code)
            ->whereIn('statut_publication', ['brouillon', 'non_publiee'])
            ->orderByDesc('date_evaluation')
            ->limit(6)
            ->get()
            ->map(fn (Evaluation $evaluation) => [
                'num_evaluation' => $evaluation->num_evaluation,
                'matiere' => $evaluation->matiere?->libelle_matiere,
                'classe' => $evaluation->classe?->libelle_classe,
                'type' => $evaluation->type_evaluation,
                'statut' => $evaluation->statut_publication,
            ]);

        $supports = SupportCours::query()
            ->with('matiere')
            ->where('code_enseignant', $code)
            ->orderByDesc('date_depot')
            ->limit(5)
            ->get()
            ->map(fn (SupportCours $support) => [
                'titre' => $support->titre_support,
                'matiere' => $support->matiere?->libelle_matiere,
                'date' => optional($support->date_depot)->toDateString(),
            ]);

        return [
            'affectations' => $affectations->map(fn ($ligne) => [
                'matiere' => $ligne->matiere?->libelle_matiere,
                'classe' => $ligne->classe?->libelle_classe,
            ])->values(),
            'a_completer' => $aCompleter,
            'supports' => $supports,
            'notifications' => $this->notificationsUtilisateur($utilisateur),
        ];
    }

    private function detailEtudiant(Utilisateur $utilisateur, EvaluationService $evaluations, AccesScolaire $acces): array
    {
        $releve = $this->relevePublie($evaluations, $utilisateur->etudiant);

        return [
            'classe' => $releve['classe'] ?? null,
            'filiere' => $releve['filiere'] ?? null,
            'moyenne_generale' => $releve['moyenne_generale'] ?? null,
            'moyenne_generale_libelle' => $releve['moyenne_generale_libelle'] ?? 'Moyenne non disponible',
            'matieres' => collect($releve['matieres'] ?? [])->map(fn (array $matiere) => [
                'libelle' => $matiere['libelle_matiere'],
                'moyenne' => $matiere['moyenne'],
                'moyenne_libelle' => $matiere['moyenne_libelle'],
            ])->values(),
            'resultats' => $this->dernieresNotes($releve),
            'devoirs' => $this->devoirsProgrammes($utilisateur->etudiant?->matricule),
            'supports' => $this->supportsVisibles($utilisateur, $acces),
            'notifications' => $this->notificationsUtilisateur($utilisateur),
        ];
    }

    private function detailParent(Utilisateur $utilisateur, PaiementService $paiements, EvaluationService $evaluations): array
    {
        $enfants = $utilisateur->parentRattache?->enfants()->with('utilisateur')->get() ?? collect();

        return [
            'enfants' => $enfants->map(function ($enfant) use ($paiements, $evaluations) {
                $releve = $this->relevePublie($evaluations, $enfant);
                $situation = $paiements->situation($enfant);

                return [
                    'matricule' => $enfant->matricule,
                    'nom_complet' => $enfant->utilisateur?->nomComplet(),
                    'lien' => $enfant->pivot->lien_parente,
                    'classe' => $releve['classe'] ?? null,
                    'moyenne_generale' => $releve['moyenne_generale'] ?? null,
                    'moyenne_generale_libelle' => $releve['moyenne_generale_libelle'] ?? 'Moyenne non disponible',
                    'solde' => $situation['solde'] ?? 0,
                    'montant_du' => $situation['montant_du'] ?? 0,
                    'echeances' => collect($situation['annees'] ?? [])->flatMap(fn (array $annee) => $annee['tranches'])->take(4)->values(),
                ];
            })->values(),
            'notifications' => $this->notificationsUtilisateur($utilisateur),
        ];
    }

    private function relevePublie(EvaluationService $evaluations, $etudiant): ?array
    {
        $annee = AnneeScolaire::query()->where('est_courante', true)->value('code_annee');
        if (! $etudiant || ! $annee) {
            return null;
        }

        try {
            return $evaluations->releve($etudiant, $annee, null, true);
        } catch (ValidationException) {
            return null;
        }
    }

    private function devoirsProgrammes(?string $matricule, int $limite = 6): array
    {
        if (! $matricule) {
            return [];
        }

        $classes = Inscription::query()->where('matricule', $matricule)->pluck('code_classe');

        return Evaluation::query()
            ->with(['matiere', 'classe', 'enseignant.utilisateur'])
            ->whereIn('code_classe', $classes)
            ->orderBy('date_evaluation')
            ->limit($limite)
            ->get()
            ->map(fn (Evaluation $evaluation) => [
                'num_evaluation' => $evaluation->num_evaluation,
                'type' => $evaluation->type_evaluation,
                'date' => optional($evaluation->date_evaluation)->toDateString(),
                'matiere' => $evaluation->matiere?->libelle_matiere,
                'classe' => $evaluation->classe?->libelle_classe,
                'enseignant' => $evaluation->enseignant?->utilisateur?->nomComplet(),
                'publiee' => $evaluation->estPubliee(),
            ])
            ->values()
            ->all();
    }

    private function dernieresNotes(?array $releve): array
    {
        $lignes = [];
        foreach ($releve['matieres'] ?? [] as $matiere) {
            foreach ($matiere['evaluations'] as $evaluation) {
                if ($evaluation['note'] === null) {
                    continue;
                }
                $lignes[] = [
                    'matiere' => $matiere['libelle_matiere'],
                    'type' => $evaluation['type_evaluation'],
                    'note' => $evaluation['note'],
                    'date' => $evaluation['date_evaluation'],
                ];
            }
        }

        return array_slice(array_reverse($lignes), 0, 5);
    }

    private function supportsVisibles(Utilisateur $utilisateur, AccesScolaire $acces)
    {
        $matricules = $acces->matriculesVisibles($utilisateur);

        return SupportCours::query()
            ->with('matiere')
            ->whereExists(function ($sous) use ($matricules) {
                $sous->selectRaw('1')
                    ->from('s_inscrire')
                    ->join('classes', 'classes.code_classe', '=', 's_inscrire.code_classe')
                    ->whereIn('s_inscrire.matricule', $matricules)
                    ->whereColumn('classes.code_filiere', 'supports_cours.code_filiere')
                    ->whereColumn('classes.code_niveau', 'supports_cours.code_niveau');
            })
            ->orderByDesc('date_depot')
            ->limit(4)
            ->get()
            ->map(fn (SupportCours $support) => [
                'titre' => $support->titre_support,
                'matiere' => $support->matiere?->libelle_matiere,
                'date' => optional($support->date_depot)->toDateString(),
            ]);
    }

    private function notificationsUtilisateur(Utilisateur $utilisateur)
    {
        return Notification::query()
            ->where('id_utilisateur', $utilisateur->id_utilisateur)
            ->orderByDesc('date_envoi')
            ->limit(5)
            ->get(['num_notification', 'type_notification', 'message', 'date_envoi', 'statut_lecture']);
    }

    private function cartesAdmin(): array
    {
        return [
            $this->carte('Étudiants', Utilisateur::query()->where('role', 'etudiant')->where('statut_compte', 'valide')->count(), 'ti-school'),
            $this->carte('Enseignants', Utilisateur::query()->where('role', 'enseignant')->count(), 'ti-users'),
            $this->carte('Parents en attente', Utilisateur::query()->where('role', 'parent')->where('statut_compte', 'en_attente')->count(), 'ti-user-exclamation'),
            $this->carte('Classes', Classe::query()->count(), 'ti-building'),
            $this->carte('Filières', Filiere::query()->count(), 'ti-books'),
            $this->carte('Matières', Matiere::query()->count(), 'ti-notebook'),
            $this->carte('Niveaux', Niveau::query()->count(), 'ti-stairs'),
            $this->carte('Signalements ouverts', Signalement::query()->where('statut_signalement', 'en_attente')->count(), 'ti-alert-circle'),
            $this->carte('Paiements à vérifier', FicheVersement::query()->where('statut_paiement', 'en_attente_paiement')->whereNotNull('soumis_le')->count(), 'ti-cash'),
        ];
    }

    private function cartesEnseignant(Utilisateur $utilisateur): array
    {
        $code = $utilisateur->enseignant?->code_enseignant;

        return [
            $this->carte('Affectations', $utilisateur->enseignant?->affectations()->count() ?? 0, 'ti-chalkboard'),
            $this->carte('Brouillons', Evaluation::query()->where('code_enseignant', $code)->where('statut_publication', 'brouillon')->count(), 'ti-pencil'),
            $this->carte('À publier', Evaluation::query()->where('code_enseignant', $code)->where('statut_publication', 'non_publiee')->count(), 'ti-send'),
            $this->carte('Notes publiées', Evaluation::query()->where('code_enseignant', $code)->where('statut_publication', 'publiee')->count(), 'ti-checkbox'),
            $this->carte('Signalements', Signalement::query()->where('statut_signalement', 'en_attente')->whereHas('evaluation', fn ($q) => $q->where('code_enseignant', $code))->count(), 'ti-alert-circle'),
        ];
    }

    private function cartesEtudiant(Utilisateur $utilisateur, PaiementService $paiements): array
    {
        $etudiant = $utilisateur->etudiant;
        $situation = $etudiant ? $paiements->situation($etudiant) : null;

        return [
            $this->carte('Solde restant', $situation['solde'] ?? 0, 'ti-cash', 'FCFA'),
            $this->carte('Montant dû', $situation['montant_du'] ?? 0, 'ti-report-money', 'FCFA'),
            $this->carte('Déjà réglé', $situation['montant_paye'] ?? 0, 'ti-circle-check', 'FCFA'),
        ];
    }

    private function cartesParent(Utilisateur $utilisateur): array
    {
        return [
            $this->carte('Enfants rattachés', $utilisateur->parentRattache?->enfants()->count() ?? 0, 'ti-users'),
            $this->carte('Demandes', $utilisateur->parentRattache?->demandes()->count() ?? 0, 'ti-file-text'),
        ];
    }

    private function carte(string $libelle, int $valeur, string $icone, ?string $unite = null): array
    {
        return compact('libelle', 'valeur', 'icone', 'unite');
    }
}
