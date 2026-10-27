<template>
  <div class="saas-page saas-entrer">
    <p v-if="erreur" class="etat-erreur">{{ erreur }}</p>
    <p v-else-if="chargement" class="etat-chargement">Chargement des indicateurs…</p>
    <template v-else>
      <section class="saas-bienvenue">
        <div>
          <p class="saas-kicker">{{ annee || 'Année en cours' }} · {{ libelleRole }}</p>
          <h2 v-if="session.role === 'parent'">
            Bienvenue parent « {{ session.enfantActif?.nom_complet || session.utilisateur?.enfants?.[0]?.nom_complet || '…' }} »
          </h2>
          <h2 v-else>Bonjour, {{ session.utilisateur?.prenoms }}</h2>
          <p>{{ phrase }}</p>
        </div>
        <div class="saas-raccourcis">
          <router-link v-for="lien in raccourcis" :key="lien.to" :to="lien.to" class="btn btn-primary btn-sm">
            <i :class="lien.icone + ' me-1'"></i>{{ lien.libelle }}
          </router-link>
        </div>
      </section>

      <section class="saas-grille">
        <article v-for="carte in cartes" :key="carte.libelle" class="saas-indicateur" :class="ton(carte)">
          <i :class="'ti ' + carte.icone"></i>
          <p class="valeur">{{ afficher(carte) }}</p>
          <p class="libelle">{{ carte.libelle }}</p>
        </article>
      </section>

      <section v-if="session.role === 'administrateur'" class="saas-colonnes">
        <article class="saas-carte">
          <h3>Répartition</h3>
          <div v-for="barre in graphique" :key="barre.libelle" class="saas-barre">
            <span>{{ barre.libelle }}</span>
            <div class="saas-piste"><div class="saas-remplissage" :style="{ width: barre.pourcent + '%' }"></div></div>
            <strong>{{ barre.valeur }}</strong>
          </div>
          <p v-if="!graphique.length" class="etat-vide mb-0">Aucun indicateur chiffré.</p>
        </article>
        <article class="saas-carte">
          <h3>Situation financière</h3>
          <ul class="saas-liste">
            <li><span>Montant dû</span><strong>{{ fcfa(finances?.montant_du) }}</strong></li>
            <li><span>Encaissé après validation</span><strong>{{ fcfa(finances?.montant_encaisse) }}</strong></li>
            <li><span>Reste à recouvrer</span><strong>{{ fcfa(finances?.solde_restant) }}</strong></li>
          </ul>
          <p class="saas-meta mb-0">Aucune passerelle bancaire n’est connectée. Les montants simulés restent identifiés comme tels.</p>
        </article>
      </section>

      <section v-if="session.role === 'administrateur'" class="saas-colonnes">
        <article class="saas-carte">
          <h3>Paiements en attente de validation</h3>
          <p v-if="!detail.paiements?.length" class="etat-vide mb-0">Aucun justificatif à vérifier.</p>
          <ul v-else class="saas-liste">
            <li v-for="fiche in detail.paiements" :key="fiche.numero_fiche">
              <span>{{ fiche.etudiant || fiche.matricule }}<br><small>{{ fiche.tranche }}<span v-if="fiche.mode === 'simulation'"> · Simulation</span></small></span>
              <strong>{{ fcfa(fiche.montant) }}</strong>
            </li>
          </ul>
        </article>
        <article class="saas-carte">
          <h3>Dernières activités</h3>
          <p v-if="!detail.activites?.length" class="etat-vide mb-0">Aucune action enregistrée.</p>
          <ul v-else class="saas-liste">
            <li v-for="ligne in detail.activites" :key="ligne.id">
              <span>{{ ligne.description || ligne.action }}<br><small>{{ ligne.auteur }}</small></span>
              <small>{{ court(ligne.date) }}</small>
            </li>
          </ul>
        </article>
      </section>

      <section v-if="session.role === 'enseignant'" class="saas-colonnes">
        <article class="saas-carte">
          <h3>Classes et matières</h3>
          <p v-if="!detail.affectations?.length" class="etat-vide mb-0">Aucune affectation.</p>
          <ul v-else class="saas-liste">
            <li v-for="(ligne, index) in detail.affectations" :key="index">
              <span>{{ ligne.matiere }}</span><span class="badge-statut">{{ ligne.classe }}</span>
            </li>
          </ul>
        </article>
        <article class="saas-carte">
          <h3>Notes à compléter ou à publier</h3>
          <p v-if="!detail.a_completer?.length" class="etat-vide mb-0">Rien en attente.</p>
          <ul v-else class="saas-liste">
            <li v-for="ev in detail.a_completer" :key="ev.num_evaluation">
              <span>{{ ev.matiere }} · {{ ev.classe }}<br><small>{{ ev.type }}</small></span>
              <span class="badge-statut ambre">{{ libelleStatut(ev.statut) }}</span>
            </li>
          </ul>
        </article>
      </section>

      <section v-if="session.role === 'etudiant'" class="saas-colonnes">
        <article class="saas-carte">
          <h3>Moyenne générale</h3>
          <p class="valeur">{{ detail.moyenne_generale ? noteFr(detail.moyenne_generale) : 'Moyenne non disponible' }}</p>
          <p class="saas-meta">{{ [detail.classe, detail.filiere].filter(Boolean).join(' · ') }}</p>
          <h3 class="mt-3">Matières</h3>
          <p v-if="!detail.matieres?.length" class="etat-vide mb-0">Aucune note publiée.</p>
          <ul v-else class="saas-liste">
            <li v-for="matiere in detail.matieres" :key="matiere.libelle">
              <span>{{ matiere.libelle }}</span>
              <strong>{{ matiere.moyenne ? noteFr(matiere.moyenne) : 'Moyenne non disponible' }}</strong>
            </li>
          </ul>
        </article>
        <article class="saas-carte">
          <h3>Derniers résultats publiés</h3>
          <p v-if="!detail.resultats?.length" class="etat-vide mb-0">Aucun résultat publié.</p>
          <ul v-else class="saas-liste">
            <li v-for="(note, index) in detail.resultats" :key="index">
              <span>{{ note.matiere }}<br><small>{{ note.type }} · {{ note.date }}</small></span>
              <span class="badge-statut positif">{{ noteFr(note.note) }}</span>
            </li>
          </ul>
        </article>
        <article class="saas-carte">
          <h3>Devoirs programmés</h3>
          <p v-if="!detail.devoirs?.length" class="etat-vide mb-0">Aucun devoir programmé pour l’instant.</p>
          <ul v-else class="saas-liste">
            <li v-for="ev in detail.devoirs" :key="ev.num_evaluation">
              <span>{{ ev.matiere }}<br><small>{{ ev.type }} · {{ ev.date }}</small></span>
              <span class="badge-statut" :class="ev.publiee ? 'positif' : 'ambre'">{{ ev.publiee ? 'Notes publiées' : 'Programmé' }}</span>
            </li>
          </ul>
          <router-link to="/devoirs" class="btn btn-outline-primary btn-sm mt-2">Voir tout</router-link>
        </article>
      </section>

      <section v-if="session.role === 'parent'" class="saas-carte">
        <h3>Enfants associés</h3>
        <p v-if="!detail.enfants?.length" class="etat-vide mb-0">Aucun enfant n’est rattaché à ce compte.</p>
        <div v-for="enfant in detail.enfants" :key="enfant.matricule" class="mb-3">
          <div class="d-flex justify-content-between flex-wrap gap-2">
            <strong>{{ enfant.nom_complet }}</strong>
            <span class="badge-statut">{{ enfant.matricule }}<span v-if="enfant.lien"> · {{ enfant.lien }}</span></span>
          </div>
          <ul class="saas-liste">
            <li><span>Classe</span><span>{{ enfant.classe || 'Non inscrite pour l’année courante' }}</span></li>
            <li><span>Moyenne publiée</span><strong>{{ enfant.moyenne_generale ? noteFr(enfant.moyenne_generale) : 'Moyenne non disponible' }}</strong></li>
            <li><span>Solde des frais</span><strong>{{ fcfa(enfant.solde) }}</strong></li>
          </ul>
          <p v-if="enfant.echeances?.length" class="saas-meta">Échéances</p>
          <ul class="saas-liste">
            <li v-for="echeance in enfant.echeances" :key="echeance.code_tranche">
              <span>{{ echeance.libelle_tranche }}<br><small>Limite {{ echeance.date_limite }}</small></span>
              <span class="badge-statut" :class="echeance.statut === 'valide' ? 'positif' : 'ambre'">{{ libellePaiement(echeance.statut) }}</span>
            </li>
          </ul>
        </div>
      </section>

      <section class="saas-colonnes">
        <article v-if="session.role === 'enseignant' || session.role === 'etudiant'" class="saas-carte">
          <h3>Supports de cours</h3>
          <p v-if="!detail.supports?.length" class="etat-vide mb-0">Aucun support.</p>
          <ul v-else class="saas-liste">
            <li v-for="support in detail.supports" :key="support.titre + support.date">
              <span>{{ support.titre }}<br><small>{{ support.matiere }}</small></span>
              <small>{{ support.date }}</small>
            </li>
          </ul>
        </article>
        <article class="saas-carte">
          <h3>Notifications</h3>
          <p v-if="!detail.notifications?.length" class="etat-vide mb-0">Aucune notification.</p>
          <ul v-else class="saas-liste">
            <li v-for="note in detail.notifications" :key="note.num_notification">
              <span>{{ note.message }}<br><small>{{ note.type_notification }}</small></span>
              <span class="badge-statut" :class="note.statut_lecture === 'non_lue' ? 'ambre' : ''">{{ note.statut_lecture === 'non_lue' ? 'Non lue' : 'Lue' }}</span>
            </li>
          </ul>
        </article>
      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import http, { fcfa, messageErreur, noteFr } from '../api'
