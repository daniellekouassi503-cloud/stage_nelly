<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Affectation;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Filiere;
use App\Models\Matiere;
use App\Models\Niveau;
use App\Models\Programme;
use App\Services\JournalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StructureController extends Controller
{
    public function __construct(private readonly JournalService $journal) {}

    public function annees(): JsonResponse
    {
        return $this->ok(AnneeScolaire::query()->orderByDesc('code_annee')->get());
    }

    public function enregistrerAnnee(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'code_annee' => ['required', 'string', 'max:10', 'unique:annees_scolaires,code_annee'],
            'libelle_annee' => ['required', 'string', 'max:50'],
            'est_courante' => ['sometimes', 'boolean'],
        ]);

        $annee = DB::transaction(function () use ($donnees) {
            if (! empty($donnees['est_courante'])) {
                AnneeScolaire::query()->update(['est_courante' => false]);
            }

            return AnneeScolaire::query()->create($donnees);
        });

        $this->journal->ecrire($request->user(), 'structure.annee', "Année {$annee->code_annee} créée.", $request);

        return $this->ok($annee, 201);
    }

    public function filieres(): JsonResponse
    {
        return $this->ok(Filiere::query()->orderBy('libelle_filiere')->get());
    }

    public function enregistrerFiliere(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'code_filiere' => ['required', 'string', 'max:10', 'unique:filieres,code_filiere'],
            'libelle_filiere' => ['required', 'string', 'max:100'],
        ]);
        $filiere = Filiere::query()->create($donnees);
        $this->journal->ecrire($request->user(), 'structure.filiere', "Filière {$filiere->code_filiere} créée.", $request);

        return $this->ok($filiere, 201);
    }

    public function niveaux(): JsonResponse
    {
        return $this->ok(Niveau::query()->orderBy('code_niveau')->get());
    }

    public function enregistrerNiveau(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'code_niveau' => ['required', 'string', 'max:10', 'unique:niveaux,code_niveau'],
            'libelle_niveau' => ['required', 'string', 'max:50'],
        ]);

        return $this->ok(Niveau::query()->create($donnees), 201);
    }

    public function classes(): JsonResponse
    {
        return $this->ok(Classe::query()->with(['filiere', 'niveau'])->orderBy('libelle_classe')->get());
    }

    public function enregistrerClasse(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'code_classe' => ['required', 'string', 'max:20', 'unique:classes,code_classe'],
            'libelle_classe' => ['required', 'string', 'max:50'],
            'code_filiere' => ['required', 'exists:filieres,code_filiere'],
            'code_niveau' => ['required', 'exists:niveaux,code_niveau'],
        ]);

        return $this->ok(Classe::query()->create($donnees)->load('filiere', 'niveau'), 201);
    }

    public function matieres(): JsonResponse
    {
        return $this->ok(Matiere::query()->orderBy('libelle_matiere')->get());
    }

    public function enregistrerMatiere(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'code_matiere' => ['required', 'string', 'max:20', 'unique:matieres,code_matiere'],
            'libelle_matiere' => ['required', 'string', 'max:100'],
        ]);

        return $this->ok(Matiere::query()->create($donnees), 201);
    }

    public function programmes(): JsonResponse
    {
        return $this->ok(Programme::query()->with('matiere')->orderBy('code_filiere')->get());
    }

    public function enregistrerProgramme(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'code_matiere' => ['required', 'exists:matieres,code_matiere'],
            'code_filiere' => ['required', 'exists:filieres,code_filiere'],
            'code_niveau' => ['required', 'exists:niveaux,code_niveau'],
            'coefficient' => ['required', 'numeric', 'gt:0', 'max:99'],
        ]);

        $existe = Programme::query()
            ->where('code_matiere', $donnees['code_matiere'])
            ->where('code_filiere', $donnees['code_filiere'])
            ->where('code_niveau', $donnees['code_niveau'])
            ->exists();

        if ($existe) {
            Programme::query()
                ->where('code_matiere', $donnees['code_matiere'])
                ->where('code_filiere', $donnees['code_filiere'])
                ->where('code_niveau', $donnees['code_niveau'])
                ->update(['coefficient' => $donnees['coefficient']]);
        } else {
            Programme::query()->create($donnees);
        }

        return $this->ok(['message' => 'Coefficient enregistré.']);
    }

    public function affectations(Request $request): JsonResponse
    {
        $query = Affectation::query()->with(['enseignant.utilisateur', 'matiere', 'classe']);
        if ($request->user()->role === 'enseignant') {
            $query->where('code_enseignant', $request->user()->enseignant?->code_enseignant);
        }

        return $this->ok($query->get());
    }

    public function enregistrerAffectation(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'code_enseignant' => ['required', 'exists:enseignants,code_enseignant'],
            'code_matiere' => ['required', 'exists:matieres,code_matiere'],
            'code_classe' => ['required', 'exists:classes,code_classe'],
        ]);

        $existe = Affectation::query()->where($donnees)->exists();
        if (! $existe) {
            Affectation::query()->create($donnees);
        }

        $this->journal->ecrire($request->user(), 'structure.affectation', 'Affectation enseignant enregistrée.', $request);

        return $this->ok(['message' => 'Affectation enregistrée.'], 201);
    }

    public function supprimerAffectation(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'code_enseignant' => ['required', Rule::exists('enseigner', 'code_enseignant')],
            'code_matiere' => ['required'],
            'code_classe' => ['required'],
        ]);

        Affectation::query()->where($donnees)->delete();

        return $this->ok(['message' => 'Affectation retirée.']);
    }

    private function ok(mixed $donnees, int $statut = 200): JsonResponse
    {
        return response()->json(['donnees' => $donnees], $statut);
    }
}
