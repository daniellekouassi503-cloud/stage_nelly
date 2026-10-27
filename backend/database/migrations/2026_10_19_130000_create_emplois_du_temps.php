<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emplois_du_temps', function (Blueprint $table) {
            $table->id('id_seance');
            $table->string('code_classe', 20);
            $table->string('code_annee', 20);
            $table->unsignedTinyInteger('jour_semaine');
            $table->time('heure_debut');
            $table->time('heure_fin');
            $table->string('code_matiere', 20)->nullable();
            $table->string('libelle_matiere', 120);
            $table->string('enseignant', 150)->nullable();
            $table->string('salle', 50)->nullable();
            $table->boolean('provisoire')->default(true);
            $table->foreign('code_classe')->references('code_classe')->on('classes')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('code_annee')->references('code_annee')->on('annees_scolaires')->cascadeOnUpdate()->restrictOnDelete();
            $table->index(['code_classe', 'code_annee', 'jour_semaine']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emplois_du_temps');
    }
};
