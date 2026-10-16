<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Evaluation;
use App\Models\FicheVersement;
use App\Models\Filiere;
use App\Models\Matiere;
use App\Models\Niveau;
use App\Models\Notification;
use App\Models\ParentTuteur;
use App\Models\Signalement;
use App\Models\Utilisateur;
use App\Services\PaiementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, PaiementService $paiements): JsonResponse
    {
        $utilisateur = $request->user()->load(['etudiant', 'enseignant', 'parentRattache']);
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
            ],
        ]);
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
