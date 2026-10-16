import { defineStore } from 'pinia'
import http from '../api'

export const useSession = defineStore('session', {
  state: () => ({
    utilisateur: null,
    pret: false,
  }),
  getters: {
    connecte: (state) => Boolean(state.utilisateur),
    role: (state) => state.utilisateur?.role ?? null,
    enAttente: (state) => state.utilisateur?.statut_compte === 'en_attente',
  },
  actions: {
    async charger() {
      const jeton = localStorage.getItem('jeton')
      if (!jeton) {
        this.utilisateur = null
        this.pret = true
        return
      }
      try {
        const { data } = await http.get('/api/auth/moi')
        this.utilisateur = data.donnees
      } catch {
        localStorage.removeItem('jeton')
        this.utilisateur = null
      } finally {
        this.pret = true
      }
    },
    async connecter(email, motDePasse) {
      const { data } = await http.post('/api/auth/connexion', {
        email,
        mot_de_passe: motDePasse,
      })
      localStorage.setItem('jeton', data.donnees.jeton)
      this.utilisateur = data.donnees.utilisateur
      this.pret = true
    },
    async deconnecter() {
      try {
        await http.post('/api/auth/deconnexion')
      } catch {
        /* le jeton local est retiré dans tous les cas */
      }
      localStorage.removeItem('jeton')
      this.utilisateur = null
    },
  },
})
