<?php

namespace Database\Seeders;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\EmploiDuTemps;
use App\Models\Enseignant;
use App\Models\Etudiant;
use App\Models\Filiere;
use App\Models\Inscription;
use App\Models\Matiere;
use App\Models\Niveau;
use App\Models\Tranche;
use App\Models\Utilisateur;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Listes et emplois fictifs (10 élèves / classe) d’après
 * https://www.epcci.edu.ci/cours-et-formations/
 * La classe réelle IDA2A n’est pas remplacée.
 */
class ClassesFictivesSeeder extends Seeder
{
    private const MOT_DE_PASSE = 'Demo-2026!';

    private const CRENEAUX = [
        [1, '07:15', '10:00'],
        [1, '10:15', '13:00'],
        [1, '14:20', '16:10'],
        [2, '07:15', '10:00'],
        [2, '10:15', '13:00'],
        [2, '14:20', '16:10'],
        [3, '07:15', '10:00'],
        [3, '10:15', '13:00'],
        [3, '14:20', '16:10'],
        [4, '07:15', '10:00'],
        [4, '10:15', '13:00'],
        [4, '14:20', '16:10'],
        [5, '07:15', '10:00'],
        [5, '10:15', '13:00'],
        [5, '14:20', '16:10'],
    ];

    public function run(): void
    {
        $annee = AnneeScolaire::query()->where('est_courante', true)->value('code_annee')
            ?? AnneeScolaire::query()->value('code_annee')
            ?? '2025-2026';

        AnneeScolaire::query()->updateOrCreate(
            ['code_annee' => $annee],
            ['libelle_annee' => $annee, 'est_courante' => true]
        );
        Niveau::query()->updateOrCreate(['code_niveau' => 'N1'], ['libelle_niveau' => 'Première année']);
        Niveau::query()->updateOrCreate(['code_niveau' => 'N2'], ['libelle_niveau' => 'Deuxième année']);

        $filieres = [
            'AD' => 'Assistanat de Direction',
            'IDA' => 'Informatique – Développeur d\'Application',
            'LT' => 'Logistique',
            'FC' => 'Finances Comptabilité et Gestion d\'Entreprises',
            'GC' => 'Gestion Commerciale',
            'TH' => 'Tourisme et Hôtellerie',
        ];
        foreach ($filieres as $code => $libelle) {
            Filiere::query()->updateOrCreate(['code_filiere' => $code], ['libelle_filiere' => $libelle]);
        }

        $matieres = $this->matieres();
        foreach ($matieres as $code => $libelle) {
            Matiere::query()->updateOrCreate(['code_matiere' => $code], ['libelle_matiere' => $libelle]);
        }

        foreach ($this->nouveauxEnseignants() as $code => [$nom, $prenoms, $email]) {
            $utilisateur = Utilisateur::query()->updateOrCreate(
                ['email' => $email],
                [
                    'nom_utilisateur' => $nom,
                    'prenoms_utilisateur' => $prenoms,
                    'telephone_utilisateur' => null,
                    'mot_de_passe' => self::MOT_DE_PASSE,
                    'role' => 'enseignant',
                    'statut_compte' => 'valide',
                    'date_creation' => now(),
                ]
            );
            Enseignant::query()->updateOrCreate(
                ['code_enseignant' => $code],
                ['id_utilisateur' => $utilisateur->id_utilisateur]
            );
        }

        $programmes = $this->programmesParFiliere();
        $titulaires = $this->titulaires();

        foreach ($programmes as $codeFiliere => $codesMatieres) {
            foreach (['N1', 'N2'] as $niveau) {
                $codeClasse = $codeFiliere.substr($niveau, 1).'A';
                if ($codeClasse === 'IDA2A') {
                    continue;
                }

                Classe::query()->updateOrCreate(
                    ['code_classe' => $codeClasse],
                    [
                        'libelle_classe' => 'BTS '.$codeFiliere.' '.substr($niveau, 1).'/A',
                        'code_filiere' => $codeFiliere,
                        'code_niveau' => $niveau,
                    ]
                );

                foreach ($codesMatieres as $index => $codeMatiere) {
                    DB::table('programmer')->updateOrInsert(
                        [
                            'code_matiere' => $codeMatiere,
                            'code_filiere' => $codeFiliere,
                            'code_niveau' => $niveau,
                        ],
                        ['coefficient' => $index < 6 ? 2 : 3]
                    );
                    $codeEns = $titulaires[$codeMatiere] ?? 'ENS-CRESPIN';
                    DB::table('enseigner')->updateOrInsert([
                        'code_enseignant' => $codeEns,
                        'code_matiere' => $codeMatiere,
                        'code_classe' => $codeClasse,
                    ]);
                }

                $this->inscrireDix($codeClasse, $codeFiliere, $niveau, $annee);
                $this->emploi($codeClasse, $annee, $codesMatieres, $matieres, $titulaires, 'SALLE '.$codeFiliere);

                $this->assurerTranches($codeFiliere, $niveau, $annee);
            }
        }

        Classe::query()->updateOrCreate(
            ['code_classe' => 'IDA1B'],
            ['libelle_classe' => 'BTS IDA 1/B', 'code_filiere' => 'IDA', 'code_niveau' => 'N1']
        );
        foreach ($programmes['IDA'] as $codeMatiere) {
            $codeEns = $titulaires[$codeMatiere] ?? 'ENS-CRESPIN';
            DB::table('enseigner')->updateOrInsert([
                'code_enseignant' => $codeEns,
                'code_matiere' => $codeMatiere,
                'code_classe' => 'IDA1B',
            ]);
        }
        $this->inscrireDix('IDA1B', 'IDA', 'N1', $annee, 'B');
        $this->emploi('IDA1B', $annee, $programmes['IDA'], $matieres, $titulaires, 'SALLE IDA-B');
    }

