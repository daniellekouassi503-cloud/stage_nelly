import { defineStore } from 'pinia'
import http from '../api'

const CLE_ENFANT = 'enfant_actif'

export const useSession = defineStore('session', {
  state: () => ({
    utilisateur: null,
    enfantActif: null,
    pret: false,
  }),
  getters: {
    connecte: (state) => Boolean(state.utilisateur),
    role: (state) => state.utilisateur?.role ?? null,
    enAttente: (state) => state.utilisateur?.statut_compte === 'en_attente',
  },
  actions: {
    memoriserEnfant(enfant) {
      this.enfantActif = enfant
      if (enfant) localStorage.setItem(CLE_ENFANT, JSON.stringify(enfant))
      else localStorage.removeItem(CLE_ENFANT)
    },
    restaurerEnfant() {
      try {
        const brut = localStorage.getItem(CLE_ENFANT)
        this.enfantActif = brut ? JSON.parse(brut) : null
      } catch {
        this.enfantActif = null
      }
    },
    async charger() {
      const jeton = localStorage.getItem('jeton')
      if (!jeton) {
        this.utilisateur = null
        this.enfantActif = null
        this.pret = true
        return
      }
      try {
        const { data } = await http.get('/api/auth/moi')
        this.utilisateur = data.donnees
        this.restaurerEnfant()
        if (this.role === 'parent' && !this.enfantActif && this.utilisateur?.enfants?.length) {
          this.memoriserEnfant(this.utilisateur.enfants[0])
        }
      } catch {
        localStorage.removeItem('jeton')
        localStorage.removeItem(CLE_ENFANT)
        this.utilisateur = null
        this.enfantActif = null
      } finally {
        this.pret = true
      }
    },
    async connecter(email, motDePasse, matricule = '', profil = '') {
      const charge = {
        email,
        mot_de_passe: motDePasse,
      }
      if (matricule) charge.matricule = matricule
      if (profil) charge.profil = profil
      const { data } = await http.post('/api/auth/connexion', charge)
      localStorage.setItem('jeton', data.donnees.jeton)
      this.utilisateur = data.donnees.utilisateur
      if (data.donnees.utilisateur?.enfant_actif) {
        this.memoriserEnfant(data.donnees.utilisateur.enfant_actif)
      } else {
        this.memoriserEnfant(null)
      }
      this.pret = true
    },
    async deconnecter() {
      try {
        await http.post('/api/auth/deconnexion')
      } catch {
        /* le jeton local est retiré dans tous les cas */
      }
      localStorage.removeItem('jeton')
      localStorage.removeItem(CLE_ENFANT)
      this.utilisateur = null
      this.enfantActif = null
    },
  },
})
