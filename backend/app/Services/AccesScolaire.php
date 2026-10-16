<?php

namespace App\Services;

use App\Models\Affectation;
use App\Models\Etudiant;
use App\Models\Evaluation;
use App\Models\FicheVersement;
use App\Models\Inscription;
use App\Models\ParentTuteur;
use App\Models\SupportCours;
use App\Models\Utilisateur;
use Illuminate\Auth\Access\AuthorizationException;

class AccesScolaire
{
    public function exigerValide(Utilisateur $utilisateur): void
    {
        if ($utilisateur->statut_compte !== 'valide') {
            throw new AuthorizationException('Le compte doit être validé par l’administration.');
        }
    }

    public function peutVoirEtudiant(Utilisateur $utilisateur, Etudiant $etudiant): bool
    {
        return match ($utilisateur->role) {
            'administrateur' => true,
            'etudiant' => $utilisateur->etudiant?->matricule === $etudiant->matricule,
            'parent' => $this->parentLie($utilisateur, $etudiant->matricule),
            'enseignant' => $this->enseignantVoitEtudiant($utilisateur, $etudiant),
            default => false,
        };
    }

    public function exigerEtudiant(Utilisateur $utilisateur, Etudiant $etudiant): void
    {
        if (! $this->peutVoirEtudiant($utilisateur, $etudiant)) {
            throw new AuthorizationException('Vous ne pouvez pas consulter cet étudiant.');
        }
    }

    public function parentLie(Utilisateur $utilisateur, string $matricule): bool
    {
        if ($utilisateur->role !== 'parent' || $utilisateur->statut_compte !== 'valide') {
            return false;
        }

        $code = $utilisateur->parentRattache?->code_parent;
        if (! $code) {
            return false;
        }

        return ParentTuteur::query()
            ->where('code_parent', $code)
            ->whereHas('enfants', fn ($q) => $q->where('etudiants.matricule', $matricule))
            ->exists();
    }

    public function enseignantVoitEtudiant(Utilisateur $utilisateur, Etudiant $etudiant): bool
    {
        $code = $utilisateur->enseignant?->code_enseignant;
        if (! $code) {
            return false;
        }

        $classes = Affectation::query()->where('code_enseignant', $code)->pluck('code_classe');

        return Inscription::query()
            ->where('matricule', $etudiant->matricule)
            ->whereIn('code_classe', $classes)
            ->exists();
    }

    public function peutGererEvaluation(Utilisateur $utilisateur, Evaluation $evaluation): bool
    {
        if ($utilisateur->role === 'administrateur') {
            return true;
        }

        return $utilisateur->role === 'enseignant'
            && $utilisateur->enseignant?->code_enseignant === $evaluation->code_enseignant;
    }

    public function evaluationVisible(Utilisateur $utilisateur, Evaluation $evaluation): bool
    {
        if ($this->peutGererEvaluation($utilisateur, $evaluation)) {
            return true;
        }

        return $evaluation->estPubliee();
    }

    public function peutVoirFiche(Utilisateur $utilisateur, FicheVersement $fiche): bool
    {
        if ($utilisateur->role === 'administrateur') {
            return true;
        }

        $etudiant = $fiche->etudiant ?? Etudiant::query()->find($fiche->matricule);
        if (! $etudiant) {
            return false;
        }

        if ($utilisateur->role === 'enseignant') {
            return false;
        }

        return $this->peutVoirEtudiant($utilisateur, $etudiant);
    }

    public function peutVoirSupport(Utilisateur $utilisateur, SupportCours $support): bool
    {
        if ($utilisateur->role === 'administrateur') {
            return true;
        }

        if ($utilisateur->role === 'enseignant') {
            return $utilisateur->enseignant?->code_enseignant === $support->code_enseignant
                || Affectation::query()
                    ->where('code_enseignant', $utilisateur->enseignant?->code_enseignant)
                    ->where('code_matiere', $support->code_matiere)
                    ->whereHas('classe', function ($q) use ($support) {
                        $q->where('code_filiere', $support->code_filiere)
                            ->where('code_niveau', $support->code_niveau);
                    })
                    ->exists();
        }

        $matricules = $this->matriculesVisibles($utilisateur);

        return Inscription::query()
            ->whereIn('matricule', $matricules)
            ->whereHas('classe', function ($q) use ($support) {
                $q->where('code_filiere', $support->code_filiere)
                    ->where('code_niveau', $support->code_niveau);
            })
            ->exists();
    }

    /**
     * @return array<int, string>
     */
    public function matriculesVisibles(Utilisateur $utilisateur): array
    {
        return match ($utilisateur->role) {
            'etudiant' => array_filter([$utilisateur->etudiant?->matricule]),
            'parent' => $utilisateur->statut_compte === 'valide'
                ? ($utilisateur->parentRattache?->enfants()->pluck('etudiants.matricule')->all() ?? [])
                : [],
            default => [],
        };
    }
}