    private function assurerTranches(string $filiere, string $niveau, string $annee): void
    {
        $lignes = [
            ['T1', '1re tranche', 150000, '2025-11-30'],
            ['T2', '2e tranche', 150000, '2026-02-28'],
            ['T3', '3e tranche', 100000, '2026-05-31'],
        ];
        foreach ($lignes as [$prefixe, $libelle, $montant, $date]) {
            Tranche::query()->firstOrCreate(
                ['code_tranche' => $prefixe.'-'.$filiere.'-'.$niveau],
                [
                    'libelle_tranche' => $libelle,
                    'montant_tranche' => $montant,
                    'date_limite' => $date,
                    'code_filiere' => $filiere,
                    'code_niveau' => $niveau,
                    'code_annee' => $annee,
                ]
            );
        }
    }

    private function inscrireDix(string $codeClasse, string $filiere, string $niveau, string $annee, string $groupe = 'A'): void
    {
        $deja = Inscription::query()->where('code_classe', $codeClasse)->where('code_annee', $annee)->count();
        $aCreer = max(0, 10 - $deja);
        $noms = $this->noms();
        $niveauChiffre = substr($niveau, 1);

        for ($i = 1; $i <= $aCreer; $i++) {
            $matricule = sprintf('F%s%s%s%02d', $filiere, $niveauChiffre, $groupe, $i);
            $matricule = substr($matricule, 0, 20);
            $pair = $noms[($this->hashClasse($codeClasse) + $i) % count($noms)];
            $email = 'etudiant.'.strtolower($matricule).'@plateforme.test';

            $utilisateur = Utilisateur::query()->updateOrCreate(
                ['email' => $email],
                [
                    'nom_utilisateur' => $pair[0],
                    'prenoms_utilisateur' => $pair[1],
                    'telephone_utilisateur' => null,
                    'mot_de_passe' => self::MOT_DE_PASSE,
                    'role' => 'etudiant',
                    'statut_compte' => 'valide',
                    'date_creation' => now(),
                ]
            );
            Etudiant::query()->updateOrCreate(
                ['matricule' => $matricule],
                [
                    'nom_parent' => 'Parent '.$pair[0],
                    'telephone_parent' => '07'.str_pad((string) (10000000 + $i), 8, '0', STR_PAD_LEFT),
                    'id_utilisateur' => $utilisateur->id_utilisateur,
                ]
            );
            Inscription::query()->updateOrCreate(
                ['matricule' => $matricule, 'code_annee' => $annee],
                ['code_classe' => $codeClasse]
            );
        }
    }

