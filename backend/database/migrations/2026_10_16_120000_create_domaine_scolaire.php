<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utilisateurs', function (Blueprint $table) {
            $table->increments('id_utilisateur');
            $table->string('nom_utilisateur', 100);
            $table->string('prenoms_utilisateur', 150);
            $table->string('email', 150)->unique();
            $table->string('telephone_utilisateur', 20)->nullable();
            $table->string('mot_de_passe', 255);
            $table->enum('role', ['administrateur', 'enseignant', 'etudiant', 'parent']);
            $table->enum('statut_compte', ['en_attente', 'valide', 'rejete'])->default('en_attente');
            $table->dateTime('date_creation')->useCurrent();
        });

        Schema::create('etudiants', function (Blueprint $table) {
            $table->string('matricule', 20)->primary();
            $table->string('nom_parent', 150)->nullable();
            $table->string('telephone_parent', 20)->nullable();
            $table->unsignedInteger('id_utilisateur')->unique();
            $table->foreign('id_utilisateur')->references('id_utilisateur')->on('utilisateurs')->restrictOnDelete();
        });

        Schema::create('enseignants', function (Blueprint $table) {
            $table->string('code_enseignant', 20)->primary();
            $table->unsignedInteger('id_utilisateur')->unique();
            $table->foreign('id_utilisateur')->references('id_utilisateur')->on('utilisateurs')->restrictOnDelete();
        });

        Schema::create('parents', function (Blueprint $table) {
            $table->string('code_parent', 20)->primary();
            $table->unsignedInteger('id_utilisateur')->unique();
            $table->foreign('id_utilisateur')->references('id_utilisateur')->on('utilisateurs')->restrictOnDelete();
        });

        Schema::create('annees_scolaires', function (Blueprint $table) {
            $table->string('code_annee', 10)->primary();
            $table->string('libelle_annee', 50);
            $table->boolean('est_courante')->default(false);
        });

        Schema::create('filieres', function (Blueprint $table) {
            $table->string('code_filiere', 10)->primary();
            $table->string('libelle_filiere', 100);
        });

        Schema::create('niveaux', function (Blueprint $table) {
            $table->string('code_niveau', 10)->primary();
            $table->string('libelle_niveau', 50);
        });

        Schema::create('classes', function (Blueprint $table) {
            $table->string('code_classe', 20)->primary();
            $table->string('libelle_classe', 50);
            $table->string('code_filiere', 10);
            $table->string('code_niveau', 10);
            $table->foreign('code_filiere')->references('code_filiere')->on('filieres')->restrictOnDelete();
            $table->foreign('code_niveau')->references('code_niveau')->on('niveaux')->restrictOnDelete();
        });

        Schema::create('matieres', function (Blueprint $table) {
            $table->string('code_matiere', 20)->primary();
            $table->string('libelle_matiere', 100);
        });

        Schema::create('programmer', function (Blueprint $table) {
            $table->string('code_matiere', 20);
            $table->string('code_filiere', 10);
            $table->string('code_niveau', 10);
            $table->decimal('coefficient', 4, 2);
            $table->primary(['code_matiere', 'code_filiere', 'code_niveau']);
            $table->foreign('code_matiere')->references('code_matiere')->on('matieres')->restrictOnDelete();
            $table->foreign('code_filiere')->references('code_filiere')->on('filieres')->restrictOnDelete();
            $table->foreign('code_niveau')->references('code_niveau')->on('niveaux')->restrictOnDelete();
        });

        Schema::create('enseigner', function (Blueprint $table) {
            $table->string('code_enseignant', 20);
            $table->string('code_matiere', 20);
            $table->string('code_classe', 20);
            $table->primary(['code_enseignant', 'code_matiere', 'code_classe']);
            $table->foreign('code_enseignant')->references('code_enseignant')->on('enseignants')->restrictOnDelete();
            $table->foreign('code_matiere')->references('code_matiere')->on('matieres')->restrictOnDelete();
            $table->foreign('code_classe')->references('code_classe')->on('classes')->restrictOnDelete();
        });

        Schema::create('s_inscrire', function (Blueprint $table) {
            $table->string('matricule', 20);
            $table->string('code_annee', 10);
            $table->string('code_classe', 20);
            $table->primary(['matricule', 'code_annee']);
            $table->foreign('matricule')->references('matricule')->on('etudiants')->restrictOnDelete();
            $table->foreign('code_annee')->references('code_annee')->on('annees_scolaires')->restrictOnDelete();
            $table->foreign('code_classe')->references('code_classe')->on('classes')->restrictOnDelete();
        });

        Schema::create('evaluations', function (Blueprint $table) {
            $table->increments('num_evaluation');
            $table->enum('type_evaluation', ['devoir', 'interrogation']);
            $table->date('date_evaluation');
            $table->unsignedTinyInteger('semestre');
            $table->enum('statut_publication', ['brouillon', 'non_publiee', 'publiee'])->default('brouillon');
            $table->dateTime('date_publication')->nullable();
            $table->string('code_matiere', 20);
            $table->string('code_classe', 20);
            $table->string('code_enseignant', 20);
            $table->string('code_annee', 10);
            $table->foreign('code_matiere')->references('code_matiere')->on('matieres')->restrictOnDelete();
            $table->foreign('code_classe')->references('code_classe')->on('classes')->restrictOnDelete();
            $table->foreign('code_enseignant')->references('code_enseignant')->on('enseignants')->restrictOnDelete();
            $table->foreign('code_annee')->references('code_annee')->on('annees_scolaires')->restrictOnDelete();
            $table->index(['code_classe', 'code_annee', 'statut_publication'], 'evaluations_classe_annee_statut');
        });

        Schema::create('obtenir', function (Blueprint $table) {
            $table->string('matricule', 20);
            $table->unsignedInteger('num_evaluation');
            $table->decimal('note', 4, 2);
            $table->primary(['matricule', 'num_evaluation']);
            $table->foreign('matricule')->references('matricule')->on('etudiants')->restrictOnDelete();
            $table->foreign('num_evaluation')->references('num_evaluation')->on('evaluations')->restrictOnDelete();
        });

        Schema::create('signalements', function (Blueprint $table) {
            $table->increments('num_signalement');
            $table->text('motif_signalement');
            $table->dateTime('date_signalement')->useCurrent();
            $table->enum('statut_signalement', ['en_attente', 'traite'])->default('en_attente');
            $table->text('reponse_enseignant')->nullable();
            $table->string('matricule', 20);
            $table->unsignedInteger('num_evaluation');
            $table->foreign('matricule')->references('matricule')->on('etudiants')->restrictOnDelete();
            $table->foreign('num_evaluation')->references('num_evaluation')->on('evaluations')->restrictOnDelete();
            $table->foreign(['matricule', 'num_evaluation'], 'signalements_note_fk')
                ->references(['matricule', 'num_evaluation'])
                ->on('obtenir')
                ->restrictOnDelete();
        });

        Schema::create('rattacher', function (Blueprint $table) {
            $table->string('code_parent', 20);
            $table->string('matricule', 20);
            $table->string('lien_parente', 30)->nullable();
            $table->primary(['code_parent', 'matricule']);
            $table->foreign('code_parent')->references('code_parent')->on('parents')->restrictOnDelete();
            $table->foreign('matricule')->references('matricule')->on('etudiants')->restrictOnDelete();
        });

        Schema::create('demandes_rattachement', function (Blueprint $table) {
            $table->increments('id_demande');
            $table->string('code_parent', 20);
            $table->string('matricule', 20);
            $table->string('lien_parente', 30)->nullable();
            $table->enum('statut', ['en_attente', 'accepte', 'refuse'])->default('en_attente');
            $table->dateTime('date_demande')->useCurrent();
            $table->unique(['code_parent', 'matricule']);
            $table->foreign('code_parent')->references('code_parent')->on('parents')->restrictOnDelete();
            $table->foreign('matricule')->references('matricule')->on('etudiants')->restrictOnDelete();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->increments('num_notification');
            $table->enum('type_notification', ['notes', 'support', 'compte_parent', 'paiement']);
            $table->string('message', 255);
            $table->dateTime('date_envoi')->useCurrent();
            $table->enum('statut_lecture', ['non_lue', 'lue'])->default('non_lue');
            $table->unsignedInteger('id_utilisateur');
            $table->index(['id_utilisateur', 'statut_lecture']);
            $table->foreign('id_utilisateur')->references('id_utilisateur')->on('utilisateurs')->restrictOnDelete();
        });

        Schema::create('tranches', function (Blueprint $table) {
            $table->string('code_tranche', 20)->primary();
            $table->string('libelle_tranche', 50);
            $table->unsignedInteger('montant_tranche');
            $table->date('date_limite');
            $table->string('code_filiere', 10);
            $table->string('code_niveau', 10);
            $table->string('code_annee', 10);
            $table->foreign('code_filiere')->references('code_filiere')->on('filieres')->restrictOnDelete();
            $table->foreign('code_niveau')->references('code_niveau')->on('niveaux')->restrictOnDelete();
            $table->foreign('code_annee')->references('code_annee')->on('annees_scolaires')->restrictOnDelete();
        });

        Schema::create('fiches_versement', function (Blueprint $table) {
            $table->string('numero_fiche', 30)->primary();
            $table->dateTime('date_generation')->useCurrent();
            $table->enum('statut_paiement', ['en_attente_paiement', 'valide', 'rejete'])->default('en_attente_paiement');
            $table->date('date_paiement')->nullable();
            $table->dateTime('date_verification')->nullable();
            $table->string('motif_rejet', 255)->nullable();
            $table->string('numero_recu', 30)->nullable()->unique();
            $table->string('matricule', 20);
            $table->string('code_tranche', 20);
            $table->unsignedInteger('id_utilisateur')->nullable();
            $table->enum('mode_justificatif', ['aucun', 'banque', 'simulation'])->default('aucun');
            $table->string('reference_simulation', 40)->nullable()->unique();
            $table->dateTime('soumis_le')->nullable();
            $table->foreign('matricule')->references('matricule')->on('etudiants')->restrictOnDelete();
            $table->foreign('code_tranche')->references('code_tranche')->on('tranches')->restrictOnDelete();
            $table->foreign('id_utilisateur')->references('id_utilisateur')->on('utilisateurs')->restrictOnDelete();
            $table->index(['matricule', 'code_tranche', 'statut_paiement'], 'fiches_matricule_tranche_statut');
        });

        $this->contraintesMysql();

        Schema::create('supports_cours', function (Blueprint $table) {
            $table->string('code_support', 20)->primary();
            $table->string('titre_support', 150);
            $table->text('description_support')->nullable();
            $table->string('fichier_support', 255);
            $table->string('nom_original', 255);
            $table->dateTime('date_depot')->useCurrent();
            $table->string('code_enseignant', 20);
            $table->string('code_matiere', 20);
            $table->string('code_filiere', 10);
            $table->string('code_niveau', 10);
            $table->foreign('code_enseignant')->references('code_enseignant')->on('enseignants')->restrictOnDelete();
            $table->foreign('code_matiere')->references('code_matiere')->on('matieres')->restrictOnDelete();
            $table->foreign('code_filiere')->references('code_filiere')->on('filieres')->restrictOnDelete();
            $table->foreign('code_niveau')->references('code_niveau')->on('niveaux')->restrictOnDelete();
        });

        Schema::create('journaux_activite', function (Blueprint $table) {
            $table->increments('id_journal');
            $table->unsignedInteger('id_utilisateur')->nullable();
            $table->string('action', 80);
            $table->string('description', 255);
            $table->string('adresse_ip', 45)->nullable();
            $table->dateTime('date_action')->useCurrent();
            $table->foreign('id_utilisateur')->references('id_utilisateur')->on('utilisateurs')->nullOnDelete();
        });
    }

    private function contraintesMysql(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE programmer ADD CONSTRAINT programmer_coeff_pos CHECK (coefficient > 0)');
        DB::statement('ALTER TABLE evaluations ADD CONSTRAINT evaluations_semestre CHECK (semestre in (1, 2))');
        DB::statement('ALTER TABLE obtenir ADD CONSTRAINT obtenir_note CHECK (note >= 0 AND note <= 20)');
        DB::statement('ALTER TABLE tranches ADD CONSTRAINT tranches_montant CHECK (montant_tranche > 0)');
        DB::statement("ALTER TABLE fiches_versement ADD CONSTRAINT fiches_rejet CHECK ((statut_paiement <> 'rejete') OR (motif_rejet IS NOT NULL AND CHAR_LENGTH(motif_rejet) > 0))");
        DB::statement("ALTER TABLE fiches_versement ADD CONSTRAINT fiches_verification CHECK ((statut_paiement = 'en_attente_paiement') OR (id_utilisateur IS NOT NULL AND date_verification IS NOT NULL))");
    }

    public function down(): void
    {
        foreach ([
            'journaux_activite',
            'supports_cours',
            'fiches_versement',
            'tranches',
            'notifications',
            'demandes_rattachement',
            'rattacher',
            'signalements',
            'obtenir',
            'evaluations',
            's_inscrire',
            'enseigner',
            'programmer',
            'matieres',
            'classes',
            'niveaux',
            'filieres',
            'annees_scolaires',
            'parents',
            'enseignants',
            'etudiants',
            'utilisateurs',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
