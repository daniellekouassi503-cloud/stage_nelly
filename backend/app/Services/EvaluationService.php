<?php

namespace App\Services;

use App\Models\Affectation;
use App\Models\AnneeScolaire;
use App\Models\Etudiant;
use App\Models\Evaluation;
use App\Models\Inscription;
use App\Models\Note;
use App\Models\Programme;
use App\Models\Signalement;
use App\Models\Utilisateur;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EvaluationService
{
    public function __construct(
        private readonly AccesScolaire $acces,
        private readonly MoyenneService $moyennes,
        private readonly NotificationService $notifications,
        private readonly JournalService $journal,
    ) {}

    public function enregistrerNotes(Utilisateur $acteur, Evaluation $evaluation, array $lignes): Evaluation
    {
        $this->exigerEdition($acteur, $evaluation);

        $autorises = Inscription::query()
            ->where('code_annee', $evaluation->code_annee)
            ->where('code_classe', $evaluation->code_classe)
            ->pluck('matricule')
            ->all();

        DB::transaction(function () use ($evaluation, $lignes, $autorises, $acteur) {
            foreach ($lignes as $ligne) {
                $matricule = $ligne['matricule'];
                if (! in_array($matricule, $autorises, true)) {
                    throw ValidationException::withMessages([
                        'notes' => "L’étudiant {$matricule} n’est pas inscrit dans cette classe pour cette année.",
                    ]);
                }

                $requete = Note::query()
                    ->where('matricule', $matricule)
                    ->where('num_evaluation', $evaluation->num_evaluation);

                if (! array_key_exists('note', $ligne) || $ligne['note'] === null || $ligne['note'] === '') {
                    $requete->delete();

                    continue;
                }

                if ($requete->exists()) {
                    $requete->update(['note' => $ligne['note']]);
                } else {
                    Note::query()->create([
                        'matricule' => $matricule,
                        'num_evaluation' => $evaluation->num_evaluation,
                        'note' => $ligne['note'],
                    ]);
                }
            }

            $this->journal->ecrire($acteur, 'notes.saisie', "Saisie des notes de l’évaluation {$evaluation->num_evaluation}.", request());
        });

        return $evaluation->fresh(['notes', 'matiere', 'classe']);
    }

    public function soumettre(Utilisateur $acteur, Evaluation $evaluation): Evaluation
    {
        $this->exigerEdition($acteur, $evaluation);

        if ($evaluation->statut_publication !== 'brouillon') {
            throw ValidationException::withMessages([
                'statut' => 'Seule une évaluation en brouillon peut être soumise.',
            ]);
        }

        if ($evaluation->notes()->count() === 0) {
            throw ValidationException::withMessages([
                'notes' => 'Saisissez au moins une note avant de soumettre l’évaluation.',
            ]);
        }

        $evaluation->statut_publication = 'non_publiee';
        $evaluation->save();
        $this->journal->ecrire($acteur, 'notes.soumission', "Soumission de l’évaluation {$evaluation->num_evaluation}.", request());

        return $evaluation->fresh();
    }

    public function publier(Utilisateur $acteur, Evaluation $evaluation): Evaluation
    {
        if (! $this->acces->peutGererEvaluation($acteur, $evaluation)) {
            throw new AuthorizationException('Vous ne pouvez pas publier cette évaluation.');
        }

        if ($evaluation->statut_publication !== 'non_publiee') {
            throw ValidationException::withMessages([
                'statut' => 'L’évaluation doit être soumise avant d’être publiée.',
            ]);
        }

        DB::transaction(function () use ($acteur, $evaluation) {
            $evaluation->statut_publication = 'publiee';
            $evaluation->date_publication = now();
            $evaluation->save();

            $message = sprintf(
                'Les notes de %s (%s) ont été publiées.',
                $evaluation->matiere?->libelle_matiere ?? $evaluation->code_matiere,
                $evaluation->classe?->libelle_classe ?? $evaluation->code_classe
            );

            $ids = $this->destinatairesClasse($evaluation);
            $this->notifications->envoyerPlusieurs($ids, 'notes', $message);
            $this->journal->ecrire($acteur, 'notes.publication', $message, request());
        });

        return $evaluation->fresh();
    }

    public function signaler(Utilisateur $acteur, Evaluation $evaluation, string $matricule, string $motif): Signalement
    {
        if ($acteur->role !== 'etudiant' || $acteur->etudiant?->matricule !== $matricule) {
            throw new AuthorizationException('Un étudiant ne peut signaler que ses propres notes.');
        }

        if (! $evaluation->estPubliee()) {
            throw new AuthorizationException('Seule une note publiée peut être signalée.');
        }

        $note = Note::query()
            ->where('matricule', $matricule)
            ->where('num_evaluation', $evaluation->num_evaluation)
            ->first();

        if (! $note) {
            throw ValidationException::withMessages([
                'note' => 'Aucune note publiée ne correspond à cette évaluation.',
            ]);
        }

        $ouvert = Signalement::query()
            ->where('matricule', $matricule)
            ->where('num_evaluation', $evaluation->num_evaluation)
            ->where('statut_signalement', 'en_attente')
            ->exists();

        if ($ouvert) {
            throw ValidationException::withMessages([
                'signalement' => 'Un signalement est déjà en attente pour cette note.',
            ]);
        }

        $signalement = Signalement::query()->create([
            'motif_signalement' => $motif,
            'date_signalement' => now(),
            'statut_signalement' => 'en_attente',
            'matricule' => $matricule,
            'num_evaluation' => $evaluation->num_evaluation,
        ]);

        $enseignantId = $evaluation->enseignant?->id_utilisateur;
        if ($enseignantId) {
            $this->notifications->envoyer(
                $enseignantId,
                'notes',
                "Signalement de note pour {$matricule}, évaluation {$evaluation->num_evaluation}."
            );
        }

        $this->journal->ecrire($acteur, 'notes.signalement', "Signalement {$signalement->num_signalement}.", request());

        return $signalement;
    }

    public function traiterSignalement(Utilisateur $acteur, Signalement $signalement, string $reponse, ?string $note): Signalement
    {
        $evaluation = $signalement->evaluation;
        if (! $evaluation || ! $this->acces->peutGererEvaluation($acteur, $evaluation)) {
            throw new AuthorizationException('Vous ne pouvez pas traiter ce signalement.');
        }

        if ($signalement->statut_signalement !== 'en_attente') {
            throw ValidationException::withMessages([
                'statut' => 'Ce signalement est déjà traité.',
            ]);
        }

        DB::transaction(function () use ($acteur, $signalement, $reponse, $note, $evaluation) {
            if ($note !== null && $note !== '') {
                Note::query()
                    ->where('matricule', $signalement->matricule)
                    ->where('num_evaluation', $evaluation->num_evaluation)
                    ->update(['note' => $note]);
            }

            $signalement->reponse_enseignant = $reponse;
            $signalement->statut_signalement = 'traite';
            $signalement->save();

            $etudiant = Etudiant::query()->find($signalement->matricule);
            if ($etudiant) {
                $this->notifications->envoyer(
                    $etudiant->id_utilisateur,
                    'notes',
                    "Votre signalement sur l’évaluation {$evaluation->num_evaluation} a été traité."
                );
            }

            $this->journal->ecrire($acteur, 'notes.signalement.traite', "Signalement {$signalement->num_signalement} traité.", request());
        });

        return $signalement->fresh();
    }

    public function releve(Etudiant $etudiant, string $codeAnnee, ?int $semestre, bool $publieesSeulement): array
    {
        $inscription = Inscription::query()
            ->with('classe.filiere', 'classe.niveau', 'annee')
            ->where('matricule', $etudiant->matricule)
            ->where('code_annee', $codeAnnee)
            ->first();

        if (! $inscription) {
            throw ValidationException::withMessages([
                'code_annee' => 'Aucune inscription pour cette année scolaire.',
            ]);
        }

        $classe = $inscription->classe;
        $coefficients = Programme::query()
            ->where('code_filiere', $classe->code_filiere)
            ->where('code_niveau', $classe->code_niveau)
            ->get()
            ->keyBy('code_matiere');

        $evaluations = Evaluation::query()
            ->with('matiere')
            ->where('code_classe', $classe->code_classe)
            ->where('code_annee', $codeAnnee)
            ->when($semestre, fn ($q) => $q->where('semestre', $semestre))
            ->when($publieesSeulement, fn ($q) => $q->where('statut_publication', 'publiee'))
            ->orderBy('date_evaluation')
            ->get();

        $notes = Note::query()
            ->where('matricule', $etudiant->matricule)
            ->whereIn('num_evaluation', $evaluations->pluck('num_evaluation'))
            ->get()
            ->keyBy('num_evaluation');

        $parMatiere = [];
        foreach ($evaluations as $evaluation) {
            $code = $evaluation->code_matiere;
            $parMatiere[$code] ??= [
                'code_matiere' => $code,
                'libelle_matiere' => $evaluation->matiere?->libelle_matiere ?? $code,
                'coefficient' => (string) ($coefficients[$code]->coefficient ?? '1.00'),
                'evaluations' => [],
                'notes' => [],
            ];

            $valeur = $notes[$evaluation->num_evaluation]->note ?? null;
            $parMatiere[$code]['evaluations'][] = [
                'num_evaluation' => $evaluation->num_evaluation,
                'type_evaluation' => $evaluation->type_evaluation,
                'date_evaluation' => optional($evaluation->date_evaluation)->toDateString(),
                'semestre' => $evaluation->semestre,
                'statut_publication' => $evaluation->statut_publication,
                'note' => $valeur !== null ? (string) $valeur : null,
            ];

            if ($valeur !== null && (! $publieesSeulement || $evaluation->estPubliee())) {
                $parMatiere[$code]['notes'][] = (string) $valeur;
            }
        }

        $lignes = [];
        $pourGenerale = [];
        foreach ($parMatiere as $bloc) {
            $moyenne = $this->moyennes->moyenneMatiere($bloc['notes']);
            $lignes[] = [
                'code_matiere' => $bloc['code_matiere'],
                'libelle_matiere' => $bloc['libelle_matiere'],
                'coefficient' => $bloc['coefficient'],
                'moyenne' => $moyenne,
                'moyenne_libelle' => $this->moyennes->libelle($moyenne),
                'evaluations' => $bloc['evaluations'],
            ];
            $pourGenerale[] = [
                'moyenne' => $moyenne,
                'coefficient' => $bloc['coefficient'],
            ];
        }

        $generale = $this->moyennes->moyenneGenerale($pourGenerale);

        return [
            'matricule' => $etudiant->matricule,
            'nom_complet' => $etudiant->utilisateur?->nomComplet(),
            'code_annee' => $codeAnnee,
            'libelle_annee' => $inscription->annee?->libelle_annee,
            'semestre' => $semestre,
            'classe' => $classe->libelle_classe,
            'filiere' => $classe->filiere?->libelle_filiere,
            'niveau' => $classe->niveau?->libelle_niveau,
            'matieres' => $lignes,
            'moyenne_generale' => $generale,
            'moyenne_generale_libelle' => $this->moyennes->libelle($generale),
            'publiees_seulement' => $publieesSeulement,
        ];
    }

    public function enseignantAffecte(string $codeEnseignant, string $codeMatiere, string $codeClasse): bool
    {
        return Affectation::query()
            ->where('code_enseignant', $codeEnseignant)
            ->where('code_matiere', $codeMatiere)
            ->where('code_classe', $codeClasse)
            ->exists();
    }

    private function exigerEdition(Utilisateur $acteur, Evaluation $evaluation): void
    {
        if (! $this->acces->peutGererEvaluation($acteur, $evaluation)) {
            throw new AuthorizationException('Vous ne pouvez pas modifier cette évaluation.');
        }

        if ($evaluation->estPubliee()) {
            throw new AuthorizationException('Une évaluation publiée ne se modifie que par le traitement d’un signalement.');
        }
    }

    /**
     * @return array<int, int>
     */
    private function destinatairesClasse(Evaluation $evaluation): array
    {
        $etudiants = Etudiant::query()
            ->with('parents.utilisateur')
            ->whereHas('inscriptions', function ($q) use ($evaluation) {
                $q->where('code_annee', $evaluation->code_annee)
                    ->where('code_classe', $evaluation->code_classe);
            })
            ->get();

        $ids = [];
        foreach ($etudiants as $etudiant) {
            $ids[] = $etudiant->id_utilisateur;
            foreach ($etudiant->parents as $parent) {
                if ($parent->utilisateur?->statut_compte === 'valide') {
                    $ids[] = $parent->utilisateur->id_utilisateur;
                }
            }
        }

        return $ids;
    }

    public function anneeCourante(): ?AnneeScolaire
    {
        return AnneeScolaire::query()->where('est_courante', true)->first()
            ?? AnneeScolaire::query()->orderByDesc('code_annee')->first();
    }
}