    private function emploi(
        string $codeClasse,
        string $annee,
        array $codesMatieres,
        array $libelles,
        array $titulaires,
        string $salle
    ): void {
        EmploiDuTemps::query()->where('code_classe', $codeClasse)->where('code_annee', $annee)->delete();

        foreach (self::CRENEAUX as $index => [$jour, $debut, $fin]) {
            $codeMatiere = $codesMatieres[$index % count($codesMatieres)];
            $codeEns = $titulaires[$codeMatiere] ?? null;
            $nomEns = null;
            if ($codeEns) {
                $enseignant = Enseignant::query()->with('utilisateur')->find($codeEns);
                $nomEns = $enseignant?->utilisateur?->nomComplet();
            }

            EmploiDuTemps::query()->create([
                'code_classe' => $codeClasse,
                'code_annee' => $annee,
                'jour_semaine' => $jour,
                'heure_debut' => $debut,
                'heure_fin' => $fin,
                'code_matiere' => $codeMatiere,
                'libelle_matiere' => $libelles[$codeMatiere] ?? $codeMatiere,
                'enseignant' => $nomEns,
                'salle' => $salle,
                'provisoire' => true,
            ]);
        }
    }

    private function matieres(): array
    {
        return [
            'TEX' => 'Technique d’expression',
            'ANG' => 'Anglais',
            'ANGCOM' => 'Anglais commercial',
            'ANGTEC' => 'Anglais technique',
            'ANGINF' => 'Anglais appliqué à l’informatique',
            'ESP' => 'Espagnol',
            'ECO' => 'Économie générale',
            'EOE' => 'Économie et organisation des entreprises',
            'ECOGES' => 'Économie et gestion',
            'DROIT' => 'Droit des affaires et du travail',
            'INFO' => 'Informatique',
            'INFOAPP' => 'Informatique appliquée',
            'MATHGLE' => 'Mathématiques générales, statistiques, probabilités',
            'MATHFIN' => 'Mathématiques financières et recherche opérationnelle',
            'MATHGES' => 'Mathématiques appliquées à la gestion',
            'MATHSIG' => 'Mathématiques du signal',
            'COMPTA' => 'Comptabilité générale',
            'CPTSOC' => 'Comptabilité des sociétés',
            'CPTANA' => 'Comptabilité analytique',
            'CGEST' => 'Contrôle de gestion',
            'FISCA' => 'Fiscalité',
            'BURCPT' => 'Bureau comptable',
            'GESTPREV' => 'Comptabilité analytique et gestion prévisionnelle',
            'ENTREP' => 'Entrepreneuriat',
            'STENO' => 'Sténographie',
            'DACTY' => 'Dactylographie',
            'ORGA' => 'Organisation administrative',
            'MOYEXP' => 'Moyens d’expression',
            'BUREAU' => 'Bureautique',
            'ETUDE' => 'Étude de cas',
            'GESTSTAT' => 'Gestion – statistiques',
            'ARCHI' => 'Architecture des ordinateurs et téléinformatique',
            'ALGO' => 'Algorithme',
            'LC' => 'Langage Pascal et C',
            'VB' => 'Visual Basic',
            'SYS' => 'Système d’exploitation',
            'MERISE' => 'Méthodologie MERISE',
            'BDD' => 'Base de données',
            'LOGIC' => 'Logiciels',
            'NEGO' => 'Négociation informatique',
            'PROJET' => 'Projet',
            'WEB' => 'Web',
            'MARKET' => 'Fondements marketing et étude de marché',
            'VENTE' => 'Techniques de vente et négociation',
            'STRAT' => 'Stratégie marketing et plan d’action commerciale',
            'DISTRI' => 'Distribution – merchandising',
            'COMINT' => 'Techniques du commerce international',
            'STOCK' => 'Gestion des approvisionnements et des stocks',
            'MKINT' => 'Marketing international',
            'FORCEV' => 'Management de la force de vente',
            'APPRO' => 'Management des approvisionnements et des stocks',
            'LOGPROD' => 'Logistique de production et distribution physique',
            'TRANSIT' => 'Transit, douane et règlements internationaux',
            'FACTU' => 'Facturation',
            'ECONTR' => 'Économie des transports',
            'GESTTR' => 'Gestion des transports',
            'DECIS' => 'Outils d’aide à la décision',
            'MARIT' => 'Expertise maritime et assurance des transports',
            'PORT' => 'Manutention portuaire et consignation',
            'MOYGEN' => 'Gestion des moyens généraux',
            'TECTOUR' => 'Techniques touristiques',
            'TECHOT' => 'Techniques hôtelières',
        ];
    }

