<template>
  <div class="main-wrapper">
    <div class="header">
      <div class="header-left active">
        <router-link to="/tableau" class="logo logo-normal marque-epcci">EPCCI</router-link>
        <router-link to="/tableau" class="logo-small marque-epcci">EPCCI</router-link>
        <a id="toggle_btn" href="#" @click.prevent="replier"><i class="ti ti-menu-deep"></i></a>
      </div>
      <a id="mobile_btn" class="mobile_btn" href="#sidebar" @click.prevent="mobile">
        <span class="bar-icon"><span></span><span></span><span></span></span>
      </a>
      <div class="header-user">
        <div class="nav user-menu">
          <div class="nav-item nav-search-inputs me-auto">
            <div class="top-nav-search">
              <form class="dropdown" @submit.prevent="chercher">
                <div class="searchinputs">
                  <input v-model="recherche" type="text" placeholder="Rechercher une page…" aria-label="Rechercher une page" />
                </div>
              </form>
            </div>
          </div>
          <div class="d-flex align-items-center">
            <div v-if="annee" class="pe-1 mui-hide-sm d-none d-lg-inline-flex">
              <span class="top-year btn btn-outline-light fw-normal bg-white d-flex align-items-center p-2">
                <i class="ti ti-calendar-due me-1"></i>{{ annee }}
              </span>
            </div>
            <div class="pe-1">
              <router-link to="/notifications" class="btn btn-outline-light bg-white btn-icon position-relative me-1">
                <i class="ti ti-bell"></i>
                <span v-if="nonLues" class="mui-count">{{ nonLues }}</span>
              </router-link>
            </div>
            <div class="dropdown ms-1">
              <a href="#" class="dropdown-toggle mui-profile-toggle d-flex align-items-center" data-bs-toggle="dropdown">
                <span class="avatar avatar-md rounded">
                  <img src="/preskool/images/avatar-27.jpg" alt="" class="img-fluid" />
                </span>
              </a>
              <div class="dropdown-menu mui-profile-drop p-0">
                <div class="mui-profile-head">
                  <span class="avatar avatar-md avatar-rounded">
                    <img src="/preskool/images/avatar-27.jpg" alt="" />
                  </span>
                  <div class="mui-profile-id">
                    <h6 class="mb-0">{{ session.utilisateur?.nom_complet }}</h6>
                    <span class="mui-profile-mail">{{ session.utilisateur?.email }}</span>
                  </div>
                  <span class="mui-profile-role">{{ libelleRole }}</span>
                </div>
                <div class="mui-profile-body">
                  <router-link class="dropdown-item" to="/"><i class="ti ti-home"></i>Page d’accueil</router-link>
                  <router-link class="dropdown-item" to="/notifications"><i class="ti ti-bell"></i>Notifications</router-link>
                  <button class="dropdown-item text-danger" type="button" @click="sortir"><i class="ti ti-logout"></i>Déconnexion</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="sidebar" id="sidebar">
      <div class="sidebar-inner mui-native-scroll">
        <div id="sidebar-menu" class="sidebar-menu">
          <div class="mui-side-brand">
            <div class="mui-brand-card">
              <span class="mui-brand-logo"><img src="/preskool/images/global-img.svg" alt="" /></span>
              <span class="mui-brand-meta d-flex flex-column gap-1">
                <span class="mui-brand-name">EPCCI</span>
                <span class="mui-brand-sub">{{ libelleRole }}</span>
              </span>
            </div>
          </div>
          <nav aria-label="Navigation">
            <ul>
              <li>
                <h6 class="submenu-hdr"><span>Site</span></h6>
                <ul>
                  <li>
                    <router-link to="/" custom v-slot="{ href, navigate }">
                      <a :href="href" @click="ouvrir(navigate)">
                        <i class="ti ti-home"></i><span>Page d’accueil</span>
                      </a>
                    </router-link>
                  </li>
                </ul>
              </li>
              <li v-for="groupe in groupes" :key="groupe.titre">
                <h6 class="submenu-hdr"><span>{{ groupe.titre }}</span></h6>
                <ul>
                  <li v-for="lien in groupe.liens" :key="lien.to">
                    <router-link :to="lien.to" custom v-slot="{ href, navigate, isExactActive }">
                      <a :href="href" :class="{ active: isExactActive }" @click="ouvrir(navigate)">
                        <i :class="lien.icone"></i><span>{{ lien.libelle }}</span>
                      </a>
                    </router-link>
                  </li>
                </ul>
              </li>
            </ul>
          </nav>
        </div>
      </div>
      <div class="mui-side-footer">
        <button type="button" class="mui-side-user" @click="sortir">
          <img src="/preskool/images/avatar-27.jpg" alt="" />
          <span class="mui-su-meta">
            <span class="mui-su-name">{{ session.utilisateur?.nom_complet }}</span>
            <span class="mui-su-role">Déconnexion</span>
          </span>
          <i class="ti ti-logout mui-su-caret"></i>
        </button>
      </div>
    </div>

    <div class="page-wrapper">
      <div class="content">
        <div v-if="accueilParent" class="saas-bienvenue parent-accueil mb-3">
          <div>
            <p class="saas-kicker">Espace parent</p>
            <h2 class="parent-accueil-titre">Bienvenue parent « {{ accueilParent }} »</h2>
          </div>
        </div>
        <div class="d-md-flex d-block align-items-center justify-content-between flex-wrap gap-2 mb-3">
          <div class="my-auto mb-2">
            <h3 class="page-title mb-1">{{ titrePage }}</h3>
            <nav v-if="route.path !== '/tableau'">
              <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><router-link to="/tableau">Tableau de bord</router-link></li>
                <li class="breadcrumb-item active" aria-current="page">{{ titrePage }}</li>
              </ol>
            </nav>
          </div>
        </div>
        <div class="saas-entrer" :key="route.fullPath">
          <router-view />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import http from '../api'
