<?php

namespace App\Services;

use App\Models\Affectation;
use App\Models\AnneeScolaire;
use App\Models\DemandeRattachement;
use App\Models\Enseignant;
use App\Models\Etudiant;
use App\Models\Evaluation;
use App\Models\FicheVersement;
use App\Models\Inscription;
use App\Models\Note;
use App\Models\Notification;
use App\Models\ParentTuteur;
use App\Models\Signalement;
use App\Models\SupportCours;
use App\Models\Utilisateur;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CompteService
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly JournalService $journal,
    ) {}

    public function creerPersonnel(array $donnees, string $role): Utilisateur
    {
        return DB::transaction(function () use ($donnees, $role) {
            $utilisateur = Utilisateur::query()->create([
                'nom_utilisateur' => $donnees['nom'],
                'prenoms_utilisateur' => $donnees['prenoms'],
                'email' => $donnees['email'],
                'telephone_utilisateur' => $donnees['telephone'] ?? null,
                'mot_de_passe' => $donnees['mot_de_passe'],
                'role' => $role,
                'statut_compte' => 'valide',
                'date_creation' => now(),
            ]);

            if ($role === 'etudiant') {
                Etudiant::query()->create([
                    'matricule' => $donnees['matricule'],
                    'nom_parent' => $donnees['nom_parent'] ?? null,
                    'telephone_parent' => $donnees['telephone_parent'] ?? null,
                    'id_utilisateur' => $utilisateur->id_utilisateur,
                ]);
            }

            if ($role === 'enseignant') {
                Enseignant::query()->create([
                    'code_enseignant' => $donnees['code_enseignant'],
                    'id_utilisateur' => $utilisateur->id_utilisateur,
                ]);
            }

            return $utilisateur->fresh(['etudiant', 'enseignant']);
        });
    }

    public function inscrireParent(array $donnees): Utilisateur
    {
        return DB::transaction(function () use ($donnees) {
            // Second facteur : le téléphone saisi doit être celui que l'établissement
            // a enregistré dans le dossier de l'enfant. Connaître le matricule ne suffit pas.
            // Message identique dans tous les cas pour ne pas révéler quels matricules existent.
            $telephone = $this->normaliserTelephone($donnees['telephone']);
            foreach ($donnees['enfants'] as $enfant) {
                $etudiant = Etudiant::query()->where('matricule', $enfant['matricule'])->first();
                $telephoneDossier = $this->normaliserTelephone((string) $etudiant?->telephone_parent);

                if (! $etudiant || $telephoneDossier === '' || ! hash_equals($telephoneDossier, $telephone)) {
                    throw ValidationException::withMessages([
                        'enfants' => 'Le matricule et le téléphone ne correspondent à aucun dossier étudiant. Utilisez le numéro de téléphone donné à l’établissement lors de l’inscription, ou contactez l’administration.',
                    ]);
                }
            }

            $utilisateur = Utilisateur::query()->create([
                'nom_utilisateur' => $donnees['nom'],
                'prenoms_utilisateur' => $donnees['prenoms'],
                'email' => $donnees['email'],
                'telephone_utilisateur' => $donnees['telephone'],
                'mot_de_passe' => $donnees['mot_de_passe'],
                'role' => 'parent',
                'statut_compte' => 'valide',
                'date_creation' => now(),
            ]);

            $code = $this->prochainCode('PAR', ParentTuteur::class, 'code_parent');
            ParentTuteur::query()->create([
                'code_parent' => $code,
                'id_utilisateur' => $utilisateur->id_utilisateur,
            ]);

            foreach ($donnees['enfants'] as $enfant) {
                DB::table('rattacher')->updateOrInsert(
                    ['code_parent' => $code, 'matricule' => $enfant['matricule']],
                    ['lien_parente' => $enfant['lien_parente'] ?? null]
                );

                DemandeRattachement::query()->updateOrCreate(
                    ['code_parent' => $code, 'matricule' => $enfant['matricule']],
                    [
                        'lien_parente' => $enfant['lien_parente'] ?? null,
                        'statut' => 'accepte',
                        'date_demande' => now(),
                    ]
                );
            }

            $this->notifications->envoyer(
                $utilisateur->id_utilisateur,
                'compte_parent',
                'Votre compte parent est actif. Le matricule saisi correspond à un étudiant de l’établissement.'
            );
            $this->journal->ecrire($utilisateur, 'compte.parent.inscription', "Compte parent {$code} créé et activé automatiquement.", request());

            return $utilisateur->fresh(['parentRattache.enfants']);
        });
    }

    /**
     * Garde uniquement les chiffres et retire l'indicatif +225 / 00225,
     * pour que « 07 00 00 00 01 » et « +225 0700000001 » soient identiques.
     */
    private function normaliserTelephone(string $telephone): string
    {
        $chiffres = preg_replace('/\D+/', '', $telephone) ?? '';
        $chiffres = preg_replace('/^(00)?225(?=\d{10}$)/', '', $chiffres) ?? $chiffres;

        return $chiffres;
    }

    public function deciderParent(Utilisateur $admin, ParentTuteur $parent, string $decision, array $choix, ?string $motif): ParentTuteur
    {
        if ($admin->role !== 'administrateur') {
            throw new AuthorizationException('Action réservée à l’administration.');
        }

        return DB::transaction(function () use ($admin, $parent, $decision, $choix, $motif) {
            $utilisateur = $parent->utilisateur()->lockForUpdate()->first();
            if ($utilisateur->statut_compte !== 'en_attente') {
                throw ValidationException::withMessages([
                    'statut' => 'Ce compte a déjà été traité.',
                ]);
            }

            if ($decision === 'rejete') {
                $utilisateur->statut_compte = 'rejete';
                $utilisateur->save();
                DemandeRattachement::query()->where('code_parent', $parent->code_parent)->update(['statut' => 'refuse']);
                $this->notifications->envoyer(
                    $utilisateur->id_utilisateur,
                    'compte_parent',
                    'Votre compte parent a été rejeté.'.($motif ? ' Motif : '.$motif : '')
                );
                $this->journal->ecrire($admin, 'compte.parent.rejet', "Compte {$parent->code_parent} rejeté.", request());

                return $parent->fresh('utilisateur');
            }

            $acceptes = collect($choix)->filter(fn ($ligne) => ! empty($ligne['accepter']))->values();
            if ($acceptes->isEmpty()) {
                throw ValidationException::withMessages([
                    'rattachements' => 'Validez au moins un enfant pour accepter le compte.',
                ]);
            }

            foreach ($acceptes as $ligne) {
                $demande = DemandeRattachement::query()
                    ->where('code_parent', $parent->code_parent)
                    ->where('matricule', $ligne['matricule'])
                    ->first();

                if (! $demande) {
                    throw ValidationException::withMessages([
                        'rattachements' => "Aucune demande pour le matricule {$ligne['matricule']}.",
                    ]);
                }

                DB::table('rattacher')->updateOrInsert(
                    ['code_parent' => $parent->code_parent, 'matricule' => $ligne['matricule']],
                    ['lien_parente' => $ligne['lien_parente'] ?? $demande->lien_parente]
                );
                $demande->statut = 'accepte';
                $demande->save();
            }

            DemandeRattachement::query()
                ->where('code_parent', $parent->code_parent)
                ->where('statut', 'en_attente')
                ->update(['statut' => 'refuse']);

            $utilisateur->statut_compte = 'valide';
            $utilisateur->save();

            $this->notifications->envoyer(
                $utilisateur->id_utilisateur,
                'compte_parent',
                'Votre compte parent est validé. Vous pouvez consulter les enfants qui vous sont rattachés.'
            );
            $this->journal->ecrire($admin, 'compte.parent.validation', "Compte {$parent->code_parent} validé.", request());

            return $parent->fresh(['utilisateur', 'enfants']);
        });
    }

    public function modifierEtudiant(Utilisateur $admin, Etudiant $etudiant, array $donnees): Etudiant
    {
        $this->exigerAdministrateur($admin);

        return DB::transaction(function () use ($admin, $etudiant, $donnees) {
            $etudiant->load('utilisateur');
            $utilisateur = $etudiant->utilisateur;
            if (! $utilisateur) {
                throw ValidationException::withMessages(['etudiant' => 'Compte utilisateur introuvable.']);
            }

            $this->mettreAJourIdentite($utilisateur, $donnees);
            $etudiant->nom_parent = $donnees['nom_parent'] ?? $etudiant->nom_parent;
            $etudiant->telephone_parent = $donnees['telephone_parent'] ?? $etudiant->telephone_parent;
            $etudiant->save();

            $this->journal->ecrire($admin, 'etudiant.modification', "Étudiant {$etudiant->matricule} modifié.", request());

            return $etudiant->fresh('utilisateur');
        });
    }

    public function modifierEnseignant(Utilisateur $admin, Enseignant $enseignant, array $donnees): Enseignant
    {
        $this->exigerAdministrateur($admin);

        return DB::transaction(function () use ($admin, $enseignant, $donnees) {
            $enseignant->load('utilisateur');
            $utilisateur = $enseignant->utilisateur;
            if (! $utilisateur) {
                throw ValidationException::withMessages(['enseignant' => 'Compte utilisateur introuvable.']);
            }

            $this->mettreAJourIdentite($utilisateur, $donnees);
            $this->journal->ecrire($admin, 'enseignant.modification', "Enseignant {$enseignant->code_enseignant} modifié.", request());

            return $enseignant->fresh('utilisateur');
        });
    }

    public function modifierParent(Utilisateur $admin, ParentTuteur $parent, array $donnees): ParentTuteur
    {
        $this->exigerAdministrateur($admin);

        return DB::transaction(function () use ($admin, $parent, $donnees) {
            $parent->load('utilisateur');
            $utilisateur = $parent->utilisateur;
            if (! $utilisateur) {
                throw ValidationException::withMessages(['parent' => 'Compte utilisateur introuvable.']);
            }

            $this->mettreAJourIdentite($utilisateur, $donnees);
            $this->journal->ecrire($admin, 'compte.parent.modification', "Parent {$parent->code_parent} modifié.", request());

            return $parent->fresh('utilisateur');
        });
    }

    public function reinitialiserMotDePasse(Utilisateur $admin, Utilisateur $cible, string $motDePasse): void
    {
        $this->exigerAdministrateur($admin);
        $cible->mot_de_passe = $motDePasse;
        $cible->save();
        $cible->tokens()->delete();
        $this->journal->ecrire(
            $admin,
            'compte.mot_de_passe',
            "Mot de passe réinitialisé pour l’utilisateur {$cible->id_utilisateur}.",
            request()
        );
    }

    public function supprimerEnseignant(Utilisateur $admin, Enseignant $enseignant): void
    {
        $this->exigerAdministrateur($admin);

        DB::transaction(function () use ($admin, $enseignant) {
            $enseignant->load('utilisateur');
            $code = $enseignant->code_enseignant;
            $utilisateur = $enseignant->utilisateur;

            $evaluations = Evaluation::query()->where('code_enseignant', $code)->pluck('num_evaluation');
            if ($evaluations->isNotEmpty()) {
                Signalement::query()->whereIn('num_evaluation', $evaluations)->delete();
                Note::query()->whereIn('num_evaluation', $evaluations)->delete();
                Evaluation::query()->whereIn('num_evaluation', $evaluations)->delete();
            }

            $supports = SupportCours::query()->where('code_enseignant', $code)->get();
            foreach ($supports as $support) {
                $this->supprimerFichierSupport($support);
                $support->delete();
            }

            Affectation::query()->where('code_enseignant', $code)->delete();
            $enseignant->delete();
            if ($utilisateur) {
                $this->supprimerCompteUtilisateur($utilisateur);
            }

            $this->journal->ecrire($admin, 'enseignant.suppression', "Enseignant {$code} supprimé.", request());
        });
    }

    public function supprimerEtudiant(Utilisateur $admin, Etudiant $etudiant): void
    {
        $this->exigerAdministrateur($admin);

        DB::transaction(function () use ($admin, $etudiant) {
            $etudiant->load('utilisateur');
            $matricule = $etudiant->matricule;
            $utilisateur = $etudiant->utilisateur;

            Signalement::query()->where('matricule', $matricule)->delete();
            Note::query()->where('matricule', $matricule)->delete();
            FicheVersement::query()->where('matricule', $matricule)->delete();
            DB::table('rattacher')->where('matricule', $matricule)->delete();
            DemandeRattachement::query()->where('matricule', $matricule)->delete();
            Inscription::query()->where('matricule', $matricule)->delete();

            $etudiant->delete();
            if ($utilisateur) {
                $this->supprimerCompteUtilisateur($utilisateur);
            }

            $this->journal->ecrire($admin, 'etudiant.suppression', "Étudiant {$matricule} supprimé.", request());
        });
    }

    public function supprimerParent(Utilisateur $admin, ParentTuteur $parent): void
    {
        $this->exigerAdministrateur($admin);

        DB::transaction(function () use ($admin, $parent) {
            $parent->load('utilisateur');
            $code = $parent->code_parent;
            $utilisateur = $parent->utilisateur;

            DB::table('rattacher')->where('code_parent', $code)->delete();
            DemandeRattachement::query()->where('code_parent', $code)->delete();
            $parent->delete();
            if ($utilisateur) {
                $this->supprimerCompteUtilisateur($utilisateur);
            }

            $this->journal->ecrire($admin, 'compte.parent.suppression', "Compte parent {$code} supprimé.", request());
        });
    }

    public function deposerSupport(Utilisateur $acteur, array $donnees, UploadedFile $fichier): SupportCours
    {
        $codeEnseignant = $donnees['code_enseignant'] ?? $acteur->enseignant?->code_enseignant;
        if ($acteur->role === 'enseignant' && $codeEnseignant !== $acteur->enseignant?->code_enseignant) {
            throw new AuthorizationException('Vous ne pouvez déposer un support qu’en votre nom.');
        }

        if (! in_array($acteur->role, ['enseignant', 'administrateur'], true)) {
            throw new AuthorizationException('Dépôt réservé aux enseignants et à l’administration.');
        }

        $affecte = Affectation::query()
            ->where('code_enseignant', $codeEnseignant)
            ->where('code_matiere', $donnees['code_matiere'])
            ->whereHas('classe', function ($q) use ($donnees) {
                $q->where('code_filiere', $donnees['code_filiere'])
                    ->where('code_niveau', $donnees['code_niveau']);
            })
            ->exists();

        if (! $affecte) {
            throw ValidationException::withMessages([
                'code_matiere' => 'Cet enseignant n’est pas affecté à cette matière pour la filière et le niveau choisis.',
            ]);
        }

        $extension = strtolower($fichier->getClientOriginalExtension());
        $nomStocke = Str::uuid()->toString().'.'.$extension;
        $chemin = $fichier->storeAs('', $nomStocke, 'supports');

        $support = SupportCours::query()->create([
            'code_support' => $this->prochainCode('SUP', SupportCours::class, 'code_support'),
            'titre_support' => $donnees['titre'],
            'description_support' => $donnees['description'] ?? null,
            'fichier_support' => $chemin,
            'nom_original' => $fichier->getClientOriginalName(),
            'date_depot' => now(),
            'code_enseignant' => $codeEnseignant,
            'code_matiere' => $donnees['code_matiere'],
            'code_filiere' => $donnees['code_filiere'],
            'code_niveau' => $donnees['code_niveau'],
        ]);

        $annee = AnneeScolaire::query()->where('est_courante', true)->value('code_annee');
        $ids = Etudiant::query()
            ->whereHas('inscriptions', function ($q) use ($donnees, $annee) {
                $q->when($annee, fn ($query) => $query->where('code_annee', $annee))
                    ->whereHas('classe', function ($classe) use ($donnees) {
                        $classe->where('code_filiere', $donnees['code_filiere'])
                            ->where('code_niveau', $donnees['code_niveau']);
                    });
            })
            ->pluck('id_utilisateur');

        $this->notifications->envoyerPlusieurs(
            $ids,
            'support',
            'Nouveau support de cours : '.$support->titre_support
        );
        $this->journal->ecrire($acteur, 'support.depot', "Support {$support->code_support} déposé.", request());

        return $support;
    }

    public function supprimerFichierSupport(SupportCours $support): void
    {
        if ($support->fichier_support && Storage::disk('supports')->exists($support->fichier_support)) {
            Storage::disk('supports')->delete($support->fichier_support);
        }
    }

    private function mettreAJourIdentite(Utilisateur $utilisateur, array $donnees): void
    {
        if (! empty($donnees['email']) && $donnees['email'] !== $utilisateur->email) {
            $pris = Utilisateur::query()
                ->where('email', $donnees['email'])
                ->where('id_utilisateur', '!=', $utilisateur->id_utilisateur)
                ->exists();
            if ($pris) {
                throw ValidationException::withMessages([
                    'email' => 'Cette adresse e-mail est déjà utilisée.',
                ]);
            }
            $utilisateur->email = $donnees['email'];
        }

        if (array_key_exists('nom', $donnees) && filled($donnees['nom'])) {
            $utilisateur->nom_utilisateur = $donnees['nom'];
        }
        if (array_key_exists('prenoms', $donnees) && filled($donnees['prenoms'])) {
            $utilisateur->prenoms_utilisateur = $donnees['prenoms'];
        }
        if (array_key_exists('telephone', $donnees)) {
            $utilisateur->telephone_utilisateur = $donnees['telephone'];
        }
        $utilisateur->save();
    }

    private function exigerAdministrateur(Utilisateur $admin): void
    {
        if ($admin->role !== 'administrateur') {
            throw new AuthorizationException('Action réservée à l’administration.');
        }
    }

    private function supprimerCompteUtilisateur(Utilisateur $utilisateur): void
    {
        $utilisateur->tokens()->delete();
        Notification::query()->where('id_utilisateur', $utilisateur->id_utilisateur)->delete();
        FicheVersement::query()->where('id_utilisateur', $utilisateur->id_utilisateur)->update(['id_utilisateur' => null]);
        $utilisateur->delete();
    }

    public function prochainCode(string $prefixe, string $modele, string $colonne): string
    {
        $dernier = $modele::query()->where($colonne, 'like', $prefixe.'%')->orderByDesc($colonne)->value($colonne);
        $suivant = 1;
        if ($dernier && preg_match('/(\d+)$/', $dernier, $m)) {
            $suivant = ((int) $m[1]) + 1;
        }

        return $prefixe.str_pad((string) $suivant, 4, '0', STR_PAD_LEFT);
    }
}
