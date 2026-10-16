<?php

namespace Database\Seeders;

use App\Models\Affectation;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\DemandeRattachement;
use App\Models\Enseignant;
use App\Models\Etudiant;
use App\Models\Evaluation;
use App\Models\Filiere;
use App\Models\Inscription;
use App\Models\Matiere;
use App\Models\Niveau;
use App\Models\Note;
use App\Models\ParentTuteur;
use App\Models\Programme;
use App\Models\Tranche;
use App\Models\Utilisateur;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new RuntimeException('Les comptes de démonstration ne sont créés qu’en environnement local.');
        }

        DB::transaction(function () {
            $annee = AnneeScolaire::query()->updateOrCreate(
                ['code_annee' => '2025-2026'],
                ['libelle_annee' => '2025-2026', 'est_courante' => true]
            );

            $filieres = [
                'IDA' => 'Informatique Développeur d\'Application',
                'LT' => 'Logistique Transport',
                'FC' => 'Finance Comptabilité',
                'GC' => 'Gestion Commerciale',
                'TH' => 'Tourisme et Hôtellerie',
            ];
            foreach ($filieres as $code => $libelle) {
                Filiere::query()->updateOrCreate(['code_filiere' => $code], ['libelle_filiere' => $libelle]);
            }

            Niveau::query()->updateOrCreate(['code_niveau' => 'N1'], ['libelle_niveau' => 'Première année']);
            Niveau::query()->updateOrCreate(['code_niveau' => 'N2'], ['libelle_niveau' => 'Deuxième année']);

            $classes = [
                ['IDA1A', 'IDA 1 A', 'IDA', 'N1'],
                ['IDA1B', 'IDA 1 B', 'IDA', 'N1'],
                ['FC1A', 'FC 1 A', 'FC', 'N1'],
            ];
            foreach ($classes as [$code, $libelle, $filiere, $niveau]) {
                Classe::query()->updateOrCreate(
                    ['code_classe' => $code],
                    ['libelle_classe' => $libelle, 'code_filiere' => $filiere, 'code_niveau' => $niveau]
                );
            }

            $matieres = [
                'ALGO' => 'Algorithmique',
                'BDD' => 'Bases de données',
                'ANG' => 'Anglais',
                'MATH' => 'Mathématiques',
                'COMPTA' => 'Comptabilité générale',
            ];
            foreach ($matieres as $code => $libelle) {
                Matiere::query()->updateOrCreate(['code_matiere' => $code], ['libelle_matiere' => $libelle]);
            }

            $programmes = [
                ['ALGO', 'IDA', 'N1', 3],
                ['BDD', 'IDA', 'N1', 3],
                ['ANG', 'IDA', 'N1', 1],
                ['MATH', 'IDA', 'N1', 2],
                ['COMPTA', 'FC', 'N1', 3],
                ['ANG', 'FC', 'N1', 1],
            ];
            foreach ($programmes as [$matiere, $filiere, $niveau, $coefficient]) {
                DB::table('programmer')->updateOrInsert(
                    ['code_matiere' => $matiere, 'code_filiere' => $filiere, 'code_niveau' => $niveau],
                    ['coefficient' => $coefficient]
                );
            }

            $motDePasse = 'Demo-2026!';

            $admin = $this->utilisateur('admin@plateforme.test', 'Koulibaly', 'Awa', 'administrateur', '0100000001', $motDePasse);
            $enseignantUser = $this->utilisateur('enseignant@plateforme.test', 'Koffi', 'Jean', 'enseignant', '0100000002', $motDePasse);
            $etudiantUser = $this->utilisateur('etudiant@plateforme.test', 'Konan', 'Aya', 'etudiant', '0100000003', $motDePasse);
            $etudiant2User = $this->utilisateur('etudiant2@plateforme.test', 'Diallo', 'Moussa', 'etudiant', '0100000004', $motDePasse);
            $parentUser = $this->utilisateur('parent@plateforme.test', 'Konan', 'Adjoua', 'parent', '0700000001', $motDePasse);
            $parentAttente = $this->utilisateur('parent.attente@plateforme.test', 'Nguessan', 'Serge', 'parent', '0700000002', $motDePasse, 'en_attente');

            Enseignant::query()->updateOrCreate(['code_enseignant' => 'ENS0001'], ['id_utilisateur' => $enseignantUser->id_utilisateur]);
            Etudiant::query()->updateOrCreate(['matricule' => 'ETU-2025-001'], [
                'nom_parent' => 'Konan Adjoua',
                'telephone_parent' => '0700000001',
                'id_utilisateur' => $etudiantUser->id_utilisateur,
            ]);
            Etudiant::query()->updateOrCreate(['matricule' => 'ETU-2025-002'], [
                'nom_parent' => 'Diallo Fatou',
                'telephone_parent' => '0700000099',
                'id_utilisateur' => $etudiant2User->id_utilisateur,
            ]);
            ParentTuteur::query()->updateOrCreate(['code_parent' => 'PAR0001'], ['id_utilisateur' => $parentUser->id_utilisateur]);
            ParentTuteur::query()->updateOrCreate(['code_parent' => 'PAR0002'], ['id_utilisateur' => $parentAttente->id_utilisateur]);

            DB::table('rattacher')->updateOrInsert(
                ['code_parent' => 'PAR0001', 'matricule' => 'ETU-2025-001'],
                ['lien_parente' => 'mère']
            );

            DemandeRattachement::query()->updateOrCreate(
                ['code_parent' => 'PAR0002', 'matricule' => 'ETU-2025-002'],
                ['lien_parente' => 'père', 'statut' => 'en_attente', 'date_demande' => now()]
            );

            foreach (['ETU-2025-001', 'ETU-2025-002'] as $matricule) {
                DB::table('s_inscrire')->updateOrInsert(
                    ['matricule' => $matricule, 'code_annee' => $annee->code_annee],
                    ['code_classe' => 'IDA1A']
                );
            }

            DB::table('enseigner')->updateOrInsert([
                'code_enseignant' => 'ENS0001',
                'code_matiere' => 'ALGO',
                'code_classe' => 'IDA1A',
            ]);
            DB::table('enseigner')->updateOrInsert([
                'code_enseignant' => 'ENS0001',
                'code_matiere' => 'BDD',
                'code_classe' => 'IDA1A',
            ]);

            $publiee = Evaluation::query()->firstOrCreate(
                [
                    'type_evaluation' => 'devoir',
                    'code_matiere' => 'ALGO',
                    'code_classe' => 'IDA1A',
                    'code_enseignant' => 'ENS0001',
                    'code_annee' => $annee->code_annee,
                    'semestre' => 1,
                ],
                [
                    'date_evaluation' => '2025-11-12',
                    'statut_publication' => 'publiee',
                    'date_publication' => now(),
                ]
            );
            DB::table('obtenir')->updateOrInsert(['matricule' => 'ETU-2025-001', 'num_evaluation' => $publiee->num_evaluation], ['note' => 14]);
            DB::table('obtenir')->updateOrInsert(['matricule' => 'ETU-2025-002', 'num_evaluation' => $publiee->num_evaluation], ['note' => 11.5]);

            $interro = Evaluation::query()->firstOrCreate(
                [
                    'type_evaluation' => 'interrogation',
                    'code_matiere' => 'ALGO',
                    'code_classe' => 'IDA1A',
                    'code_enseignant' => 'ENS0001',
                    'code_annee' => $annee->code_annee,
                    'semestre' => 1,
                ],
                [
                    'date_evaluation' => '2025-12-03',
                    'statut_publication' => 'publiee',
                    'date_publication' => now(),
                ]
            );
            DB::table('obtenir')->updateOrInsert(['matricule' => 'ETU-2025-001', 'num_evaluation' => $interro->num_evaluation], ['note' => 16]);

            Evaluation::query()->firstOrCreate(
                [
                    'type_evaluation' => 'devoir',
                    'code_matiere' => 'BDD',
                    'code_classe' => 'IDA1A',
                    'code_enseignant' => 'ENS0001',
                    'code_annee' => $annee->code_annee,
                    'semestre' => 1,
                ],
                [
                    'date_evaluation' => '2026-01-20',
                    'statut_publication' => 'brouillon',
                ]
            );

            $tranches = [
                ['T1-IDA-N1', '1re tranche', 150000, '2025-11-30'],
                ['T2-IDA-N1', '2e tranche', 150000, '2026-02-28'],
                ['T3-IDA-N1', '3e tranche', 100000, '2026-05-31'],
                ['T1-FC-N1', '1re tranche', 120000, '2025-11-30'],
            ];
            foreach ($tranches as [$code, $libelle, $montant, $date]) {
                $filiere = str_contains($code, 'FC') ? 'FC' : 'IDA';
                Tranche::query()->updateOrCreate(
                    ['code_tranche' => $code],
                    [
                        'libelle_tranche' => $libelle,
                        'montant_tranche' => $montant,
                        'date_limite' => $date,
                        'code_filiere' => $filiere,
                        'code_niveau' => 'N1',
                        'code_annee' => $annee->code_annee,
                    ]
                );
            }

            unset($admin);
        });
    }

    private function utilisateur(string $email, string $nom, string $prenoms, string $role, string $telephone, string $motDePasse, string $statut = 'valide'): Utilisateur
    {
        $existant = Utilisateur::query()->where('email', $email)->first();
        if ($existant) {
            return $existant;
        }

        return Utilisateur::query()->create([
            'nom_utilisateur' => $nom,
            'prenoms_utilisateur' => $prenoms,
            'email' => $email,
            'telephone_utilisateur' => $telephone,
            'mot_de_passe' => $motDePasse,
            'role' => $role,
            'statut_compte' => $statut,
            'date_creation' => now(),
        ]);
    }
}