import { useSession } from '../stores/session'

const session = useSession()
const route = useRoute()
const router = useRouter()
const nonLues = ref(0)
const annee = ref('')
const recherche = ref('')

const libelles = {
  administrateur: 'Administrateur',
  enseignant: 'Enseignant',
  etudiant: 'Étudiant',
  parent: 'Parent',
}
const libelleRole = computed(() => libelles[session.role] ?? '')
const titrePage = computed(() => route.meta.titre || 'Tableau de bord')
const accueilParent = computed(() => (
  session.role === 'parent' ? (session.enfantActif?.nom_complet || session.utilisateur?.enfants?.[0]?.nom_complet || '') : ''
))

const catalogue = {
  tableau: { to: '/tableau', libelle: 'Tableau de bord', icone: 'ti ti-layout-dashboard' },
  structure: { to: '/structure', libelle: 'Données scolaires', icone: 'ti ti-school-bell' },
  personnes: { to: '/personnes', libelle: 'Personnes', icone: 'ti ti-users' },
  evaluations: { to: '/evaluations', libelle: 'Évaluations', icone: 'ti ti-certificate' },
  devoirs: { to: '/devoirs', libelle: 'Devoirs programmés', icone: 'ti ti-calendar-event' },
  resultats: { to: '/resultats', libelle: 'Résultats', icone: 'ti ti-report-analytics' },
  emploi: { to: '/emploi-du-temps', libelle: 'Emploi du temps', icone: 'ti ti-calendar' },
  listeClasse: { to: '/liste-classe', libelle: 'Liste de classe', icone: 'ti ti-list' },
  supports: { to: '/supports', libelle: 'Supports de cours', icone: 'ti ti-books' },
  frais: { to: '/frais', libelle: 'Frais de scolarité', icone: 'ti ti-report-money' },
  paiements: { to: '/paiements', libelle: 'Vérification des paiements', icone: 'ti ti-checkup-list' },
  notifications: { to: '/notifications', libelle: 'Notifications', icone: 'ti ti-bell' },
  journal: { to: '/journal', libelle: 'Journal d’activité', icone: 'ti ti-activity' },
}

const menus = {
  administrateur: [
    { titre: 'Principal', liens: ['tableau'] },
    { titre: 'Personnes', liens: ['personnes'] },
    { titre: 'Scolarité', liens: ['structure', 'evaluations', 'devoirs', 'resultats', 'emploi', 'listeClasse', 'supports'] },
    { titre: 'Frais', liens: ['frais', 'paiements'] },
    { titre: 'Suivi', liens: ['notifications', 'journal'] },
  ],
  enseignant: [
    { titre: 'Principal', liens: ['tableau'] },
    { titre: 'Pédagogie', liens: ['evaluations', 'devoirs', 'resultats', 'emploi', 'listeClasse', 'supports'] },
    { titre: 'Suivi', liens: ['notifications'] },
  ],
  etudiant: [
    { titre: 'Principal', liens: ['tableau'] },
    { titre: 'Scolarité', liens: ['devoirs', 'resultats', 'emploi', 'listeClasse', 'supports'] },
    { titre: 'Frais', liens: ['frais'] },
    { titre: 'Suivi', liens: ['notifications'] },
  ],
  parent: [
    { titre: 'Principal', liens: ['tableau'] },
    { titre: 'Scolarité', liens: ['devoirs', 'resultats', 'emploi', 'listeClasse', 'supports'] },
    { titre: 'Frais', liens: ['frais'] },
    { titre: 'Suivi', liens: ['notifications'] },
  ],
}

const groupes = computed(() => (menus[session.role] ?? []).map((groupe) => ({
  titre: groupe.titre,
  liens: groupe.liens.map((cle) => catalogue[cle]),
})))

function replier() {
  document.body.classList.toggle('mini-sidebar')
}
function mobile() {
  document.body.classList.toggle('slide-nav')
}
function ouvrir(navigate) {
  navigate()
  document.body.classList.remove('slide-nav')
}
function chercher() {
  const texte = recherche.value.trim().toLowerCase()
  const trouve = groupes.value.flatMap((g) => g.liens).find((lien) => lien.libelle.toLowerCase().includes(texte))
  if (trouve) router.push(trouve.to)
}
async function sortir() {
  await session.deconnecter()
  router.push('/')
}

onMounted(async () => {
  try {
    const [notes, bord] = await Promise.all([
      http.get('/api/notifications'),
      http.get('/api/tableau-de-bord'),
    ])
    nonLues.value = notes.data.donnees.filter((n) => n.statut_lecture === 'non_lue').length
    annee.value = bord.data.donnees.annee_courante?.libelle_annee ?? ''
  } catch {
    nonLues.value = 0
  }
})
</script>
