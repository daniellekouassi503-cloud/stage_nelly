<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Utilisateur;
use App\Services\CompteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function connexion(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'email' => ['required', 'email'],
            'mot_de_passe' => ['required', 'string'],
        ]);

        if (! Auth::attempt(['email' => $donnees['email'], 'password' => $donnees['mot_de_passe']])) {
            throw ValidationException::withMessages([
                'email' => 'Identifiants incorrects.',
            ]);
        }

        /** @var Utilisateur $utilisateur */
        $utilisateur = Auth::user();
        if ($utilisateur->statut_compte === 'rejete') {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => 'Ce compte a été rejeté par l’administration.',
            ]);
        }

        $utilisateur->tokens()->delete();
        $token = $utilisateur->createToken('session-plateforme')->plainTextToken;

        return response()->json([
            'message' => 'Connexion réussie.',
            'donnees' => [
                'jeton' => $token,
                'utilisateur' => $utilisateur->load(['etudiant', 'enseignant', 'parentRattache.enfants.utilisateur'])->resume(),
            ],
        ]);
    }

    public function inscriptionParent(Request $request, CompteService $comptes): JsonResponse
    {
        $donnees = $request->validate([
            'nom' => ['required', 'string', 'max:100'],
            'prenoms' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:utilisateurs,email'],
            'telephone' => ['required', 'string', 'max:20'],
            'mot_de_passe' => ['required', 'string', 'min:8', 'confirmed'],
            'enfants' => ['required', 'array', 'min:1'],
            'enfants.*.matricule' => ['required', 'string', 'exists:etudiants,matricule'],
            'enfants.*.lien_parente' => ['nullable', 'string', 'max:30'],
        ]);

        $utilisateur = $comptes->inscrireParent($donnees);

        return response()->json([
            'message' => 'Demande enregistrée. Le compte reste en attente de validation.',
            'donnees' => $utilisateur->load('parentRattache')->resume(),
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
