<?php

namespace App\Services;

use App\Models\Affectation;
use App\Models\AnneeScolaire;
use App\Models\DemandeRattachement;
use App\Models\Enseignant;
use App\Models\Etudiant;
use App\Models\ParentTuteur;
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
            $utilisateur = Utilisateur::query()->create([
                'nom_utilisateur' => $donnees['nom'],
                'prenoms_utilisateur' => $donnees['prenoms'],
                'email' => $donnees['email'],
                'telephone_utilisateur' => $donnees['telephone'],
                'mot_de_passe' => $donnees['mot_de_passe'],
                'role' => 'parent',
                'statut_compte' => 'en_attente',
                'date_creation' => now(),
            ]);

            $code = $this->prochainCode('PAR', ParentTuteur::class, 'code_parent');
            ParentTuteur::query()->create([
                'code_parent' => $code,
                'id_utilisateur' => $utilisateur->id_utilisateur,
            ]);

            foreach ($donnees['enfants'] as $enfant) {
                if (! Etudiant::query()->where('matricule', $enfant['matricule'])->exists()) {
                    throw ValidationException::withMessages([
                        'enfants' => "Aucun étudiant ne porte le matricule {$enfant['matricule']}.",
                    ]);
                }

                DemandeRattachement::query()->create([
                    'code_parent' => $code,
                    'matricule' => $enfant['matricule'],
                    'lien_parente' => $enfant['lien_parente'] ?? null,
                    'statut' => 'en_attente',
                    'date_demande' => now(),
                ]);
            }

            $admins = Utilisateur::query()->where('role', 'administrateur')->where('statut_compte', 'valide')->pluck('id_utilisateur');
            $this->notifications->envoyerPlusieurs(
                $admins,
                'compte_parent',
                "Nouvelle demande de compte parent : {$utilisateur->nomComplet()}."
            );
            $this->journal->ecrire($utilisateur, 'compte.parent.demande', "Demande de compte parent {$code}.", request());

            return $utilisateur->fresh('parentRattache');
        });
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
