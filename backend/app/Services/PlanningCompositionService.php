<?php

namespace App\Services;

use App\Models\Affectation;
use App\Models\Etudiant;
use App\Models\Inscription;
use App\Models\ParentTuteur;
use App\Models\PlanningComposition;
use App\Models\Utilisateur;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PlanningCompositionService
{
    public function __construct(
        private readonly CompteService $comptes,
        private readonly NotificationService $notifications,
        private readonly JournalService $journal,
    ) {}

    public function publier(Utilisateur $admin, array $donnees, UploadedFile $fichier): PlanningComposition
    {
        if ($admin->role !== 'administrateur') {
            throw new AuthorizationException('Seul l’administrateur peut publier un planning de composition.');
        }

        $debut = \Carbon\Carbon::parse($donnees['date_debut'])->startOfDay();
        $fin = \Carbon\Carbon::parse($donnees['date_fin'])->startOfDay();
        if ($fin->lt($debut) || $debut->diffInDays($fin) > 5) {
            throw ValidationException::withMessages([
                'date_fin' => 'Le planning va du lundi au samedi au maximum (6 jours).',
            ]);
        }

        return DB::transaction(function () use ($admin, $donnees, $fichier) {
            $extension = strtolower($fichier->getClientOriginalExtension() ?: 'bin');
            $nomStocke = Str::uuid()->toString().'.'.$extension;
            $chemin = $fichier->storeAs('', $nomStocke, 'plannings');

            $planning = PlanningComposition::query()->create([
                'code_planning' => $this->comptes->prochainCode('PLN', PlanningComposition::class, 'code_planning'),
                'titre_planning' => $donnees['titre'],
                'date_debut' => $donnees['date_debut'],
                'date_fin' => $donnees['date_fin'],
                'fichier_planning' => $chemin,
                'nom_original' => $fichier->getClientOriginalName(),
                'mime_fichier' => $fichier->getMimeType(),
                'date_publication' => now(),
                'code_filiere' => $donnees['code_filiere'],
                'code_niveau' => $donnees['code_niveau'],
                'code_annee' => $donnees['code_annee'],
                'id_utilisateur' => $admin->id_utilisateur,
            ]);

            $this->notifierPublic($planning);
            $this->journal->ecrire(
                $admin,
                'planning.publication',
                "Planning {$planning->code_planning} publié pour {$planning->code_filiere} / {$planning->code_niveau}.",
                request()
            );

            return $planning->fresh(['filiere', 'niveau', 'annee']);
        });
    }

    public function supprimer(Utilisateur $admin, PlanningComposition $planning): void
    {
        if ($admin->role !== 'administrateur') {
            throw new AuthorizationException('Seul l’administrateur peut retirer un planning de composition.');
        }

        DB::transaction(function () use ($admin, $planning) {
            $code = $planning->code_planning;
            if ($planning->fichier_planning && Storage::disk('plannings')->exists($planning->fichier_planning)) {
                Storage::disk('plannings')->delete($planning->fichier_planning);
            }
            $planning->delete();
            $this->journal->ecrire($admin, 'planning.suppression', "Planning {$code} retiré.", request());
        });
    }

    public function queryVisible(Utilisateur $utilisateur)
    {
        $query = PlanningComposition::query()->with(['filiere', 'niveau', 'annee']);

        if ($utilisateur->role === 'administrateur') {
            return $query;
        }

        $cibles = $this->cibles($utilisateur);
        if ($cibles === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($q) use ($cibles) {
            foreach ($cibles as $cible) {
                $q->orWhere(function ($ligne) use ($cible) {
                    $ligne->where('code_filiere', $cible['code_filiere'])
                        ->where('code_niveau', $cible['code_niveau']);
                });
            }
        });
    }

    public function peutVoir(Utilisateur $utilisateur, PlanningComposition $planning): bool
    {
        if ($utilisateur->role === 'administrateur') {
            return true;
        }

        foreach ($this->cibles($utilisateur) as $cible) {
            if ($cible['code_filiere'] === $planning->code_filiere && $cible['code_niveau'] === $planning->code_niveau) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, array{code_filiere: string, code_niveau: string}>
     */
    private function cibles(Utilisateur $utilisateur): array
    {
        if ($utilisateur->role === 'enseignant') {
            return Affectation::query()
                ->with('classe')
                ->where('code_enseignant', $utilisateur->enseignant?->code_enseignant)
                ->get()
                ->map(fn (Affectation $a) => [
                    'code_filiere' => $a->classe?->code_filiere,
                    'code_niveau' => $a->classe?->code_niveau,
                ])
                ->filter(fn (array $ligne) => $ligne['code_filiere'] && $ligne['code_niveau'])
                ->unique(fn (array $ligne) => $ligne['code_filiere'].'|'.$ligne['code_niveau'])
                ->values()
                ->all();
        }

        $matricules = match ($utilisateur->role) {
            'etudiant' => array_filter([$utilisateur->etudiant?->matricule]),
            'parent' => $utilisateur->statut_compte === 'valide'
                ? ($utilisateur->parentRattache?->enfants()->pluck('etudiants.matricule')->all() ?? [])
                : [],
            default => [],
        };

        if ($matricules === []) {
            return [];
        }

        return Inscription::query()
            ->with('classe')
            ->whereIn('matricule', $matricules)
            ->get()
            ->map(fn (Inscription $i) => [
                'code_filiere' => $i->classe?->code_filiere,
                'code_niveau' => $i->classe?->code_niveau,
            ])
            ->filter(fn (array $ligne) => $ligne['code_filiere'] && $ligne['code_niveau'])
            ->unique(fn (array $ligne) => $ligne['code_filiere'].'|'.$ligne['code_niveau'])
            ->values()
            ->all();
    }

    private function notifierPublic(PlanningComposition $planning): void
    {
        $matricules = Inscription::query()
            ->where('code_annee', $planning->code_annee)
            ->whereHas('classe', function ($q) use ($planning) {
                $q->where('code_filiere', $planning->code_filiere)
                    ->where('code_niveau', $planning->code_niveau);
            })
            ->pluck('matricule');

        $idsEtudiants = Etudiant::query()->whereIn('matricule', $matricules)->pluck('id_utilisateur');
        $idsParents = ParentTuteur::query()
            ->whereHas('enfants', fn ($q) => $q->whereIn('etudiants.matricule', $matricules))
            ->pluck('id_utilisateur');
        $idsEnseignants = Affectation::query()
            ->with('enseignant')
            ->whereHas('classe', function ($q) use ($planning) {
                $q->where('code_filiere', $planning->code_filiere)
                    ->where('code_niveau', $planning->code_niveau);
            })
            ->get()
            ->map(fn (Affectation $affectation) => $affectation->enseignant?->id_utilisateur)
            ->filter();

        $message = 'Planning de composition publié : '.$planning->titre_planning;
        $this->notifications->envoyerPlusieurs($idsEtudiants->merge($idsParents)->merge($idsEnseignants), 'notes', $message);
    }
}