    private function programmesParFiliere(): array
    {
        return [
            'AD' => ['TEX', 'ANG', 'ECO', 'ECOGES', 'DROIT', 'INFO', 'STENO', 'DACTY', 'ORGA', 'MOYEXP', 'BUREAU', 'ETUDE', 'GESTSTAT'],
            'FC' => ['TEX', 'ANGCOM', 'ECO', 'EOE', 'DROIT', 'MATHGLE', 'COMPTA', 'CPTSOC', 'CPTANA', 'CGEST', 'FISCA', 'BURCPT', 'MATHFIN', 'INFOAPP'],
            'GC' => ['TEX', 'ANGTEC', 'ECO', 'DROIT', 'EOE', 'MATHGES', 'MARKET', 'VENTE', 'STRAT', 'DISTRI', 'COMINT', 'GESTPREV', 'INFOAPP', 'STOCK'],
            'IDA' => ['TEX', 'ANGINF', 'ECO', 'EOE', 'CGEST', 'DROIT', 'MATHGLE', 'MATHFIN', 'ARCHI', 'ALGO', 'LC', 'VB', 'SYS', 'MERISE', 'BDD'],
            'LT' => ['TEX', 'ANGCOM', 'ECO', 'EOE', 'DROIT', 'INFO', 'COMPTA', 'MATHGLE', 'ENTREP', 'APPRO', 'LOGPROD', 'TRANSIT', 'FACTU', 'GESTTR', 'DECIS'],
            'TH' => ['TEX', 'ANG', 'ESP', 'EOE', 'DROIT', 'INFO', 'COMPTA', 'TECTOUR', 'TECHOT', 'ECO', 'ENTREP'],
        ];
    }

    private function nouveauxEnseignants(): array
    {
        return [
            'ENS-CRESPIN' => ['CRESPIN', 'MATHIEU', 'enseignant.crespin@plateforme.test'],
            'ENS-KONEH' => ['KONE', 'HAOUA', 'enseignant.kone@plateforme.test'],
            'ENS-NGUESSAN' => ['N\'GUESSAN', 'BERNARD', 'enseignant.nguessan@plateforme.test'],
            'ENS-OUATTARA' => ['OUATTARA', 'SOULEYMANE', 'enseignant.ouattara@plateforme.test'],
            'ENS-BAKAYOKO' => ['BAKAYOKO', 'VASSIDIKI', 'enseignant.bakayoko@plateforme.test'],
            'ENS-LIASSOU' => ['LIASSOU', 'MURANGA', 'enseignant.liassou@plateforme.test'],
            'ENS-KADJO' => ['KADJO', 'ABBLEY', 'enseignant.kadjo@plateforme.test'],
            'ENS-JAHOU' => ['JAHOU', 'FENAHOU', 'enseignant.jahou@plateforme.test'],
            'ENS-TANHI' => ['TANHI', 'VIRGINIE', 'enseignant.tanhi@plateforme.test'],
            'ENS-GOHI' => ['GOHI', 'ERNEST', 'enseignant.gohi@plateforme.test'],
            'ENS-SANOGO' => ['SANOGO', 'BABA', 'enseignant.sanogo@plateforme.test'],
            'ENS-ADJE' => ['ADJE', 'ATTICORA', 'enseignant.adje@plateforme.test'],
            'ENS-F-COMPTA' => ['KOFFI', 'AWA', 'enseignant.compta@plateforme.test'],
            'ENS-F-AD' => ['AMANI', 'GRACE', 'enseignant.ad@plateforme.test'],
            'ENS-F-GC' => ['BLOTTO', 'PATRICE', 'enseignant.gc@plateforme.test'],
            'ENS-F-LT' => ['DIABATE', 'SEYDOU', 'enseignant.lt@plateforme.test'],
            'ENS-F-TH' => ['EBA', 'MICHELLE', 'enseignant.th@plateforme.test'],
            'ENS-F-FISCA' => ['FOFANA', 'IBRAHIM', 'enseignant.fisca@plateforme.test'],
        ];
    }

