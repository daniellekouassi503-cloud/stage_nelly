<?php

namespace Tests;

use App\Models\Affectation;
use App\Models\AnneeScolaire;
use App\Models\Classe;
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
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class EcoleTestCase extends TestCase
{
    use RefreshDatabase;

    protected function scene(): array
    {
        $annee = AnneeScolaire::query()->create([
            'code_annee' => '2025-2026',
            'libelle_annee' => '2025-2026',
            'est_courante' => true,
        ]);
        Filiere::query()->create(['code_filiere' => 'IDA', 'libelle_filiere' => 'Informatique Développeur d\'Application']);
        Filiere::query()->create(['code_filiere' => 'FC', 'libelle_filiere' => 'Finance Comptabilité']);
        Niveau::query()->create(['code_niveau' => 'N1', 'libelle_niveau' => 'Première année']);
        Classe::query()->create(['code_classe' => 'IDA1A', 'libelle_classe' => 'IDA 1 A', 'code_filiere' => 'IDA', 'code_niveau' => 'N1']);
        Classe::query()->create(['code_classe' => 'FC1A', 'libelle_classe' => 'FC 1 A', 'code_filiere' => 'FC', 'code_niveau' => 'N1']);
        Matiere::query()->create(['code_matiere' => 'ALGO', 'libelle_matiere' => 'Algorithmique']);
        Matiere::query()->create(['code_matiere' => 'ANG', 'libelle_matiere' => 'Anglais']);
        Programme::query()->create(['code_matiere' => 'ALGO', 'code_filiere' => 'IDA', 'code_niveau' => 'N1', 'coefficient' => 3]);
        Programme::query()->create(['code_matiere' => 'ANG', 'code_filiere' => 'IDA', 'code_niveau' => 'N1', 'coefficient' => 1]);

        $admin = $this->compte('admin@test.local', 'Admin', 'Awa', 'administrateur');
        $enseignant = $this->compte('enseignant@test.local', 'Koffi', 'Jean', 'enseignant');
        $etudiant = $this->compte('etudiant@test.local', 'Konan', 'Aya', 'etudiant');
        $autre = $this->compte('autre@test.local', 'Diallo', 'Moussa', 'etudiant');
        $parent = $this->compte('parent@test.local', 'Konan', 'Adjoua', 'parent');
        $parentAttente = $this->compte('attente@test.local', 'Nguessan', 'Serge', 'parent', 'en_attente');

        Enseignant::query()->create(['code_enseignant' => 'ENS0001', 'id_utilisateur' => $enseignant->id_utilisateur]);
        Etudiant::query()->create([
            'matricule' => 'ETU001',
            'nom_parent' => 'Konan Adjoua',
            'telephone_parent' => '0700000001',
            'id_utilisateur' => $etudiant->id_utilisateur,
        ]);
        Etudiant::query()->create([
            'matricule' => 'ETU002',
            'nom_parent' => 'Diallo Fatou',
            'telephone_parent' => '0700000099',
            'id_utilisateur' => $autre->id_utilisateur,
        ]);
        ParentTuteur::query()->create(['code_parent' => 'PAR0001', 'id_utilisateur' => $parent->id_utilisateur]);
        ParentTuteur::query()->create(['code_parent' => 'PAR0002', 'id_utilisateur' => $parentAttente->id_utilisateur]);
        \Illuminate\Support\Facades\DB::table('rattacher')->insert([
            'code_parent' => 'PAR0001',
            'matricule' => 'ETU001',
            'lien_parente' => 'mère',
        ]);

        Inscription::query()->create(['matricule' => 'ETU001', 'code_annee' => $annee->code_annee, 'code_classe' => 'IDA1A']);
        Inscription::query()->create(['matricule' => 'ETU002', 'code_annee' => $annee->code_annee, 'code_classe' => 'IDA1A']);
        Affectation::query()->create(['code_enseignant' => 'ENS0001', 'code_matiere' => 'ALGO', 'code_classe' => 'IDA1A']);

        Tranche::query()->create([
            'code_tranche' => 'T1',
            'libelle_tranche' => '1re tranche',
            'montant_tranche' => 150000,
            'date_limite' => '2025-11-30',
            'code_filiere' => 'IDA',
            'code_niveau' => 'N1',
            'code_annee' => $annee->code_annee,
        ]);

        $publiee = Evaluation::query()->create([
            'type_evaluation' => 'devoir',
            'date_evaluation' => '2025-11-12',
            'semestre' => 1,
            'statut_publication' => 'publiee',
            'date_publication' => now(),
            'code_matiere' => 'ALGO',
            'code_classe' => 'IDA1A',
            'code_enseignant' => 'ENS0001',
            'code_annee' => $annee->code_annee,
        ]);
        Note::query()->create(['matricule' => 'ETU001', 'num_evaluation' => $publiee->num_evaluation, 'note' => 12]);
        Note::query()->create(['matricule' => 'ETU002', 'num_evaluation' => $publiee->num_evaluation, 'note' => 18]);

        $brouillon = Evaluation::query()->create([
            'type_evaluation' => 'interrogation',
            'date_evaluation' => '2025-12-01',
            'semestre' => 1,
            'statut_publication' => 'brouillon',
            'code_matiere' => 'ALGO',
            'code_classe' => 'IDA1A',
            'code_enseignant' => 'ENS0001',
            'code_annee' => $annee->code_annee,
        ]);
        Note::query()->create(['matricule' => 'ETU001', 'num_evaluation' => $brouillon->num_evaluation, 'note' => 4]);

        return compact('admin', 'enseignant', 'etudiant', 'autre', 'parent', 'parentAttente', 'publiee', 'brouillon', 'annee');
    }

    protected function compte(string $email, string $nom, string $prenoms, string $role, string $statut = 'valide'): Utilisateur
    {
        return Utilisateur::query()->create([
            'nom_utilisateur' => $nom,
            'prenoms_utilisateur' => $prenoms,
            'email' => $email,
            'telephone_utilisateur' => '0102030405',
            'mot_de_passe' => 'Demo-2026!',
            'role' => $role,
            'statut_compte' => $statut,
            'date_creation' => now(),
        ]);
    }

    protected function agir(Utilisateur $utilisateur)
    {
        $this->app['auth']->forgetGuards();
        $jeton = $utilisateur->createToken('test')->plainTextToken;

        return $this->withHeader('Authorization', 'Bearer '.$jeton)
            ->withHeader('Accept', 'application/json');
    }
}
