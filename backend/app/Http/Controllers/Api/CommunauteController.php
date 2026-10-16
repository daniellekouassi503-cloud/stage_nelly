<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DemandeRattachement;
use App\Models\Enseignant;
use App\Models\Etudiant;
use App\Models\Inscription;
use App\Models\ParentTuteur;
use App\Services\CompteService;
use App\Services\JournalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommunauteController extends Controller
{
    public function __construct(
        private readonly CompteService $comptes,
        private readonly JournalService $journal,
    ) {}

    public function etudiants(): JsonResponse
    {
        $lignes = Etudiant::query()->with(['utilisateur', 'inscriptions.classe', 'inscriptions.annee'])->orderBy('matricule')->get();

        return response()->json(['donnees' => $lignes->map(fn (Etudiant $e) => $this->presenterEtudiant($e))]);
    }

    public function enregistrerEtudiant(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'matricule' => ['required', 'string', 'max:20', 'unique:etudiants,matricule'],
            'nom' => ['required', 'string', 'max:100'],
            'prenoms' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:utilisateurs,email'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'mot_de_passe' => ['required', 'string', 'min:8'],
            'nom_parent' => ['nullable', 'string', 'max:150'],
            'telephone_parent' => ['nullable', 'string', 'max:20'],
        ]);

        $utilisateur = $this->comptes->creerPersonnel($donnees, 'etudiant');
        $this->journal->ecrire($request->user(), 'etudiant.creation', "Étudiant {$donnees['matricule']} créé.", $request);

        return response()->json(['donnees' => $utilisateur->load('etudiant')->resume()], 201);
    }

    public function enseignants(): JsonResponse
    {
        $lignes = Enseignant::query()->with(['utilisateur', 'affectations.matiere', 'affectations.classe'])->orderBy('code_enseignant')->get();

        return response()->json([
            'donnees' => $lignes->map(fn (Enseignant $e) => [
                'code_enseignant' => $e->code_enseignant,
                'utilisateur' => $e->utilisateur?->resume(),
                'affectations' => $e->affectations,
            ]),
        ]);
    }

    public function enregistrerEnseignant(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'code_enseignant' => ['required', 'string', 'max:20', 'unique:enseignants,code_enseignant'],
            'nom' => ['required', 'string', 'max:100'],
            'prenoms' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:utilisateurs,email'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'mot_de_passe' => ['required', 'string', 'min:8'],
        ]);

        $utilisateur = $this->comptes->creerPersonnel($donnees, 'enseignant');
        $this->journal->ecrire($request->user(), 'enseignant.creation', "Enseignant {$donnees['code_enseignant']} créé.", $request);

        return response()->json(['donnees' => $utilisateur->load('enseignant')->resume()], 201);
    }

    public function parents(): JsonResponse
    {
        $lignes = ParentTuteur::query()
            ->with(['utilisateur', 'enfants.utilisateur', 'demandes.etudiant.utilisateur'])
            ->get();

        return response()->json([
            'donnees' => $lignes->map(function (ParentTuteur $parent) {
                return [
                    'code_parent' => $parent->code_parent,
                    'utilisateur' => $parent->utilisateur?->resume(),
                    'enfants' => $parent->enfants->map(fn (Etudiant $e) => [
                        'matricule' => $e->matricule,
                        'nom_complet' => $e->utilisateur?->nomComplet(),
                        'lien_parente' => $e->pivot->lien_parente,
                        'nom_parent_dossier' => $e->nom_parent,
                        'telephone_parent_dossier' => $e->telephone_parent,
                    ]),
                    'demandes' => $parent->demandes->map(fn (DemandeRattachement $d) => [
                        'matricule' => $d->matricule,
                        'nom_complet' => $d->etudiant?->utilisateur?->nomComplet(),
                        'lien_parente' => $d->lien_parente,
                        'statut' => $d->statut,
                        'nom_parent_dossier' => $d->etudiant?->nom_parent,
                        'telephone_parent_dossier' => $d->etudiant?->telephone_parent,
                    ]),
                ];
            }),
        ]);
    }

    public function deciderParent(Request $request, ParentTuteur $parent): JsonResponse
    {
        $donnees = $request->validate([
            'decision' => ['required', 'in:valide,rejete'],
            'motif' => ['nullable', 'string', 'max:255'],
            'rattachements' => ['array'],
            'rattachements.*.matricule' => ['required_with:rattachements', 'string'],
            'rattachements.*.lien_parente' => ['nullable', 'string', 'max:30'],
            'rattachements.*.accepter' => ['required_with:rattachements', 'boolean'],
        ]);

        $parent = $this->comptes->deciderParent(
            $request->user(),
            $parent,
            $donnees['decision'],
            $donnees['rattachements'] ?? [],
            $donnees['motif'] ?? null
        );

        return response()->json([
            'message' => 'Décision enregistrée.',
            'donnees' => $parent->utilisateur?->resume(),
        ]);
    }

    public function inscriptions(): JsonResponse
    {
        return response()->json([
            'donnees' => Inscription::query()->with(['etudiant.utilisateur', 'classe', 'annee'])->get(),
        ]);
    }

    public function enregistrerInscription(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'matricule' => ['required', 'exists:etudiants,matricule'],
            'code_annee' => ['required', 'exists:annees_scolaires,code_annee'],
            'code_classe' => ['required', 'exists:classes,code_classe'],
        ]);

        $existe = Inscription::query()
            ->where('matricule', $donnees['matricule'])
            ->where('code_annee', $donnees['code_annee'])
            ->first();

        if ($existe) {
            Inscription::query()
                ->where('matricule', $donnees['matricule'])
                ->where('code_annee', $donnees['code_annee'])
                ->update(['code_classe' => $donnees['code_classe']]);
            $inscription = Inscription::query()
                ->where('matricule', $donnees['matricule'])
                ->where('code_annee', $donnees['code_annee'])
                ->first();
        } else {
            $inscription = Inscription::query()->create($donnees);
        }

        $this->journal->ecrire($request->user(), 'inscription', "Inscription {$donnees['matricule']} / {$donnees['code_annee']}.", $request);

        return response()->json(['donnees' => $inscription->load('classe', 'annee')], 201);
    }

    private function presenterEtudiant(Etudiant $etudiant): array
    {
        return [
            'matricule' => $etudiant->matricule,
            'nom_parent' => $etudiant->nom_parent,
            'telephone_parent' => $etudiant->telephone_parent,
            'utilisateur' => $etudiant->utilisateur?->resume(),
            'inscriptions' => $etudiant->inscriptions->map(fn ($i) => [
                'code_annee' => $i->code_annee,
                'libelle_annee' => $i->annee?->libelle_annee,
                'code_classe' => $i->code_classe,
                'libelle_classe' => $i->classe?->libelle_classe,
            ]),
        ];
    }
}
