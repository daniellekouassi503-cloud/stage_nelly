<template>
  <div class="main-wrapper">
    <div class="header">
      <div class="header-left active">
        <router-link to="/" class="logo logo-normal marque-texte">Scolarité</router-link>
        <a id="toggle_btn" href="#" @click.prevent="replier"><i class="ti ti-menu-deep"></i></a>
      </div>
      <a id="mobile_btn" class="mobile_btn" href="#sidebar" @click.prevent="mobile">
        <span class="bar-icon"><span></span><span></span><span></span></span>
      </a>
      <div class="header-user">
        <div class="nav user-menu">
          <div class="d-flex align-items-center ms-auto">
            <router-link to="/notifications" class="btn btn-light position-relative me-2">
              <i class="ti ti-bell"></i>
              <span v-if="nonLues" class="badge bg-primary position-absolute top-0 start-100 translate-middle">{{ nonLues }}</span>
            </router-link>
            <div class="dropdown">
              <a href="#" class="dropdown-toggle d-flex align-items-center" data-bs-toggle="dropdown">
                <span class="ms-2 fw-medium">{{ session.utilisateur?.nom_complet }}</span>
              </a>
              <div class="dropdown-menu dropdown-menu-end">
                <span class="dropdown-item-text text-muted">{{ libelleRole }}</span>
                <button class="dropdown-item" type="button" @click="sortir">Déconnexion</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="sidebar" id="sidebar">
      <div class="sidebar-inner">
        <div id="sidebar-menu" class="sidebar-menu">
          <ul>
            <li class="menu-title"><span>Navigation</span></li>
            <li v-for="lien in liens" :key="lien.to">
              <router-link :to="lien.to"><i :class="lien.icone"></i><span>{{ lien.libelle }}</span></router-link>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <div class="page-wrapper">
      <div class="content">
        <router-view />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import http from '../api'
import { useSession } from '../stores/session'

const session = useSession()
const router = useRouter()
const nonLues = ref(0)

const libelles = {
  administrateur: 'Administrateur',
  enseignant: 'Enseignant',
  etudiant: 'Étudiant',
  parent: 'Parent',
}
const libelleRole = computed(() => libelles[session.role] ?? '')

const catalogue = [
  { to: '/', libelle: 'Tableau de bord', icone: 'ti ti-layout-dashboard', roles: ['administrateur', 'enseignant', 'etudiant', 'parent'] },
  { to: '/structure', libelle: 'Données scolaires', icone: 'ti ti-building', roles: ['administrateur'] },
  { to: '/personnes', libelle: 'Personnes', icone: 'ti ti-users', roles: ['administrateur'] },
  { to: '/evaluations', libelle: 'Évaluations', icone: 'ti ti-clipboard-list', roles: ['enseignant', 'administrateur'] },
  { to: '/resultats', libelle: 'Résultats', icone: 'ti ti-report-analytics', roles: ['etudiant', 'parent', 'enseignant', 'administrateur'] },
  { to: '/supports', libelle: 'Supports de cours', icone: 'ti ti-books', roles: ['administrateur', 'enseignant', 'etudiant', 'parent'] },
  { to: '/frais', libelle: 'Frais de scolarité', icone: 'ti ti-cash', roles: ['etudiant', 'parent', 'administrateur'] },
  { to: '/paiements', libelle: 'Vérification des paiements', icone: 'ti ti-checkup-list', roles: ['administrateur'] },
  { to: '/notifications', libelle: 'Notifications', icone: 'ti ti-bell', roles: ['administrateur', 'enseignant', 'etudiant', 'parent'] },
  { to: '/journal', libelle: 'Journal d’activité', icone: 'ti ti-history', roles: ['administrateur'] },
]
const liens = computed(() => catalogue.filter((lien) => lien.roles.includes(session.role)))

function replier() {
  document.body.classList.toggle('mini-sidebar')
}
function mobile() {
  document.body.classList.toggle('slide-nav')
}
async function sortir() {
  await session.deconnecter()
  router.push('/connexion')
}

onMounted(async () => {
  try {
    const { data } = await http.get('/api/notifications')
    nonLues.value = data.donnees.filter((n) => n.statut_lecture === 'non_lue').length
  } catch {
    nonLues.value = 0
  }
})
</script>