import { useSession } from '../stores/session'

const session = useSession()
const cartes = ref([])
const finances = ref(null)
const detail = ref({})
const annee = ref('')
const erreur = ref('')
const chargement = ref(true)

const libelles = { administrateur: 'Administrateur', enseignant: 'Enseignant', etudiant: 'Étudiant', parent: 'Parent' }
const libelleRole = computed(() => libelles[session.role] ?? '')
const phrases = {
  administrateur: 'Vue d’ensemble de l’établissement : effectifs, frais et opérations à traiter.',
  enseignant: 'Vos classes, vos notes en cours et vos supports.',
  etudiant: 'Vos résultats publiés, vos supports et vos frais.',
  parent: 'Le suivi des enfants qui vous sont rattachés.',
}
const phrase = computed(() => phrases[session.role] ?? '')
const raccourcis = computed(() => ({
  administrateur: [
    { to: '/personnes', libelle: 'Personnes', icone: 'ti ti-users' },
    { to: '/paiements', libelle: 'Paiements', icone: 'ti ti-checkup-list' },
    { to: '/structure', libelle: 'Scolarité', icone: 'ti ti-school' },
  ],
  enseignant: [
    { to: '/evaluations', libelle: 'Saisir les notes', icone: 'ti ti-certificate' },
    { to: '/supports', libelle: 'Déposer un support', icone: 'ti ti-books' },
  ],
  etudiant: [
    { to: '/devoirs', libelle: 'Devoirs', icone: 'ti ti-calendar-event' },
    { to: '/resultats', libelle: 'Résultats', icone: 'ti ti-report-analytics' },
    { to: '/frais', libelle: 'Frais', icone: 'ti ti-cash' },
  ],
  parent: [
    { to: '/resultats', libelle: 'Résultats', icone: 'ti ti-report-analytics' },
    { to: '/frais', libelle: 'Frais', icone: 'ti ti-cash' },
  ],
}[session.role] ?? []))

