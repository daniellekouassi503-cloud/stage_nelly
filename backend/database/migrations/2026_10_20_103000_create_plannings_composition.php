<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plannings_composition', function (Blueprint $table) {
            $table->string('code_planning', 20)->primary();
            $table->string('titre_planning', 180);
            $table->date('date_debut');
            $table->date('date_fin');
            $table->string('fichier_planning', 255);
            $table->string('nom_original', 255);
            $table->string('mime_fichier', 80)->nullable();
            $table->dateTime('date_publication')->useCurrent();
            $table->string('code_filiere', 10);
            $table->string('code_niveau', 10);
            $table->string('code_annee', 10);
            $table->unsignedInteger('id_utilisateur');
            $table->foreign('code_filiere')->references('code_filiere')->on('filieres')->restrictOnDelete();
            $table->foreign('code_niveau')->references('code_niveau')->on('niveaux')->restrictOnDelete();
            $table->foreign('code_annee')->references('code_annee')->on('annees_scolaires')->restrictOnDelete();
            $table->foreign('id_utilisateur')->references('id_utilisateur')->on('utilisateurs')->restrictOnDelete();
            $table->index(['code_filiere', 'code_niveau', 'code_annee'], 'plannings_filiere_niveau_annee');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plannings_composition');
    }
};
