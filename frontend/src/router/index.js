import { createRouter, createWebHistory } from 'vue-router'
import { useSession } from '../stores/session'

const routes = [
  { path: '/', name: 'accueil', component: () => import('../views/Accueil.vue'), meta: { public: true } },
  { path: '/connexion', name: 'connexion', component: () => import('../views/Connexion.vue'), meta: { public: true } },
  { path: '/inscription-parent', name: 'inscription-parent', component: () => import('../views/InscriptionParent.vue'), meta: { public: true } },
  { path: '/formalites-inscription', name: 'formalites-inscription', component: () => import('../views/FormalitesInscription.vue'), meta: { public: true } },
  { path: '/attente', name: 'attente', component: () => import('../views/Attente.vue'), meta: { roles: ['parent'] } },
  {
    path: '/',
    component: () => import('../layouts/AppLayout.vue'),
    children: [
      { path: 'tableau', name: 'tableau', component: () => import('../views/TableauDeBord.vue'), meta: { titre: 'Tableau de bord' } },
      { path: 'structure', name: 'structure', component: () => import('../views/Structure.vue'), meta: { roles: ['administrateur'], titre: 'Données scolaires' } },
      { path: 'personnes', name: 'personnes', component: () => import('../views/Personnes.vue'), meta: { roles: ['administrateur'], titre: 'Personnes' } },
      { path: 'evaluations', name: 'evaluations', component: () => import('../views/Evaluations.vue'), meta: { roles: ['enseignant', 'administrateur'], titre: 'Évaluations' } },
      { path: 'devoirs', name: 'devoirs', component: () => import('../views/Devoirs.vue'), meta: { titre: 'Devoirs programmés' } },
      { path: 'resultats', name: 'resultats', component: () => import('../views/Resultats.vue'), meta: { roles: ['etudiant', 'parent', 'enseignant', 'administrateur'], titre: 'Résultats' } },
      { path: 'emploi-du-temps', name: 'emploi-du-temps', component: () => import('../views/EmploiDuTemps.vue'), meta: { titre: 'Emploi du temps' } },
      { path: 'liste-classe', name: 'liste-classe', component: () => import('../views/ListeClasse.vue'), meta: { titre: 'Liste de classe' } },
      { path: 'supports', name: 'supports', component: () => import('../views/Supports.vue'), meta: { titre: 'Supports de cours' } },
      { path: 'frais', name: 'frais', component: () => import('../views/Frais.vue'), meta: { roles: ['etudiant', 'parent', 'administrateur'], titre: 'Frais de scolarité' } },
      { path: 'paiements', name: 'paiements', component: () => import('../views/Paiements.vue'), meta: { roles: ['administrateur'], titre: 'Vérification des paiements' } },
      { path: 'notifications', name: 'notifications', component: () => import('../views/Notifications.vue'), meta: { titre: 'Notifications' } },
      { path: 'journal', name: 'journal', component: () => import('../views/Journal.vue'), meta: { roles: ['administrateur'], titre: 'Journal d’activité' } },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to) => {
  const session = useSession()
  if (!session.pret) await session.charger()

  if (!to.meta.public && !session.connecte) return { name: 'connexion' }
  if (to.meta.public && session.connecte && ['connexion', 'inscription-parent'].includes(to.name)) {
    return { name: 'tableau' }
  }
  if (session.enAttente && !to.meta.public && to.name !== 'attente') return { name: 'attente' }
  if (!session.enAttente && to.name === 'attente') return { name: 'tableau' }

  const roles = to.matched.map((r) => r.meta.roles).find(Boolean)
  if (roles && !roles.includes(session.role)) return { name: 'tableau' }
  return true
})

export default router
