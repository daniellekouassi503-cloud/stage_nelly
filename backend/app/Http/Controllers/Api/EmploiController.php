<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Affectation;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\EmploiDuTemps;
use App\Models\Etudiant;
use App\Models\Inscription;
use App\Models\Matiere;
use App\Models\Programme;
use App\Services\AccesScolaire;
use App\Services\JournalService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmploiController extends Controller
{
    public function __construct(
        private readonly AccesScolaire $acces,
        private readonly JournalService $journal,
    ) {}

    public function classesDisponibles(Request $request): JsonResponse
    {
        $utilisateur = $request->user();
        $query = Classe::query()->with(['filiere', 'niveau'])->orderBy('libelle_classe');

        if ($utilisateur->role === 'enseignant') {
            $codes = Affectation::query()
                ->where('code_enseignant', $utilisateur->enseignant?->code_enseignant)
                ->pluck('code_classe');
            $query->whereIn('code_classe', $codes);
        } elseif (in_array($utilisateur->role, ['etudiant', 'parent'], true)) {
            $matricules = $this->acces->matriculesVisibles($utilisateur);
            $annee = $this->anneeCourante($request->query('code_annee'));
            $codes = Inscription::query()
                ->whereIn('matricule', $matricules)
                ->where('code_annee', $annee)
                ->pluck('code_classe');
            $query->whereIn('code_classe', $codes);
        }

        return response()->json(['donnees' => $query->get()]);
    }

    public function emploi(Request $request): JsonResponse
    {
        $utilisateur = $request->user();
        $annee = $this->anneeCourante($request->query('code_annee'));
        $codeClasse = $request->query('code_classe');

        if (! $codeClasse) {
            $codeClasse = $this->resoudreClasse($request, $utilisateur, $annee);
        } else {
            $this->autoriserClasse($utilisateur, $codeClasse, $annee, $request->query('matricule'));
        }

        $classe = Classe::query()->with(['filiere', 'niveau'])->findOrFail($codeClasse);
        $seances = EmploiDuTemps::query()
            ->where('code_classe', $codeClasse)
            ->where('code_annee', $annee)
            ->orderBy('jour_semaine')
            ->orderBy('heure_debut')
            ->get();

        $jours = [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi'];

        $matieres = Programme::query()
            ->with('matiere')
            ->where('code_filiere', $classe->code_filiere)
            ->where('code_niveau', $classe->code_niveau)
            ->get()
            ->map(fn (Programme $p) => [
                'code_matiere' => $p->code_matiere,
                'libelle_matiere' => $p->matiere?->libelle_matiere ?? $p->code_matiere,
            ])
            ->unique('code_matiere')
            ->values();

        return response()->json([
            'donnees' => [
                'classe' => [
                    'code_classe' => $classe->code_classe,
                    'libelle_classe' => $classe->libelle_classe,
                    'filiere' => $classe->filiere?->libelle_filiere,
                    'niveau' => $classe->niveau?->libelle_niveau,
                ],
                'code_annee' => $annee,
                'peut_modifier' => $utilisateur->role === 'administrateur',
                'provisoire' => $seances->contains(fn (EmploiDuTemps $s) => $s->provisoire),
                'matieres' => $matieres,
                'jours' => collect($jours)->map(fn ($libelle, $numero) => [
                    'numero' => (int) $numero,
                    'libelle' => $libelle,
                    'seances' => $seances
                        ->where('jour_semaine', (int) $numero)
                        ->values()
                        ->map(fn (EmploiDuTemps $s) => $this->presenterSeance($s, $jours)),
                ])->values(),
                'seances' => $seances->map(fn (EmploiDuTemps $s) => $this->presenterSeance($s, $jours))->values(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->exigerAdmin($request);
        $donnees = $this->validerSeance($request, true);
        $seance = EmploiDuTemps::query()->create($donnees);
        $this->journal->ecrire(
            $request->user(),
            'emploi.creation',
            "Séance {$seance->id_seance} ajoutée pour {$seance->code_classe}.",
            $request
        );

        return response()->json(['donnees' => $this->presenterSeance($seance)], 201);
    }

    public function update(Request $request, EmploiDuTemps $seance): JsonResponse
    {
        $this->exigerAdmin($request);
        $donnees = $this->validerSeance($request, false);
        $seance->update($donnees);
        $this->journal->ecrire(
            $request->user(),
            'emploi.modification',
            "Séance {$seance->id_seance} modifiée.",
            $request
        );

        return response()->json(['donnees' => $this->presenterSeance($seance->fresh())]);
    }

    public function destroy(Request $request, EmploiDuTemps $seance): JsonResponse
    {
        $this->exigerAdmin($request);
        $id = $seance->id_seance;
        $classe = $seance->code_classe;
        $seance->delete();
        $this->journal->ecrire($request->user(), 'emploi.suppression', "Séance {$id} supprimée ({$classe}).", $request);

        return response()->json(['message' => 'Séance retirée de l’emploi du temps.']);
    }

    public function listeClasse(Request $request): JsonResponse
    {
        $utilisateur = $request->user();
        $annee = $this->anneeCourante($request->query('code_annee'));
        $codeClasse = $request->query('code_classe');

        if (! $codeClasse) {
            $codeClasse = $this->resoudreClasse($request, $utilisateur, $annee);
        } else {
            $this->autoriserClasse($utilisateur, $codeClasse, $annee, $request->query('matricule'));
        }

        $classe = Classe::query()->with(['filiere', 'niveau'])->findOrFail($codeClasse);
        $inscriptions = Inscription::query()
            ->with('etudiant.utilisateur')
            ->where('code_classe', $codeClasse)
            ->where('code_annee', $annee)
            ->orderBy('matricule')
            ->get();

        $etudiants = $inscriptions
            ->sortBy(fn (Inscription $i) => mb_strtoupper($i->etudiant?->utilisateur?->nom_utilisateur.' '.$i->etudiant?->utilisateur?->prenoms_utilisateur))
            ->values()
            ->map(fn (Inscription $i, int $index) => [
                'ordre' => $index + 1,
                'matricule' => $i->matricule,
                'nom' => $i->etudiant?->utilisateur?->nom_utilisateur,
                'prenoms' => $i->etudiant?->utilisateur?->prenoms_utilisateur,
                'nom_complet' => $i->etudiant?->utilisateur?->nomComplet(),
            ]);

        return response()->json([
            'donnees' => [
                'classe' => [
                    'code_classe' => $classe->code_classe,
                    'libelle_classe' => $classe->libelle_classe,
                    'filiere' => $classe->filiere?->libelle_filiere,
                    'niveau' => $classe->niveau?->libelle_niveau,
                ],
                'code_annee' => $annee,
                'effectif' => $etudiants->count(),
                'etudiants' => $etudiants,
            ],
        ]);
    }

    private function presenterSeance(EmploiDuTemps $s, array $jours = []): array
    {
        if ($jours === []) {
            $jours = [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi'];
        }

        return [
            'id_seance' => $s->id_seance,
            'code_classe' => $s->code_classe,
            'code_annee' => $s->code_annee,
            'jour_semaine' => $s->jour_semaine,
            'jour' => $jours[$s->jour_semaine] ?? '',
            'heure_debut' => substr((string) $s->heure_debut, 0, 5),
            'heure_fin' => substr((string) $s->heure_fin, 0, 5),
            'code_matiere' => $s->code_matiere,
            'libelle_matiere' => $s->libelle_matiere,
            'enseignant' => $s->enseignant,
            'salle' => $s->salle,
            'provisoire' => (bool) $s->provisoire,
        ];
    }

    private function validerSeance(Request $request, bool $creation): array
    {
        $request->merge([
            'heure_debut' => substr((string) $request->input('heure_debut'), 0, 5),
            'heure_fin' => substr((string) $request->input('heure_fin'), 0, 5),
        ]);

        $regles = [
            'jour_semaine' => ['required', 'integer', 'between:1,6'],
            'heure_debut' => ['required', 'date_format:H:i'],
            'heure_fin' => ['required', 'date_format:H:i', 'after:heure_debut'],
            'code_matiere' => ['nullable', 'exists:matieres,code_matiere'],
            'libelle_matiere' => ['nullable', 'string', 'max:120'],
            'enseignant' => ['nullable', 'string', 'max:150'],
            'salle' => ['nullable', 'string', 'max:50'],
            'provisoire' => ['sometimes', 'boolean'],
        ];

        if ($creation) {
            $regles['code_classe'] = ['required', 'exists:classes,code_classe'];
            $regles['code_annee'] = ['nullable', 'exists:annees_scolaires,code_annee'];
        }

        $donnees = $request->validate($regles);
        if (blank($donnees['libelle_matiere'] ?? null) && ! empty($donnees['code_matiere'])) {
            $donnees['libelle_matiere'] = Matiere::query()->where('code_matiere', $donnees['code_matiere'])->value('libelle_matiere')
                ?? $donnees['code_matiere'];
        }
        if (blank($donnees['libelle_matiere'] ?? null)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'libelle_matiere' => 'Indiquez la matière de la séance.',
            ]);
        }
        $donnees['provisoire'] = $request->boolean('provisoire', $creation ? true : (bool) $request->input('provisoire'));
        if ($creation) {
            $donnees['code_annee'] = $donnees['code_annee'] ?? $this->anneeCourante(null);
        }

        return $donnees;
    }

    private function exigerAdmin(Request $request): void
    {
        if ($request->user()?->role !== 'administrateur') {
            throw new AuthorizationException('Seul l’administrateur peut modifier l’emploi du temps.');
        }
    }

    private function anneeCourante(?string $codeAnnee): string
    {
        if ($codeAnnee) {
            return $codeAnnee;
        }

        return AnneeScolaire::query()->where('est_courante', true)->value('code_annee')
            ?? AnneeScolaire::query()->orderByDesc('code_annee')->value('code_annee')
            ?? '2025-2026';
    }

    private function resoudreClasse(Request $request, $utilisateur, string $annee): string
    {
        if ($utilisateur->role === 'administrateur') {
            $defaut = EmploiDuTemps::query()->where('code_annee', $annee)->value('code_classe')
                ?? Inscription::query()->where('code_annee', $annee)->value('code_classe');
            if (! $defaut) {
                throw new AuthorizationException('Aucune classe disponible pour l’année courante.');
            }

            return $defaut;
        }

        if ($utilisateur->role === 'enseignant') {
            $code = $utilisateur->enseignant?->code_enseignant;
            $classe = Affectation::query()->where('code_enseignant', $code)->value('code_classe');
            if (! $classe) {
                throw new AuthorizationException('Aucune classe ne vous est affectée.');
            }

            return $classe;
        }

        $matricule = $request->query('matricule');
        if ($utilisateur->role === 'etudiant') {
            $matricule = $utilisateur->etudiant?->matricule;
        }
        if ($utilisateur->role === 'parent' && ! $matricule) {
            $matricule = $utilisateur->parentRattache?->enfants()->value('etudiants.matricule');
        }
        if (! $matricule) {
            throw new AuthorizationException('Matricule requis pour afficher la classe.');
        }

        $etudiant = Etudiant::query()->findOrFail($matricule);
        $this->acces->exigerEtudiant($utilisateur, $etudiant);

        $inscription = Inscription::query()
            ->where('matricule', $matricule)
            ->where('code_annee', $annee)
            ->first();
        if (! $inscription) {
            throw new AuthorizationException('Aucune inscription trouvée pour cette année.');
        }

        return $inscription->code_classe;
    }

    private function autoriserClasse($utilisateur, string $codeClasse, string $annee, ?string $matricule): void
    {
        if ($utilisateur->role === 'administrateur') {
            return;
        }

        if ($utilisateur->role === 'enseignant') {
            $ok = Affectation::query()
                ->where('code_enseignant', $utilisateur->enseignant?->code_enseignant)
                ->where('code_classe', $codeClasse)
                ->exists();
            if (! $ok) {
                throw new AuthorizationException('Cette classe ne vous est pas affectée.');
            }

            return;
        }

        if ($utilisateur->role === 'etudiant') {
            $matricule = $utilisateur->etudiant?->matricule;
        }
        if (! $matricule) {
            throw new AuthorizationException('Matricule requis.');
        }

        $etudiant = Etudiant::query()->findOrFail($matricule);
        $this->acces->exigerEtudiant($utilisateur, $etudiant);

        $ok = Inscription::query()
            ->where('matricule', $matricule)
            ->where('code_annee', $annee)
            ->where('code_classe', $codeClasse)
            ->exists();
        if (! $ok) {
            throw new AuthorizationException('Cette classe ne correspond pas à l’inscription de l’étudiant.');
        }
    }
}
