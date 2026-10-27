<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommunauteController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EmploiController;
use App\Http\Controllers\Api\EvaluationController;
use App\Http\Controllers\Api\PaiementController;
use App\Http\Controllers\Api\PlanningCompositionController;
use App\Http\Controllers\Api\ResultatController;
use App\Http\Controllers\Api\StructureController;
use App\Http\Controllers\Api\SuiviController;
use App\Http\Controllers\Api\SupportController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/connexion', [AuthController::class, 'connexion'])->middleware('throttle:connexion');
Route::post('/auth/inscription-parent', [AuthController::class, 'inscriptionParent'])->middleware('throttle:connexion');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/moi', [AuthController::class, 'moi']);
    Route::post('/auth/deconnexion', [AuthController::class, 'deconnexion']);
    Route::post('/auth/mot-de-passe', [AuthController::class, 'changerMotDePasse'])->middleware('throttle:connexion');
    Route::get('/notifications', [SuiviController::class, 'notifications']);
    Route::post('/notifications/{notification}/lire', [SuiviController::class, 'lire']);
    Route::post('/notifications/tout-lire', [SuiviController::class, 'toutLire']);

    Route::middleware('compte.valide')->group(function () {
        Route::get('/tableau-de-bord', [DashboardController::class, 'index']);

        Route::get('/resultats', [ResultatController::class, 'index']);
        Route::get('/resultats/bulletin', [ResultatController::class, 'bulletin']);

        Route::get('/supports', [SupportController::class, 'index']);
        Route::get('/supports/{support}/telecharger', [SupportController::class, 'telecharger']);

        Route::get('/frais', [PaiementController::class, 'situation']);
        Route::get('/fiches', [PaiementController::class, 'fiches']);
        Route::post('/fiches', [PaiementController::class, 'generer']);
        Route::get('/fiches/{fiche}/pdf', [PaiementController::class, 'pdfFiche']);
        Route::get('/fiches/{fiche}/recu', [PaiementController::class, 'recu']);
        Route::post('/fiches/{fiche}/simuler', [PaiementController::class, 'simuler']);

        Route::get('/evaluations', [EvaluationController::class, 'index']);
        Route::get('/evaluations/{evaluation}/notes', [EvaluationController::class, 'notes']);
        Route::get('/signalements', [EvaluationController::class, 'signalements']);

        Route::get('/classes-disponibles', [EmploiController::class, 'classesDisponibles']);
        Route::get('/emploi-du-temps', [EmploiController::class, 'emploi']);
        Route::get('/liste-classe', [EmploiController::class, 'listeClasse']);

        Route::get('/plannings', [PlanningCompositionController::class, 'index']);
        Route::get('/plannings/{planning}/fichier', [PlanningCompositionController::class, 'fichier']);

        Route::middleware('role:etudiant')->group(function () {
            Route::post('/evaluations/{evaluation}/signalements', [EvaluationController::class, 'signaler']);
        });

        Route::middleware('role:enseignant,administrateur')->group(function () {
            Route::get('/affectations', [StructureController::class, 'affectations']);
            Route::post('/evaluations', [EvaluationController::class, 'store']);
            Route::put('/evaluations/{evaluation}/notes', [EvaluationController::class, 'enregistrerNotes']);
            Route::post('/evaluations/{evaluation}/soumettre', [EvaluationController::class, 'soumettre']);
            Route::post('/evaluations/{evaluation}/publier', [EvaluationController::class, 'publier']);
            Route::post('/signalements/{signalement}/traiter', [EvaluationController::class, 'traiter']);
            Route::post('/supports', [SupportController::class, 'store']);
        });

        Route::middleware('role:administrateur')->group(function () {
            Route::get('/annees', [StructureController::class, 'annees']);
            Route::post('/annees', [StructureController::class, 'enregistrerAnnee']);
            Route::get('/filieres', [StructureController::class, 'filieres']);
            Route::post('/filieres', [StructureController::class, 'enregistrerFiliere']);
            Route::get('/niveaux', [StructureController::class, 'niveaux']);
            Route::post('/niveaux', [StructureController::class, 'enregistrerNiveau']);
            Route::get('/classes', [StructureController::class, 'classes']);
            Route::post('/classes', [StructureController::class, 'enregistrerClasse']);
            Route::get('/matieres', [StructureController::class, 'matieres']);
            Route::post('/matieres', [StructureController::class, 'enregistrerMatiere']);
            Route::get('/programmes', [StructureController::class, 'programmes']);
            Route::post('/programmes', [StructureController::class, 'enregistrerProgramme']);
            Route::post('/affectations', [StructureController::class, 'enregistrerAffectation']);
            Route::delete('/affectations', [StructureController::class, 'supprimerAffectation']);

            Route::get('/etudiants', [CommunauteController::class, 'etudiants']);
            Route::post('/etudiants', [CommunauteController::class, 'enregistrerEtudiant']);
            Route::put('/etudiants/{etudiant}', [CommunauteController::class, 'modifierEtudiant']);
            Route::post('/etudiants/{etudiant}/mot-de-passe', [CommunauteController::class, 'motDePasseEtudiant']);
            Route::delete('/etudiants/{etudiant}', [CommunauteController::class, 'supprimerEtudiant']);
            Route::get('/enseignants', [CommunauteController::class, 'enseignants']);
            Route::post('/enseignants', [CommunauteController::class, 'enregistrerEnseignant']);
            Route::put('/enseignants/{enseignant}', [CommunauteController::class, 'modifierEnseignant']);
            Route::post('/enseignants/{enseignant}/mot-de-passe', [CommunauteController::class, 'motDePasseEnseignant']);
            Route::delete('/enseignants/{enseignant}', [CommunauteController::class, 'supprimerEnseignant']);
            Route::get('/parents', [CommunauteController::class, 'parents']);
            Route::post('/parents/{parent}/decision', [CommunauteController::class, 'deciderParent']);
            Route::put('/parents/{parent}', [CommunauteController::class, 'modifierParent']);
            Route::post('/parents/{parent}/mot-de-passe', [CommunauteController::class, 'motDePasseParent']);
            Route::delete('/parents/{parent}', [CommunauteController::class, 'supprimerParent']);
            Route::get('/inscriptions', [CommunauteController::class, 'inscriptions']);
            Route::post('/inscriptions', [CommunauteController::class, 'enregistrerInscription']);

            Route::get('/tranches', [PaiementController::class, 'tranches']);
            Route::post('/tranches', [PaiementController::class, 'enregistrerTranche']);
            Route::post('/fiches/{fiche}/decision', [PaiementController::class, 'decider']);
            Route::get('/finances', [PaiementController::class, 'finances']);
            Route::get('/journal', [SuiviController::class, 'journal']);
            Route::post('/plannings', [PlanningCompositionController::class, 'store']);
            Route::delete('/plannings/{planning}', [PlanningCompositionController::class, 'destroy']);
            Route::post('/emploi-du-temps', [EmploiController::class, 'store']);
            Route::put('/emploi-du-temps/{seance}', [EmploiController::class, 'update']);
            Route::delete('/emploi-du-temps/{seance}', [EmploiController::class, 'destroy']);
        });
    });
});
