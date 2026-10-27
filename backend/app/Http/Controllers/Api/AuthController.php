<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Etudiant;
use App\Models\Utilisateur;
use App\Services\CompteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function connexion(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'email' => ['required', 'email:rfc,filter', 'max:150', 'regex:/^[^@\s]+@[^@\s]+\.[^@\s]+$/'],
            'mot_de_passe' => ['required', 'string'],
            'matricule' => ['nullable', 'string', 'max:30'],
            'profil' => ['nullable', 'in:etudiant,autre'],
        ], [
            'email.required' => 'L’adresse e-mail est obligatoire.',
            'email.email' => 'Saisissez une vraie adresse e-mail (exemple : prenom.nom@gmail.com).',
            'email.filter' => 'Saisissez une vraie adresse e-mail (exemple : prenom.nom@gmail.com).',
            'email.regex' => 'Saisissez une vraie adresse e-mail (exemple : prenom.nom@gmail.com).',
        ]);

        $matricule = trim((string) ($donnees['matricule'] ?? ''));
        $enfantActif = null;

        if (($donnees['profil'] ?? '') === 'etudiant') {
            $utilisateur = $this->connecterEtudiantParMatricule($donnees['mot_de_passe'], $matricule);
        } else {
            if (! Auth::attempt(['email' => $donnees['email'], 'password' => $donnees['mot_de_passe']])) {
                throw ValidationException::withMessages([
                    'email' => 'Identifiants incorrects.',
                ]);
            }

            /** @var Utilisateur $utilisateur */
            $utilisateur = Auth::user()->load('etudiant');
            $this->refuserSiRejete($utilisateur);

            if ($utilisateur->role === 'etudiant') {
                $etudiant = $this->exigerMatriculeEnBase($matricule);
                if ($utilisateur->etudiant?->matricule !== $etudiant->matricule) {
                    Auth::logout();
                    throw ValidationException::withMessages([
                        'matricule' => 'Ce matricule ne correspond pas à cet e-mail étudiant.',
                    ]);
                }
            }

            if ($utilisateur->role === 'parent') {
                $etudiant = $this->exigerMatriculeEnBase($matricule, 'Le matricule de l’étudiant est obligatoire pour un compte parent.');
                $parent = $utilisateur->parentRattache()->with('enfants')->first();
                $autorise = $parent && $parent->enfants->contains('matricule', $etudiant->matricule);
                if (! $autorise) {
                    Auth::logout();
                    throw ValidationException::withMessages([
                        'matricule' => 'Ce matricule n’est pas associé à votre compte parent.',
                    ]);
                }

                $enfantActif = [
                    'matricule' => $etudiant->matricule,
                    'nom_complet' => $etudiant->utilisateur?->nomComplet(),
                ];
            }
        }

        $utilisateur->tokens()->delete();
        $token = $utilisateur->createToken('session-plateforme')->plainTextToken;
        $resume = $utilisateur->load(['etudiant', 'enseignant', 'parentRattache.enfants.utilisateur'])->resume();
        if ($enfantActif) {
            $resume['enfant_actif'] = $enfantActif;
        }

        return response()->json([
            'message' => 'Connexion réussie.',
            'donnees' => [
                'jeton' => $token,
                'utilisateur' => $resume,
            ],
        ]);
    }

    private function connecterEtudiantParMatricule(string $motDePasse, string $matricule): Utilisateur
    {
        $etudiant = $this->exigerMatriculeEnBase($matricule);
        $utilisateur = $etudiant->utilisateur;

        if (
            ! $utilisateur
            || $utilisateur->role !== 'etudiant'
            || ! Hash::check($motDePasse, $utilisateur->getAuthPassword())
        ) {
            throw ValidationException::withMessages([
                'email' => 'Identifiants incorrects.',
            ]);
        }

        $this->refuserSiRejete($utilisateur);
        Auth::login($utilisateur);

        return $utilisateur;
    }

    private function refuserSiRejete(Utilisateur $utilisateur): void
    {
        if ($utilisateur->statut_compte !== 'rejete') {
            return;
        }

        Auth::logout();
        throw ValidationException::withMessages([
            'email' => 'Ce compte a été rejeté par l’administration.',
        ]);
    }

    private function exigerMatriculeEnBase(string $matricule, string $vide = 'Le matricule étudiant est obligatoire.'): Etudiant
    {
        if ($matricule === '') {
            if (Auth::check()) {
                Auth::logout();
            }
            throw ValidationException::withMessages([
                'matricule' => $vide,
            ]);
        }

        $etudiant = Etudiant::query()->with('utilisateur')->where('matricule', $matricule)->first();
        if (! $etudiant) {
            if (Auth::check()) {
                Auth::logout();
            }
            throw ValidationException::withMessages([
                'matricule' => 'Ce matricule n’existe pas dans la base de l’établissement.',
            ]);
        }

        return $etudiant;
    }

    public function inscriptionParent(Request $request, CompteService $comptes): JsonResponse
    {
        $donnees = $request->validate([
            'nom' => ['required', 'string', 'max:100'],
            'prenoms' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email:rfc,filter', 'max:150', 'regex:/^[^@\s]+@[^@\s]+\.[^@\s]+$/', 'unique:utilisateurs,email'],
            'telephone' => ['required', 'string', 'max:20'],
            'mot_de_passe' => ['required', 'string', 'min:8', 'confirmed'],
            'enfants' => ['required', 'array', 'min:1'],
            'enfants.*.matricule' => ['required', 'string', 'max:30'],
            'enfants.*.lien_parente' => ['nullable', 'string', 'max:30'],
        ], [
            'email.unique' => 'Cet e-mail est déjà utilisé. Connectez-vous avec ce compte, ou choisissez une autre adresse.',
            'email.email' => 'Saisissez une vraie adresse e-mail (exemple : prenom.nom@gmail.com).',
            'email.filter' => 'Saisissez une vraie adresse e-mail (exemple : prenom.nom@gmail.com).',
            'email.regex' => 'Saisissez une vraie adresse e-mail (exemple : prenom.nom@gmail.com).',
            'mot_de_passe.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'mot_de_passe.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
        ], [
            'email' => 'e-mail',
            'mot_de_passe' => 'mot de passe',
            'enfants.*.matricule' => 'matricule',
        ]);

        $utilisateur = $comptes->inscrireParent($donnees);

        return response()->json([
            'message' => 'Compte parent activé immédiatement. Aucune validation administrative n’est requise.',
            'donnees' => $utilisateur->load(['parentRattache.enfants.utilisateur'])->resume(),
        ], 201);
    }

    public function moi(Request $request): JsonResponse
    {
        $utilisateur = $request->user()->load(['etudiant', 'enseignant', 'parentRattache.enfants.utilisateur']);

        return response()->json(['donnees' => $utilisateur->resume()]);
    }

    public function deconnexion(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Déconnexion effectuée.']);
    }
}
