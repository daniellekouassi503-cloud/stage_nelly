<?php

namespace Tests\Feature;

use App\Models\Evaluation;
use App\Models\Note;
use Illuminate\Http\UploadedFile;
use Tests\EcoleTestCase;

class ReglesMetierTest extends EcoleTestCase
{
    public function test_une_note_non_publiee_est_invisible_pour_l_etudiant(): void
    {
        $scene = $this->scene();

        $this->agir($scene['etudiant'])
            ->getJson('/api/evaluations/'.$scene['brouillon']->num_evaluation.'/notes')
            ->assertNotFound();

        $reponse = $this->agir($scene['etudiant'])
            ->getJson('/api/resultats?code_annee=2025-2026')
            ->assertOk();

        $contenu = json_encode($reponse->json());
        $this->assertStringNotContainsString('4.00', $contenu);
        $this->assertStringContainsString('12.00', $contenu);

        $this->agir($scene['etudiant'])
            ->getJson('/api/resultats?matricule=ETU002&code_annee=2025-2026')
            ->assertForbidden();
    }

    public function test_un_parent_ne_voit_que_son_enfant_valide(): void
    {
        $scene = $this->scene();

        $this->agir($scene['parent'])
            ->getJson('/api/resultats?matricule=ETU001&code_annee=2025-2026')
            ->assertOk();

        $this->agir($scene['parent'])
            ->getJson('/api/resultats?matricule=ETU002&code_annee=2025-2026')
            ->assertForbidden();

        $this->agir($scene['parentAttente'])
            ->getJson('/api/resultats?matricule=ETU002&code_annee=2025-2026')
            ->assertForbidden();
    }

    public function test_publication_et_calcul_de_moyenne(): void
    {
        $scene = $this->scene();

        $this->agir($scene['enseignant'])
            ->postJson('/api/evaluations/'.$scene['brouillon']->num_evaluation.'/publier')
            ->assertStatus(422);

        $this->agir($scene['enseignant'])
            ->postJson('/api/evaluations/'.$scene['brouillon']->num_evaluation.'/soumettre')
            ->assertOk();

        $this->agir($scene['enseignant'])
            ->postJson('/api/evaluations/'.$scene['brouillon']->num_evaluation.'/publier')
            ->assertOk();

        $reponse = $this->agir($scene['etudiant'])
            ->getJson('/api/resultats?code_annee=2025-2026&semestre=1')
            ->assertOk();

        $this->assertSame('8.00', $reponse->json('donnees.matieres.0.moyenne'));
        $this->assertSame('8.00', $reponse->json('donnees.moyenne_generale'));
    }

    public function test_paiement_simule_exige_une_validation_administrative(): void
    {
        $scene = $this->scene();

        $creation = $this->agir($scene['etudiant'])
            ->postJson('/api/fiches', ['code_tranche' => 'T1'])
            ->assertCreated();

        $numero = $creation->json('donnees.numero_fiche');

        $this->agir($scene['etudiant'])
            ->postJson('/api/fiches/'.$numero.'/simuler', ['confirmation' => 'clic'])
            ->assertStatus(422);

        $this->agir($scene['etudiant'])
            ->postJson('/api/fiches/'.$numero.'/simuler', ['confirmation' => 'SIMULATION'])
            ->assertOk()
            ->assertJsonPath('donnees.statut_paiement', 'en_attente_paiement');

        $this->agir($scene['etudiant'])
            ->postJson('/api/fiches/'.$numero.'/simuler', ['confirmation' => 'SIMULATION'])
            ->assertStatus(422);

        $this->agir($scene['etudiant'])
            ->postJson('/api/fiches/'.$numero.'/decision', [
                'decision' => 'valide',
                'date_paiement' => '2025-11-01',
            ])
            ->assertForbidden();

        $this->agir($scene['admin'])
            ->postJson('/api/fiches/'.$numero.'/decision', [
                'decision' => 'rejete',
            ])
            ->assertStatus(422);

        $this->agir($scene['admin'])
            ->postJson('/api/fiches/'.$numero.'/decision', [
                'decision' => 'valide',
                'date_paiement' => '2025-11-01',
            ])
            ->assertOk()
            ->assertJsonPath('donnees.mode_justificatif', 'simulation');

        $recu = $this->agir($scene['etudiant'])->get('/api/fiches/'.$numero.'/recu');
        $recu->assertOk();
        $this->assertStringStartsWith('%PDF', $recu->getContent());

        $this->agir($scene['autre'])
            ->get('/api/fiches/'.$numero.'/recu')
            ->assertForbidden();
    }

    public function test_le_support_n_est_pas_telechargeable_par_une_autre_filiere(): void
    {
        $scene = $this->scene();

        $depot = $this->agir($scene['enseignant'])->post('/api/supports', [
            'titre' => 'Cours d’algorithmique',
            'description' => 'Support fictif',
            'code_matiere' => 'ALGO',
            'code_filiere' => 'IDA',
            'code_niveau' => 'N1',
            'fichier' => UploadedFile::fake()->create('cours.pdf', 20, 'application/pdf'),
        ]);
        $depot->assertCreated();
        $code = $depot->json('donnees.code_support');

        $this->agir($scene['etudiant'])
            ->get('/api/supports/'.$code.'/telecharger')
            ->assertOk();

        $hors = $this->compte('fc@test.local', 'Bamba', 'Salimata', 'etudiant');
        \App\Models\Etudiant::query()->create([
            'matricule' => 'ETU003',
            'id_utilisateur' => $hors->id_utilisateur,
        ]);
        \App\Models\Inscription::query()->create([
            'matricule' => 'ETU003',
            'code_annee' => '2025-2026',
            'code_classe' => 'FC1A',
        ]);

        $this->agir($hors)
            ->get('/api/supports/'.$code.'/telecharger')
            ->assertForbidden();
    }

    public function test_le_bulletin_pdf_ne_contient_que_des_notes_publiees(): void
    {
        $scene = $this->scene();
        $this->assertSame('brouillon', Evaluation::query()->find($scene['brouillon']->num_evaluation)->statut_publication);
        $this->assertNotNull(Note::query()->where('num_evaluation', $scene['brouillon']->num_evaluation)->first());

        $pdf = $this->agir($scene['etudiant'])->get('/api/resultats/bulletin?code_annee=2025-2026');
        $pdf->assertOk();
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
    }
}
