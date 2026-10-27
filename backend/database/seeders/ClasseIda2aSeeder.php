<?php

namespace Database\Seeders;

use App\Models\Classe;
use App\Models\EmploiDuTemps;
use App\Models\Etudiant;
use App\Models\Filiere;
use App\Models\Inscription;
use App\Models\Matiere;
use App\Models\Niveau;
use App\Models\Utilisateur;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Données réelles issues des documents liste.png et emploi.png (BTS IDA2/A).
 */
class ClasseIda2aSeeder extends Seeder
{
    public function run(): void
    {
        Filiere::query()->updateOrCreate(
            ['code_filiere' => 'IDA'],
            ['libelle_filiere' => 'Informatique – Développeur d\'Application']
        );
        Niveau::query()->updateOrCreate(['code_niveau' => 'N2'], ['libelle_niveau' => 'Deuxième année']);
        Classe::query()->updateOrCreate(
            ['code_classe' => 'IDA2A'],
            ['libelle_classe' => 'BTS IDA2/A', 'code_filiere' => 'IDA', 'code_niveau' => 'N2']
        );

        $annee = DB::table('annees_scolaires')->where('est_courante', true)->value('code_annee')
            ?? DB::table('annees_scolaires')->value('code_annee')
            ?? '2025-2026';

        if (! DB::table('annees_scolaires')->where('code_annee', $annee)->exists()) {
            DB::table('annees_scolaires')->insert([
                'code_annee' => $annee,
                'libelle_annee' => $annee,
                'est_courante' => true,
            ]);
        }

        $matieres = [
            'ANALYSE' => 'Analyse',
            'ALGO' => 'Algorithme',
            'CGEST' => 'Contrôle de gestion',
            'VB' => 'Visual Basic',
            'PROJET' => 'Projet',
            'NEGO' => 'Négociation informatique',
            'FR' => 'Français',
            'PHP' => 'Langage PHP',
            'MATHFIN' => 'Mathématiques financières et RO',
            'MATHGLE' => 'Mathématiques générales / professionnelles',
            'LC' => 'Langage C',
            'RESEAU' => 'Réseaux et télécoms',
            'BDD' => 'Bases de données',
            'ANG' => 'Anglais',
            'DROIT' => 'Droit',
            'ECO' => 'Économie',
            'CONF' => 'Conférence',
        ];
        foreach ($matieres as $code => $libelle) {
            Matiere::query()->updateOrCreate(['code_matiere' => $code], ['libelle_matiere' => $libelle]);
        }

        $etudiants = [
            ['33351', 'ALI', 'FATOUMATA'],
            ['33342', 'APPAHOU', 'KOUASSI RODRIGUE'],
            ['33304', 'ATSE', 'TRUCH NELLY ROXANE'],
            ['33292', 'BOKO', 'CHRIS IVAN NATHAN'],
            ['33218', 'BROU', 'N\'GUESSAN ANNE PRISCILLE'],
            ['33210', 'DIE', 'BOGUINARD YANN NEVE TRES'],
            ['33159', 'DJATE', 'SIKA MARIUS DONALD JEFFE'],
            ['33238', 'FAIZOU', 'REHANATOU'],
            ['33243', 'FEGONE', 'CHRIST DAVID'],
            ['33132', 'GARBA', 'MOHAMED'],
            ['33237', 'GNAZALE', 'GOHOUN YVES-EMMANUEL'],
            ['33286', 'GUEI', 'ANNE HERSILE DIVINE'],
            ['32954', 'GUEPIE', 'ANNE MARIE-CLAUDE'],
            ['33246', 'KACOU', 'GRACE BERENICE'],
            ['33160', 'KOFFI', 'APIE QUETURA MARC PRUNE'],
            ['33251', 'KOHON', 'ANGE ULRICH'],
            ['33164', 'KONE', 'FATOUMATA ZAHARA'],
            ['33217', 'KOUA', 'BLANCHE EVRADE ORNELA'],
            ['33561', 'KOUAKOU', 'EZECHIEL'],
            ['33175', 'KOUASSI', 'AMENAN NELLY DANIELLE'],
            ['32953', 'KOUASSI', 'KONAN FRANCK BOHUET'],
            ['32979', 'LALLIET', 'BASSI MARIE-PIERRE ASTRID'],
            ['33311', 'N\'DA', 'KOUADIO GRACE DEBORA'],
            ['33345', 'NGUESSAN', 'APPEKOUN AMBROISE'],
            ['32998', 'OUATTARA', 'KOUAME AMADOU'],
            ['33171', 'OYOU', 'ERNEST EMMANUEL JUNIOR'],
            ['33232', 'SE', 'SOULEYMANE'],
            ['33254', 'SEKA', 'LOIC OTHNIEL'],
            ['33298', 'SEKONGO', 'FOUNGNIGUE RICHARD'],
            ['33262', 'SORO', 'TIEREGNIMIN LOUISE'],
            ['33180', 'SOUMAHORO', 'OUMOU AICHA'],
            ['33165', 'TCHAPO', 'ADEKE JOSEPH DARRYL'],
            ['33166', 'TOKOLA', 'FATOUMATA'],
            ['33214', 'TOURE', 'MOHAMED'],
            ['32972', 'TRAORE', 'MOHAMED BELKO'],
            ['33257', 'YAO', 'PACOME CHRIST'],
            ['33572', 'YEO', 'DOKATIENIN ELIE-ARTHUR'],
            ['33322', 'ZIABE', 'AGNERO ISRAEL MEDERIC'],
        ];

        foreach ($etudiants as [$matricule, $nom, $prenoms]) {
            $email = 'etudiant.'.$matricule.'@plateforme.test';
            $utilisateur = Utilisateur::query()->updateOrCreate(
                ['email' => $email],
                [
                    'nom_utilisateur' => $nom,
                    'prenoms_utilisateur' => $prenoms,
                    'telephone_utilisateur' => null,
                    'mot_de_passe' => 'Demo-2026!',
                    'role' => 'etudiant',
                    'statut_compte' => 'valide',
                    'date_creation' => now(),
                ]
            );
            Etudiant::query()->updateOrCreate(
                ['matricule' => $matricule],
                ['id_utilisateur' => $utilisateur->id_utilisateur]
            );
            Inscription::query()->updateOrCreate(
                ['matricule' => $matricule, 'code_annee' => $annee],
                ['code_classe' => 'IDA2A']
            );
        }

        EmploiDuTemps::query()->where('code_classe', 'IDA2A')->where('code_annee', $annee)->delete();

        $seances = [
            // lundi = 1
            [1, '07:15', '10:00', 'ANALYSE', 'Analyse', 'KADJO ABBLEY', 'SALLE 18'],
            [1, '10:15', '11:10', 'ALGO', 'Algorithme', 'LIASSOU MURANGA', 'SALLE 18'],
            [1, '11:10', '13:00', 'ALGO', 'Algorithme', 'LIASSOU MURANGA', 'SALLE 18'],
            [1, '14:20', '16:10', 'CGEST', 'Contrôle de gestion', 'BAKAYOKO VASSIDIKI', 'SALLE 18'],
            [1, '16:10', '18:00', 'VB', 'Visual Basic', 'JAHOU FENAHOU', 'INFO B'],
            // mardi
            [2, '07:15', '09:05', 'PROJET', 'Projet', 'TANHI VIRGINIE', 'SALLE 18'],
            [2, '09:05', '10:00', 'NEGO', 'Négociation informatique', 'TANHI VIRGINIE', 'SALLE 18'],
            [2, '10:15', '13:00', 'FR', 'Français', 'CRESPIN MATHIEU', 'SALLE 18'],
            // mercredi
            [3, '07:15', '10:00', 'PHP', 'Langage PHP', 'JAHOU FENAHOU', 'INFO B'],
            [3, '10:15', '12:05', 'MATHFIN', 'Mathématiques financières et RO', 'GOHI ERNEST', 'SALLE 18'],
            [3, '13:25', '16:10', 'MATHGLE', 'Mathématiques générales / professionnelles', 'SANOGO BABA', 'SALLE 18'],
            // jeudi
            [4, '07:15', '09:05', 'LC', 'Langage C', 'LIASSOU MURANGA', 'INFO B'],
            [4, '09:05', '10:00', 'RESEAU', 'Réseaux et télécoms', 'ADJE ATTICORA', 'INFO B'],
            [4, '10:15', '11:10', 'RESEAU', 'Réseaux et télécoms', 'ADJE ATTICORA', 'INFO B'],
            [4, '11:10', '13:00', 'BDD', 'Bases de données', 'KADJO ABBLEY', 'INFO B'],
            [4, '14:20', '16:10', 'ANG', 'Anglais', 'KONE HAOUA', 'SALLE 18'],
            // vendredi
            [5, '07:15', '10:00', 'DROIT', 'Droit', 'OUATTARA SOULEYMANE', 'SALLE 18'],
            [5, '10:15', '13:00', 'ECO', 'Économie', 'N\'GUESSAN BERNARD', 'SALLE 18'],
            [5, '14:20', '16:10', 'CONF', 'Conférence', null, null],
        ];

        foreach ($seances as [$jour, $debut, $fin, $code, $libelle, $enseignant, $salle]) {
            EmploiDuTemps::query()->create([
                'code_classe' => 'IDA2A',
                'code_annee' => $annee,
                'jour_semaine' => $jour,
                'heure_debut' => $debut,
                'heure_fin' => $fin,
                'code_matiere' => $code,
                'libelle_matiere' => $libelle,
                'enseignant' => $enseignant,
                'salle' => $salle,
                'provisoire' => true,
            ]);
        }

        $enseignantsClasse = [
            ['ENS-KADJO', 'KADJO', 'ABBLEY', 'enseignant.kadjo@plateforme.test', ['ANALYSE', 'BDD']],
            ['ENS-LIASSOU', 'LIASSOU', 'MURANGA', 'enseignant.liassou@plateforme.test', ['ALGO', 'LC']],
            ['ENS-BAKAYOKO', 'BAKAYOKO', 'VASSIDIKI', 'enseignant.bakayoko@plateforme.test', ['CGEST']],
            ['ENS-JAHOU', 'JAHOU', 'FENAHOU', 'enseignant.jahou@plateforme.test', ['VB', 'PHP']],
            ['ENS-TANHI', 'TANHI', 'VIRGINIE', 'enseignant.tanhi@plateforme.test', ['PROJET', 'NEGO']],
            ['ENS-CRESPIN', 'CRESPIN', 'MATHIEU', 'enseignant.crespin@plateforme.test', ['FR']],
            ['ENS-GOHI', 'GOHI', 'ERNEST', 'enseignant.gohi@plateforme.test', ['MATHFIN']],
            ['ENS-SANOGO', 'SANOGO', 'BABA', 'enseignant.sanogo@plateforme.test', ['MATHGLE']],
            ['ENS-ADJE', 'ADJE', 'ATTICORA', 'enseignant.adje@plateforme.test', ['RESEAU']],
            ['ENS-KONEH', 'KONE', 'HAOUA', 'enseignant.kone@plateforme.test', ['ANG']],
            ['ENS-OUATTARA', 'OUATTARA', 'SOULEYMANE', 'enseignant.ouattara@plateforme.test', ['DROIT']],
            ['ENS-NGUESSAN', 'N\'GUESSAN', 'BERNARD', 'enseignant.nguessan@plateforme.test', ['ECO']],
        ];

        foreach ($enseignantsClasse as [$code, $nom, $prenoms, $email, $matieresEnseignees]) {
            $utilisateur = Utilisateur::query()->updateOrCreate(
                ['email' => $email],
                [
                    'nom_utilisateur' => $nom,
                    'prenoms_utilisateur' => $prenoms,
                    'telephone_utilisateur' => null,
                    'mot_de_passe' => 'Demo-2026!',
                    'role' => 'enseignant',
                    'statut_compte' => 'valide',
                    'date_creation' => now(),
                ]
            );
            DB::table('enseignants')->updateOrInsert(
                ['code_enseignant' => $code],
                ['id_utilisateur' => $utilisateur->id_utilisateur]
            );
            foreach ($matieresEnseignees as $matiere) {
                DB::table('enseigner')->updateOrInsert([
                    'code_enseignant' => $code,
                    'code_matiere' => $matiere,
                    'code_classe' => 'IDA2A',
                ]);
            }
        }
    }
}