const graphique = computed(() => {
  const chiffres = cartes.value.filter((carte) => carte.unite !== 'FCFA')
  const max = Math.max(...chiffres.map((carte) => Number(carte.valeur) || 0), 1)
  return chiffres.slice(0, 6).map((carte) => ({
    libelle: carte.libelle,
    valeur: carte.valeur,
    pourcent: Math.round((Number(carte.valeur) || 0) * 100 / max),
  }))
})

function afficher(carte) {
  return carte.unite === 'FCFA' ? fcfa(carte.valeur) : carte.valeur
}
function ton(carte) {
  const texte = carte.libelle.toLowerCase()
  if (texte.includes('attente') || texte.includes('vérifier') || texte.includes('signal') || texte.includes('brouillon') || texte.includes('publier')) return 'ambre'
  if (texte.includes('publiée') || texte.includes('réglé')) return 'positif'
  return ''
}
function libelleStatut(statut) {
  return { brouillon: 'Brouillon', non_publiee: 'Non publiée', publiee: 'Publiée' }[statut] || statut
}
function libellePaiement(statut) {
  return { non_demandee: 'Pas encore demandée', en_attente_paiement: 'En attente', valide: 'Validé', rejete: 'Rejeté' }[statut] || statut
}
function court(date) {
  return date ? String(date).slice(0, 16).replace('T', ' ') : ''
}

onMounted(async () => {
  try {
    const { data } = await http.get('/api/tableau-de-bord')
    cartes.value = data.donnees.cartes
    finances.value = data.donnees.finances
    detail.value = data.donnees.detail || {}
    annee.value = data.donnees.annee_courante?.libelle_annee ?? ''
  } catch (e) {
    erreur.value = messageErreur(e)
  } finally {
    chargement.value = false
  }
})
</script>
