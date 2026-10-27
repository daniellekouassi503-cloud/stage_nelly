<template>
  <div class="formalites-page">
    <header class="formalites-bandeau">
      <div class="formalites-contenu formalites-entete">
        <router-link to="/" class="formalites-marque">EPCCI</router-link>
        <nav class="formalites-nav">
          <router-link to="/">Accueil</router-link>
          <a href="https://www.epcci.edu.ci/admissions/" target="_blank" rel="noopener">Site admissions</a>
          <router-link to="/connexion">Connexion</router-link>
        </nav>
      </div>
    </header>

    <main class="formalites-contenu">
      <p class="formalites-fil">Admissions · Formalités d’inscription</p>
      <h1>Formalités d’inscription</h1>
      <p class="formalites-intro">
        Informations publiées par l’EPCCI pour la constitution des dossiers.
        Source :
        <a href="https://www.epcci.edu.ci/admissions/formalites-dinscription/" target="_blank" rel="noopener">
          epcci.edu.ci/admissions/formalites-dinscription
        </a>
      </p>

      <section v-for="bloc in blocs" :key="bloc.titre" class="formalites-carte">
        <h2>{{ bloc.titre }}</h2>
        <p class="formalites-exigence">Tous ces documents seront exigés à l’inscription</p>
        <ul>
          <li v-for="doc in bloc.documents" :key="doc">{{ doc }}</li>
        </ul>
        <h3>Frais de scolarité</h3>
        <ul>
          <li v-for="frais in bloc.frais" :key="frais">{{ frais }}</li>
        </ul>
      </section>

      <div class="formalites-actions">
        <a class="btn-formalites" href="https://www.epcci.edu.ci/" target="_blank" rel="noopener">Faire ma préinscription</a>
        <router-link class="btn-formalites secondaire" to="/">Retour à l’accueil</router-link>
      </div>
    </main>
  </div>
</template>

<script setup>
const blocs = [
  {
    titre: 'Première année BTS',
    documents: [
      '3 Extraits d’acte de naissance (original)',
      'Fiche de renseignement',
      '3 Photocopies du relevé de notes du BAC (à faire légaliser)',
      '3 Photocopies de l’attestation de réussite du BT (à faire légaliser)',
      '1 Attestation du correspondant (à faire légaliser)',
      '1 Charte (la dernière page renseignée et signée)',
      '1 Fiche d’engagement des affectés (AFO – PC) renseignée et signée — FCGE, GC, AD, LOG, TH / IDA',
      '3 Photos d’identité du même tirage',
      'Photocopie des bulletins semestriels 2024/2025',
      '3 Photocopies de la CNI ou de la carte consulaire',
      '2 Fiches d’orientation (une originale et une photocopie)',
    ],
    frais: [
      'Affectés FCGE – GC – AD – LOG – TH : 265 000 FCFA · Droit d’inscription : 85 000 FCFA',
      'Affectés IDA : 115 000 FCFA · Droit d’inscription : 85 000 FCFA',
      'Payants (non affectés) : 450 000 FCFA · Droit d’inscription : 200 000 FCFA',
      'Inscription bibliothèque en ligne : 6 000 FCFA',
      'Achat de cravate : 6 000 FCFA',
      'Achat de polo : 5 000 FCFA',
    ],
  },
  {
    titre: 'Deuxième année BTS',
    documents: [
      '3 Photocopies du relevé de notes du BAC (à faire légaliser) — exigée',
      'Fiche de renseignement',
      '3 Photocopies légalisées de l’attestation de réussite du BT',
      '1 Attestation du correspondant (à faire légaliser)',
      '3 Extraits d’acte de naissance (original) — exigé',
      '1 Charte (la dernière page renseignée et signée)',
      '1 Fiche d’engagement des affectés (AFO – PC) renseignée et signée — FCGE, GC, AD, LOG, TH / IDA',
      '3 Photos d’identité du même tirage',
      'Photocopie du dernier bulletin semestriel 2024/2025',
      '2 Photocopies de la CNI ou de la carte consulaire',
      'Certificat de scolarité pour les nouveaux étudiants à l’EPCCI déjà inscrits en BTS ailleurs',
    ],
    frais: [
      'Affectés FCGE – GC – AD – LOG – TH : 265 000 FCFA · Droit d’inscription : 85 000 FCFA',
      'Affectés IDA : 115 000 FCFA · Droit d’inscription : 85 000 FCFA',
      'Payants (non affectés) : 450 000 FCFA · Droit d’inscription : 200 000 FCFA',
      'Inscription bibliothèque en ligne : 6 000 FCFA',
      'Achat de cravate : 6 000 FCFA',
      'Achat de polo : 5 000 FCFA',
    ],
  },
  {
    titre: 'Licence et master',
    documents: [
      '1 Photocopie du relevé de notes du BAC ou équivalent (au vu de l’original)',
      'Fiche de renseignement cours du jour et cours du soir',
      '1 Attestation du correspondant à télécharger et à légaliser pour les licence/master jour',
      '1 Engagement de l’auditeur à télécharger et à légaliser pour les licence/master soir',
      '1 Charte (dernière page renseignée et signée) pour licence/master jour',
      '1 Charte (dernière page renseignée et signée) pour les auditeurs licence/master soir',
      '1 Extrait d’acte de naissance (original) — exigé',
      '3 Photos d’identité du même tirage',
      'Relevés de notes 2024/2025',
      '1 CV',
      '1 Photocopie de l’attestation de réussite du BTS ou équivalent (légalisée)',
      '1 Photocopie de la CNI ou de la carte consulaire — exigée',
    ],
    frais: [
      'Licence/Master (jour) : 1 000 000 FCFA',
      'Licence (soir) : 900 000 FCFA',
      'Master 1 (soir) : 1 100 000 FCFA',
      'Master 2 (soir) : 1 250 000 FCFA',
      'Inscription bibliothèque en ligne : 6 000 FCFA',
      'Achat de cravate (cours du jour) : 6 000 FCFA',
      'Achat de polo : 5 000 FCFA',
    ],
  },
]
</script>

