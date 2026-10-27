<template>
  <AuthLayout>
    <h4 class="mb-3">Connexion</h4>
    <div class="nav nav-pills mb-3">
      <button class="nav-link" :class="{ active: profil === 'etudiant' }" type="button" @click="profil = 'etudiant'">Étudiant</button>
      <button class="nav-link" :class="{ active: profil === 'autre' }" type="button" @click="profil = 'autre'">Parent / personnel</button>
    </div>
    <p v-if="erreur" class="etat-erreur">{{ erreur }}</p>
    <form @submit.prevent="envoyer">
      <div class="mb-3">
        <label class="form-label" for="email">Adresse e-mail</label>
        <input id="email" v-model="email" type="email" class="form-control" required autocomplete="username" placeholder="prenom.nom@gmail.com" />
        <p v-if="profil === 'etudiant'" class="form-text mb-0">Une vraie adresse e-mail (ex. Gmail). Elle n’a pas besoin d’être déjà enregistrée.</p>
      </div>
      <div class="mb-3">
        <label class="form-label" for="mdp">Mot de passe</label>
        <input id="mdp" v-model="motDePasse" type="password" class="form-control" required autocomplete="current-password" />
      </div>
      <div v-if="profil === 'etudiant'" class="mb-3">
        <label class="form-label" for="matricule-etudiant">Matricule étudiant</label>
        <input id="matricule-etudiant" v-model="matricule" class="form-control" required autocomplete="off" />
        <p class="form-text mb-0">Le matricule doit exister dans la base de l’établissement.</p>
      </div>
      <div v-else class="mb-3">
        <label class="form-label" for="matricule">Matricule de l’étudiant (parents)</label>
        <input id="matricule" v-model="matricule" class="form-control" autocomplete="off" />
        <p class="form-text mb-0">Obligatoire pour un compte parent : le matricule doit exister dans la base.</p>
      </div>
      <button class="btn btn-primary w-100" :disabled="chargement">{{ chargement ? 'Connexion…' : 'Se connecter' }}</button>
    </form>
    <p class="mt-3 mb-0 text-center">
      <router-link to="/inscription-parent">Créer un compte parent</router-link>
    </p>
    <p class="mt-2 mb-0 text-center">
      <router-link to="/">Retour à l’accueil</router-link>
    </p>
  </AuthLayout>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import AuthLayout from '../layouts/AuthLayout.vue'
import { messageErreur } from '../api'
import { useSession } from '../stores/session'

const session = useSession()
const router = useRouter()
const profil = ref('etudiant')
const email = ref('')
const motDePasse = ref('')
const matricule = ref('')
const erreur = ref('')
const chargement = ref(false)

watch(profil, () => {
  erreur.value = ''
})

async function envoyer() {
  erreur.value = ''
  chargement.value = true
  try {
    await session.connecter(email.value.trim(), motDePasse.value, matricule.value.trim(), profil.value)
    router.push(session.enAttente ? '/attente' : '/tableau')
  } catch (e) {
    erreur.value = messageErreur(e)
  } finally {
    chargement.value = false
  }
}
</script>
