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

    public function test_un_etudiant_doit_fournir_un_email_valide_et_un_matricule_existant(): void
    {
        $this->scene();

        $this->postJson('/api/auth/connexion', [
            'email' => 'pas-un-email',
            'mot_de_passe' => 'Demo-2026!',
            'matricule' => 'ETU001',
            'profil' => 'etudiant',
        ])->assertStatus(422);

        $this->postJson('/api/auth/connexion', [
            'email' => 'nelly.kouassi@gmail.com',
            'mot_de_passe' => 'Demo-2026!',
            'profil' => 'etudiant',
        ])->assertStatus(422);

        $this->postJson('/api/auth/connexion', [
            'email' => 'nelly.kouassi@gmail.com',
            'mot_de_passe' => 'Demo-2026!',
            'matricule' => 'INCONNU',
            'profil' => 'etudiant',
        ])->assertStatus(422);

        $this->postJson('/api/auth/connexion', [
            'email' => 'etudiant@test.local',
            'mot_de_passe' => 'Demo-2026!',
            'matricule' => 'ETU002',
        ])->assertStatus(422);

        $this->postJson('/api/auth/connexion', [
            'email' => 'nelly.kouassi@gmail.com',
            'mot_de_passe' => 'Demo-2026!',
            'matricule' => 'ETU001',
            'profil' => 'etudiant',
        ])->assertOk()
            ->assertJsonPath('donnees.utilisateur.role', 'etudiant')
            ->assertJsonPath('donnees.utilisateur.matricule', 'ETU001');

        $this->postJson('/api/auth/connexion', [
            'email' => 'etudiant@test.local',
            'mot_de_passe' => 'Demo-2026!',
            'matricule' => 'ETU001',
        ])->assertOk()
            ->assertJsonPath('donnees.utilisateur.role', 'etudiant')
            ->assertJsonPath('donnees.utilisateur.matricule', 'ETU001');
    }

    public function test_un_parent_doit_fournir_un_matricule_etudiant_existant(): void
    {
        $this->scene();

        $this->postJson('/api/auth/connexion', [
            'email' => 'parent@test.local',
            'mot_de_passe' => 'Demo-2026!',
        ])->assertStatus(422);

        $this->postJson('/api/auth/connexion', [
            'email' => 'parent@test.local',
            'mot_de_passe' => 'Demo-2026!',
            'matricule' => 'INCONNU',
        ])->assertStatus(422);

        $this->postJson('/api/auth/connexion', [
            'email' => 'parent@test.local',
            'mot_de_passe' => 'Demo-2026!',
            'matricule' => 'ETU002',
        ])->assertStatus(422);

        $ok = $this->postJson('/api/auth/connexion', [
            'email' => 'parent@test.local',
            'mot_de_passe' => 'Demo-2026!',
            'matricule' => 'ETU001',
        ])->assertOk();

        $this->assertSame('ETU001', $ok->json('donnees.utilisateur.enfant_actif.matricule'));
        $this->assertSame('Aya Konan', $ok->json('donnees.utilisateur.enfant_actif.nom_complet'));
    }

    public function test_inscription_parent_active_sans_validation_admin_si_matricule_connu(): void
    {
        $this->scene();

        $this->postJson('/api/auth/inscription-parent', [
            'nom' => 'Traore',
            'prenoms' => 'Aminata',
            'email' => 'aminata.parent@test.local',
            'telephone' => '0700111222',
            'mot_de_passe' => 'Demo-2026!',
            'mot_de_passe_confirmation' => 'Demo-2026!',
            'enfants' => [['matricule' => 'INCONNU', 'lien_parente' => 'mère']],
        ])->assertStatus(422);

        $creation = $this->postJson('/api/auth/inscription-parent', [
            'nom' => 'Traore',
            'prenoms' => 'Aminata',
            'email' => 'aminata.parent@test.local',
            'telephone' => '+225 07 00 00 00 99',
            'mot_de_passe' => 'Demo-2026!',
            'mot_de_passe_confirmation' => 'Demo-2026!',
            'enfants' => [['matricule' => 'ETU002', 'lien_parente' => 'mère']],
        ])->assertCreated();

        $this->assertSame('valide', $creation->json('donnees.statut_compte'));
        $this->assertSame('parent', $creation->json('donnees.role'));

        $this->postJson('/api/auth/connexion', [
            'email' => 'aminata.parent@test.local',
            'mot_de_passe' => 'Demo-2026!',
            'matricule' => 'ETU002',
        ])->assertOk()
            ->assertJsonPath('donnees.utilisateur.enfant_actif.matricule', 'ETU002');
    }

    public function test_inscription_parent_refusee_si_le_telephone_ne_correspond_pas_au_dossier(): void
    {
        $this->scene();

        $formulaire = fn (string $matricule, string $telephone) => [
            'nom' => 'Intrus',
            'prenoms' => 'Faux',
            'email' => 'intrus@test.local',
            'telephone' => $telephone,
            'mot_de_passe' => 'Demo-2026!',
            'mot_de_passe_confirmation' => 'Demo-2026!',
            'enfants' => [['matricule' => $matricule, 'lien_parente' => 'tuteur']],
        ];

        // Matricule connu mais mauvais téléphone : refusé, aucun compte créé.
        $refus = $this->postJson('/api/auth/inscription-parent', $formulaire('ETU001', '0102030405'))
            ->assertStatus(422);

        // Matricule inconnu : même message, pour ne pas révéler quels matricules existent.
        $inconnu = $this->postJson('/api/auth/inscription-parent', $formulaire('ETU999', '0700000001'))
            ->assertStatus(422);

        $this->assertSame($refus->json('errors.enfants'), $inconnu->json('errors.enfants'));
        $this->assertDatabaseMissing('utilisateurs', ['email' => 'intrus@test.local']);
    }

    public function test_un_utilisateur_change_son_propre_mot_de_passe(): void
    {
        $this->scene();
        $identifiants = ['email' => 'parent@test.local', 'matricule' => 'ETU001'];

        $jeton = $this->postJson('/api/auth/connexion', [...$identifiants, 'mot_de_passe' => 'Demo-2026!'])
            ->assertOk()
            ->json('donnees.jeton');

        // Mauvais mot de passe actuel : refusé.
        $this->withToken($jeton)->postJson('/api/auth/mot-de-passe', [
            'mot_de_passe_actuel' => 'faux-mot-de-passe',
            'mot_de_passe' => 'Nouveau-2026!',
            'mot_de_passe_confirmation' => 'Nouveau-2026!',
        ])->assertStatus(422)->assertJsonValidationErrors('mot_de_passe_actuel');

        $this->withToken($jeton)->postJson('/api/auth/mot-de-passe', [
            'mot_de_passe_actuel' => 'Demo-2026!',
            'mot_de_passe' => 'Nouveau-2026!',
            'mot_de_passe_confirmation' => 'Nouveau-2026!',
        ])->assertOk();

        // Simule une nouvelle requête : le middleware auth:sanctum a changé le garde par défaut.
        $this->app['auth']->forgetGuards();
        $this->app['auth']->shouldUse('web');
        $this->postJson('/api/auth/connexion', [...$identifiants, 'mot_de_passe' => 'Demo-2026!'])->assertStatus(422);
        $this->postJson('/api/auth/connexion', [...$identifiants, 'mot_de_passe' => 'Nouveau-2026!'])->assertOk();
        $this->assertDatabaseHas('journaux_activite', ['action' => 'compte.mot_de_passe']);
    }

    public function test_l_administrateur_publie_un_planning_de_composition_par_filiere_et_niveau(): void
    {
        $scene = $this->scene();

        $creation = $this->agir($scene['admin'])->post('/api/plannings', [
            'titre' => 'BTS IDA2 devoir commun n°4',
            'date_debut' => '2026-04-20',
            'date_fin' => '2026-04-25',
            'code_filiere' => 'IDA',
            'code_niveau' => 'N1',
            'code_annee' => '2025-2026',
            'fichier' => UploadedFile::fake()->image('planning-ida2.jpg', 800, 600),
        ])->assertCreated();

        $code = $creation->json('donnees.code_planning');

        $this->agir($scene['etudiant'])
            ->getJson('/api/plannings')
            ->assertOk()
            ->assertJsonPath('donnees.0.code_planning', $code);

        $this->agir($scene['etudiant'])
            ->get('/api/plannings/'.$code.'/fichier')
            ->assertOk();

        $hors = $this->compte('fc.planning@test.local', 'Bamba', 'Salimata', 'etudiant');
        \App\Models\Etudiant::query()->create([
            'matricule' => 'ETU004',
            'id_utilisateur' => $hors->id_utilisateur,
        ]);
        \App\Models\Inscription::query()->create([
            'matricule' => 'ETU004',
            'code_annee' => '2025-2026',
            'code_classe' => 'FC1A',
        ]);

        $this->agir($hors)
            ->getJson('/api/plannings')
            ->assertOk()
            ->assertJsonCount(0, 'donnees');

        $this->agir($hors)
            ->get('/api/plannings/'.$code.'/fichier')
            ->assertForbidden();
    }

    public function test_un_etudiant_voit_les_devoirs_programmes_sans_les_notes_non_publiees(): void
    {
        $scene = $this->scene();

        $liste = $this->agir($scene['etudiant'])->getJson('/api/evaluations')->assertOk();
        $parNumero = collect($liste->json('donnees'))->keyBy('num_evaluation');

        $this->assertTrue($parNumero->has($scene['brouillon']->num_evaluation));
        $this->assertTrue($parNumero->has($scene['publiee']->num_evaluation));
        $this->assertSame('programme', $parNumero[$scene['brouillon']->num_evaluation]['statut_affichage']);
        $this->assertSame('notes_publiees', $parNumero[$scene['publiee']->num_evaluation]['statut_affichage']);
        $this->assertArrayNotHasKey('notes', $parNumero[$scene['brouillon']->num_evaluation]);

        $this->agir($scene['etudiant'])
            ->getJson('/api/evaluations/'.$scene['brouillon']->num_evaluation.'/notes')
            ->assertNotFound();
    }

    public function test_un_administrateur_peut_supprimer_un_compte_etudiant_ou_parent(): void
    {
        $scene = $this->scene();

        $this->agir($scene['etudiant'])
            ->deleteJson('/api/etudiants/ETU002')
            ->assertForbidden();

        $this->agir($scene['admin'])
            ->deleteJson('/api/etudiants/ETU002')
            ->assertOk();

        $this->assertDatabaseMissing('etudiants', ['matricule' => 'ETU002']);
        $this->assertDatabaseMissing('utilisateurs', ['email' => 'autre@test.local']);
        $this->assertDatabaseHas('etudiants', ['matricule' => 'ETU001']);

        $this->agir($scene['admin'])
            ->deleteJson('/api/parents/PAR0001')
            ->assertOk();

        $this->assertDatabaseMissing('parents', ['code_parent' => 'PAR0001']);
        $this->assertDatabaseMissing('utilisateurs', ['email' => 'parent@test.local']);
        $this->assertDatabaseHas('etudiants', ['matricule' => 'ETU001']);
    }

    public function test_emploi_et_liste_classe_ida2a_viennent_du_backend(): void
    {
        $scene = $this->scene();
        $this->seed(\Database\Seeders\ClasseIda2aSeeder::class);

        $liste = $this->agir($scene['admin'])
            ->getJson('/api/liste-classe?code_classe=IDA2A')
            ->assertOk();

        $this->assertSame(38, $liste->json('donnees.effectif'));
        $this->assertSame('33351', $liste->json('donnees.etudiants.0.matricule'));

        $emploi = $this->agir($scene['admin'])
            ->getJson('/api/emploi-du-temps?code_classe=IDA2A')
            ->assertOk();

        $this->assertTrue($emploi->json('donnees.provisoire'));
        $this->assertGreaterThanOrEqual(15, count($emploi->json('donnees.seances')));
        $this->assertSame('Analyse', $emploi->json('donnees.seances.0.libelle_matiere'));

        $etudiantIda = \App\Models\Utilisateur::query()->where('email', 'etudiant.33175@plateforme.test')->firstOrFail();
        $this->agir($etudiantIda)
            ->getJson('/api/liste-classe')
            ->assertOk()
            ->assertJsonPath('donnees.classe.code_classe', 'IDA2A')
            ->assertJsonPath('donnees.effectif', 38);
    }

    public function test_listes_fictives_et_emplois_par_filiere(): void
    {
        $scene = $this->scene();
        $this->seed(\Database\Seeders\ClassesFictivesSeeder::class);

        $liste = $this->agir($scene['admin'])
            ->getJson('/api/liste-classe?code_classe=AD1A')
            ->assertOk();

        $this->assertSame(10, $liste->json('donnees.effectif'));
        $matricules = collect($liste->json('donnees.etudiants'))->pluck('matricule');
        $this->assertTrue($matricules->contains('FAD1A01'));

        $emploi = $this->agir($scene['admin'])
            ->getJson('/api/emploi-du-temps?code_classe=AD1A')
            ->assertOk();

        $this->assertGreaterThanOrEqual(15, count($emploi->json('donnees.seances')));
        $this->assertSame('Technique d’expression', $emploi->json('donnees.seances.0.libelle_matiere'));

        $this->agir($scene['admin'])
            ->getJson('/api/liste-classe?code_classe=IDA1A')
            ->assertOk()
            ->assertJsonPath('donnees.effectif', 10);
    }

    public function test_admin_gere_emploi_et_comptes_au_quotidien(): void
    {
        $scene = $this->scene();

        $this->agir($scene['etudiant'])
            ->postJson('/api/emploi-du-temps', [
                'code_classe' => 'IDA1A',
                'jour_semaine' => 1,
                'heure_debut' => '07:15',
                'heure_fin' => '10:00',
                'libelle_matiere' => 'Algorithmique',
            ])
            ->assertForbidden();

        $creation = $this->agir($scene['admin'])
            ->postJson('/api/emploi-du-temps', [
                'code_classe' => 'IDA1A',
                'code_annee' => '2025-2026',
                'jour_semaine' => 1,
                'heure_debut' => '07:15',
                'heure_fin' => '10:00',
                'code_matiere' => 'ALGO',
                'libelle_matiere' => 'Algorithmique',
                'enseignant' => 'Jean Koffi',
                'salle' => 'S1',
                'provisoire' => true,
            ])
            ->assertCreated();

        $id = $creation->json('donnees.id_seance');
        $this->assertNotEmpty($id);

        $this->agir($scene['admin'])
            ->putJson('/api/emploi-du-temps/'.$id, [
                'jour_semaine' => 2,
                'heure_debut' => '10:15',
                'heure_fin' => '13:00',
                'code_matiere' => 'ALGO',
                'libelle_matiere' => 'Algorithmique',
                'enseignant' => 'Jean Koffi',
                'salle' => 'S2',
                'provisoire' => false,
            ])
            ->assertOk()
            ->assertJsonPath('donnees.salle', 'S2')
            ->assertJsonPath('donnees.provisoire', false);

        $this->agir($scene['admin'])
            ->getJson('/api/classes-disponibles')
            ->assertOk()
            ->assertJsonFragment(['code_classe' => 'IDA1A']);

        $this->agir($scene['admin'])
            ->putJson('/api/etudiants/ETU001', [
                'nom' => 'Konan',
                'prenoms' => 'Aya Marie',
                'email' => 'etudiant@test.local',
                'nom_parent' => 'Konan Adjoua',
            ])
            ->assertOk()
            ->assertJsonPath('donnees.utilisateur.prenoms', 'Aya Marie');

        $this->agir($scene['admin'])
            ->postJson('/api/etudiants/ETU001/mot-de-passe', ['mot_de_passe' => 'Nouveau-2026!'])
            ->assertOk();

        $etudiant = \App\Models\Utilisateur::query()->find($scene['etudiant']->id_utilisateur);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('Nouveau-2026!', $etudiant->mot_de_passe));

        $this->agir($scene['admin'])
            ->postJson('/api/enseignants', [
                'code_enseignant' => 'ENS9999',
                'nom' => 'Test',
                'prenoms' => 'Prof',
                'email' => 'prof.temp@test.local',
                'mot_de_passe' => 'Demo-2026!',
            ])
            ->assertCreated();

        $this->agir($scene['admin'])
            ->deleteJson('/api/enseignants/ENS9999')
            ->assertOk();

        $this->assertDatabaseMissing('enseignants', ['code_enseignant' => 'ENS9999']);

        $this->agir($scene['admin'])
            ->deleteJson('/api/emploi-du-temps/'.$id)
            ->assertOk();
    }
}
