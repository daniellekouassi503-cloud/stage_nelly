<?php

namespace Database\Seeders;

use App\Models\Affectation;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Evaluation;
use App\Models\Inscription;
use App\Models\Note;
use App\Models\SupportCours;
use App\Models\Tranche;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Tranches T1–T3, un devoir noté et un support par filière/niveau.
 * IDA2A (liste réelle) n’est pas enrichie de notes fictives.
 */
class VieQuotidienneSeeder extends Seeder
{
    public function run(): void
    {
        $annee = AnneeScolaire::query()->where('est_courante', true)->value('code_annee')
            ?? '2025-2026';

        $this->tranches($annee);
        $this->evaluations($annee);
        $this->supports();
    }

    private function tranches(string $annee): void
    {
        $lignes = [
            ['T1', '1re tranche', 150000, '2025-11-30'],
            ['T2', '2e tranche', 150000, '2026-02-28'],
            ['T3', '3e tranche', 100000, '2026-05-31'],
        ];

        $paires = Classe::query()->get(['code_filiere', 'code_niveau'])->unique(fn ($c) => $c->code_filiere.'-'.$c->code_niveau);
        foreach ($paires as $paire) {
            foreach ($lignes as [$prefixe, $libelle, $montant, $date]) {
                Tranche::query()->firstOrCreate(
                    ['code_tranche' => $prefixe.'-'.$paire->code_filiere.'-'.$paire->code_niveau],
                    [
                        'libelle_tranche' => $libelle,
                        'montant_tranche' => $montant,
                        'date_limite' => $date,
                        'code_filiere' => $paire->code_filiere,
                        'code_niveau' => $paire->code_niveau,
                        'code_annee' => $annee,
                    ]
                );
            }
        }
    }

    private function evaluations(string $annee): void
    {
        $classes = Classe::query()->where('code_classe', '!=', 'IDA2A')->orderBy('code_classe')->get();

        foreach ($classes as $classe) {
            $affectation = Affectation::query()
                ->where('code_classe', $classe->code_classe)
                ->orderBy('code_matiere')
                ->first();
            if (! $affectation) {
                continue;
            }

            $devoir = Evaluation::query()->firstOrCreate(
                [
                    'type_evaluation' => 'devoir',
                    'code_matiere' => $affectation->code_matiere,
                    'code_classe' => $classe->code_classe,
                    'code_enseignant' => $affectation->code_enseignant,
                    'code_annee' => $annee,
                    'semestre' => 1,
                ],
                [
                    'date_evaluation' => '2026-01-20',
                    'statut_publication' => 'publiee',
                    'date_publication' => now(),
                ]
            );

            if ($devoir->statut_publication !== 'publiee') {
                $devoir->statut_publication = 'publiee';
                $devoir->date_publication = $devoir->date_publication ?? now();
                $devoir->save();
            }

            $matricules = Inscription::query()
                ->where('code_classe', $classe->code_classe)
                ->where('code_annee', $annee)
                ->pluck('matricule');

            foreach ($matricules as $matricule) {
                $base = 8 + (crc32($matricule.$affectation->code_matiere) % 11);
                Note::query()->firstOrCreate(
                    ['matricule' => $matricule, 'num_evaluation' => $devoir->num_evaluation],
                    ['note' => $base]
                );
            }

            Evaluation::query()->firstOrCreate(
                [
                    'type_evaluation' => 'interrogation',
                    'code_matiere' => $affectation->code_matiere,
                    'code_classe' => $classe->code_classe,
                    'code_enseignant' => $affectation->code_enseignant,
                    'code_annee' => $annee,
                    'semestre' => 1,
                ],
                [
                    'date_evaluation' => '2026-03-12',
                    'statut_publication' => 'brouillon',
                ]
            );
        }
    }

    private function supports(): void
    {
        Storage::disk('supports')->makeDirectory('');
        $deja = [];

        $affectations = Affectation::query()->with('classe')->orderBy('code_classe')->get();
        foreach ($affectations as $affectation) {
            $classe = $affectation->classe;
            if (! $classe || $classe->code_classe === 'IDA2A') {
                continue;
            }
            $cle = $classe->code_filiere.'-'.$classe->code_niveau;
            if (isset($deja[$cle])) {
                continue;
            }
            $deja[$cle] = true;

            $existe = SupportCours::query()
                ->where('code_filiere', $classe->code_filiere)
                ->where('code_niveau', $classe->code_niveau)
                ->exists();
            if ($existe) {
                continue;
            }

            $nomFichier = 'cours-'.$cle.'.pdf';
            Storage::disk('supports')->put($nomFichier, $this->pdfMinimal('Support '.$cle));

            SupportCours::query()->create([
                'code_support' => $this->prochainSupport(),
                'titre_support' => 'Cours introductif '.$classe->code_filiere.' '.$classe->code_niveau,
                'description_support' => 'Document de démonstration pour la filière.',
                'fichier_support' => $nomFichier,
                'nom_original' => 'cours-'.$cle.'.pdf',
                'date_depot' => now(),
                'code_enseignant' => $affectation->code_enseignant,
                'code_matiere' => $affectation->code_matiere,
                'code_filiere' => $classe->code_filiere,
                'code_niveau' => $classe->code_niveau,
            ]);
        }
    }

    private function prochainSupport(): string
    {
        $dernier = SupportCours::query()->where('code_support', 'like', 'SUP%')->orderByDesc('code_support')->value('code_support');
        $suivant = 1;
        if ($dernier && preg_match('/(\d+)$/', $dernier, $m)) {
            $suivant = ((int) $m[1]) + 1;
        }

        return 'SUP'.str_pad((string) $suivant, 4, '0', STR_PAD_LEFT);
    }

    private function pdfMinimal(string $titre): string
    {
        $texte = substr($titre, 0, 40);

        return "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Count 1/Kids[3 0 R]>>endobj\n3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 612 792]/Contents 4 0 R>>endobj\n4 0 obj<</Length 44>>stream\nBT /F1 18 Tf 72 720 Td (".$texte.") Tj ET\nendstream\nendobj\ntrailer<</Root 1 0 R>>\n%%EOF\n";
    }
}
