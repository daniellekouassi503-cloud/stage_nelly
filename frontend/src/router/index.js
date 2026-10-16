import { createRouter, createWebHistory } from 'vue-router'
import { useSession } from '../stores/session'

const routes = [
  { path: '/connexion', name: 'connexion', component: () => import('../views/Connexion.vue'), meta: { public: true } },
  { path: '/inscription-parent', name: 'inscription-parent', component: () => import('../views/InscriptionParent.vue'), meta: { public: true } },
  { path: '/attente', name: 'attente', component: () => import('../views/Attente.vue'), meta: { roles: ['parent'] } },
  {
    path: '/',
    component: () => import('../layouts/AppLayout.vue'),
    children: [
      { path: '', name: 'tableau', component: () => import('../views/TableauDeBord.vue') },
      { path: 'structure', name: 'structure', component: () => import('../views/Structure.vue'), meta: { roles: ['administrateur'] } },
      { path: 'personnes', name: 'personnes', component: () => import('../views/Personnes.vue'), meta: { roles: ['administrateur'] } },
      { path: 'evaluations', name: 'evaluations', component: () => import('../views/Evaluations.vue'), meta: { roles: ['enseignant', 'administrateur'] } },
      { path: 'resultats', name: 'resultats', component: () => import('../views/Resultats.vue'), meta: { roles: ['etudiant', 'parent', 'enseignant', 'administrateur'] } },
      { path: 'supports', name: 'supports', component: () => import('../views/Supports.vue') },
      { path: 'frais', name: 'frais', component: () => import('../views/Frais.vue'), meta: { roles: ['etudiant', 'parent', 'administrateur'] } },
      { path: 'paiements', name: 'paiements', component: () => import('../views/Paiements.vue'), meta: { roles: ['administrateur'] } },
      { path: 'notifications', name: 'notifications', component: () => import('../views/Notifications.vue') },
      { path: 'journal', name: 'journal', component: () => import('../views/Journal.vue'), meta: { roles: ['administrateur'] } },
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
  if (to.meta.public && session.connecte && to.name === 'connexion') return { name: 'tableau' }
  if (session.enAttente && to.name !== 'attente' && to.name !== 'connexion') return { name: 'attente' }
  if (!session.enAttente && to.name === 'attente') return { name: 'tableau' }

  const roles = to.matched.map((r) => r.meta.roles).find(Boolean)
  if (roles && !roles.includes(session.role)) return { name: 'tableau' }
  return true
})

export default router