<style scoped>
.formalites-page { min-height: 100vh; background: #f7f1f2; color: #2b2b2b; }
.formalites-contenu { width: min(920px, calc(100% - 2rem)); margin: 0 auto; }
.formalites-bandeau { background: #5f060d; color: #fff; }
.formalites-entete { display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding: 1rem 0; }
.formalites-marque { color: #fff; font-weight: 800; letter-spacing: 0.08em; text-decoration: none; font-size: 1.25rem; }
.formalites-nav { display: flex; flex-wrap: wrap; gap: 1rem; }
.formalites-nav a { color: #fff; text-decoration: none; font-weight: 600; }
main.formalites-contenu { padding: 2rem 0 3rem; }
.formalites-fil { color: #64748b; text-transform: uppercase; letter-spacing: 0.06em; font-size: 0.78rem; }
h1 { color: #3e0409; font-size: clamp(1.8rem, 4vw, 2.5rem); margin: 0.3rem 0 0.8rem; }
.formalites-intro { max-width: 42rem; }
.formalites-intro a { color: #5f060d; }
.formalites-carte {
  background: #fff;
  border: 1px solid #e8d5d7;
  border-left: 4px solid #5f060d;
  border-radius: 12px;
  padding: 1.25rem 1.4rem;
  margin: 1.2rem 0;
}
.formalites-carte h2 { color: #5f060d; font-size: 1.25rem; margin: 0 0 0.4rem; }
.formalites-carte h3 { color: #3e0409; font-size: 1rem; margin: 1rem 0 0.4rem; }
.formalites-exigence { color: #64748b; margin: 0 0 0.6rem; }
.formalites-carte ul { margin: 0; padding-left: 1.2rem; }
.formalites-carte li { margin: 0.35rem 0; }
.formalites-actions { display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 1.5rem; }
.btn-formalites {
  display: inline-flex; align-items: center; justify-content: center;
  padding: 0.75rem 1.1rem; background: #5f060d; color: #fff; text-decoration: none; font-weight: 700;
}
.btn-formalites.secondaire { background: #fff; color: #5f060d; border: 2px solid #5f060d; }
</style>