    private function titulaires(): array
    {
        return [
            'TEX' => 'ENS-CRESPIN',
            'ANG' => 'ENS-KONEH',
            'ANGCOM' => 'ENS-KONEH',
            'ANGTEC' => 'ENS-KONEH',
            'ANGINF' => 'ENS-KONEH',
            'ESP' => 'ENS-KONEH',
            'ECO' => 'ENS-NGUESSAN',
            'EOE' => 'ENS-NGUESSAN',
            'ECOGES' => 'ENS-NGUESSAN',
            'DROIT' => 'ENS-OUATTARA',
            'CGEST' => 'ENS-BAKAYOKO',
            'ALGO' => 'ENS-LIASSOU',
            'LC' => 'ENS-LIASSOU',
            'SYS' => 'ENS-LIASSOU',
            'MERISE' => 'ENS-KADJO',
            'BDD' => 'ENS-KADJO',
            'ARCHI' => 'ENS-ADJE',
            'VB' => 'ENS-JAHOU',
            'LOGIC' => 'ENS-JAHOU',
            'WEB' => 'ENS-JAHOU',
            'NEGO' => 'ENS-TANHI',
            'PROJET' => 'ENS-TANHI',
            'MATHFIN' => 'ENS-GOHI',
            'MATHGLE' => 'ENS-SANOGO',
            'MATHGES' => 'ENS-SANOGO',
            'MATHSIG' => 'ENS-SANOGO',
            'COMPTA' => 'ENS-F-COMPTA',
            'CPTSOC' => 'ENS-F-COMPTA',
            'CPTANA' => 'ENS-F-COMPTA',
            'BURCPT' => 'ENS-F-COMPTA',
            'GESTPREV' => 'ENS-F-COMPTA',
            'INFO' => 'ENS-ADJE',
            'INFOAPP' => 'ENS-ADJE',
            'STENO' => 'ENS-F-AD',
            'DACTY' => 'ENS-F-AD',
            'ORGA' => 'ENS-F-AD',
            'MOYEXP' => 'ENS-F-AD',
            'BUREAU' => 'ENS-F-AD',
            'ETUDE' => 'ENS-F-AD',
            'GESTSTAT' => 'ENS-F-AD',
            'MARKET' => 'ENS-F-GC',
            'VENTE' => 'ENS-F-GC',
            'STRAT' => 'ENS-F-GC',
            'DISTRI' => 'ENS-F-GC',
            'COMINT' => 'ENS-F-GC',
            'STOCK' => 'ENS-F-GC',
            'MKINT' => 'ENS-F-GC',
            'FORCEV' => 'ENS-F-GC',
            'APPRO' => 'ENS-F-LT',
            'LOGPROD' => 'ENS-F-LT',
            'TRANSIT' => 'ENS-F-LT',
            'FACTU' => 'ENS-F-LT',
            'ECONTR' => 'ENS-F-LT',
            'GESTTR' => 'ENS-F-LT',
            'DECIS' => 'ENS-F-LT',
            'MARIT' => 'ENS-F-LT',
            'PORT' => 'ENS-F-LT',
            'MOYGEN' => 'ENS-F-LT',
            'ENTREP' => 'ENS-F-LT',
            'TECTOUR' => 'ENS-F-TH',
            'TECHOT' => 'ENS-F-TH',
            'FISCA' => 'ENS-F-FISCA',
        ];
    }

    private function noms(): array
    {
        return [
            ['KOUASSI', 'AYA MARIE'],
            ['YAO', 'KOUAME JEAN'],
            ['KOFFI', 'ADJOUA CHRISTELLE'],
            ['TRAORE', 'MOUSSA'],
            ['DIALLO', 'FATIM AMINATA'],
            ['KONAN', 'BRICE PACOME'],
            ['N\'GUESSAN', 'MARIE SANDRA'],
            ['OUATTARA', 'ISSA'],
            ['BAMBA', 'NADIA'],
            ['TOURE', 'SERGE'],
            ['COULIBALY', 'LARISSA'],
            ['SANOGO', 'IBRAHIM'],
            ['KONE', 'CHANTAL'],
            ['FOSSOU', 'YVES'],
            ['ASSI', 'GRACE'],
            ['GUEU', 'MICHEL'],
            ['BLE', 'ESTHER'],
            ['EBA', 'PATRICK'],
            ['AMANI', 'RUTH'],
            ['DIABATE', 'FATOU'],
            ['FOFANA', 'ABDOUL'],
            ['MENSAH', 'JOEL'],
            ['ADJOVI', 'PRISCA'],
            ['BROU', 'CEDRIC'],
        ];
    }

    private function hashClasse(string $code): int
    {
        return array_sum(array_map('ord', str_split($code)));
    }
}
