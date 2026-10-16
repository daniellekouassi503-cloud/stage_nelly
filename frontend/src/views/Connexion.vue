<template>
  <AuthLayout>
    <h4 class="mb-3">Connexion</h4>
    <p v-if="erreur" class="etat-erreur">{{ erreur }}</p>
    <form @submit.prevent="envoyer">
      <div class="mb-3">
        <label class="form-label" for="email">Adresse e-mail</label>
        <input id="email" v-model="email" type="email" class="form-control" required autocomplete="username" />
      </div>
      <div class="mb-3">
        <label class="form-label" for="mdp">Mot de passe</label>
        <input id="mdp" v-model="motDePasse" type="password" class="form-control" required autocomplete="current-password" />
      </div>
      <button class="btn btn-primary w-100" :disabled="chargement">{{ chargement ? 'Connexion…' : 'Se connecter' }}</button>
    </form>
    <p class="mt-3 mb-0 text-center">
      <router-link to="/inscription-parent">Créer un compte parent</router-link>
    </p>
  </AuthLayout>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import AuthLayout from '../layouts/AuthLayout.vue'
import { messageErreur } from '../api'
import { useSession } from '../stores/session'

const session = useSession()
const router = useRouter()
const email = ref('')
const motDePasse = ref('')
const erreur = ref('')
const chargement = ref(false)

async function envoyer() {
  erreur.value = ''
  chargement.value = true
  try {
    await session.connecter(email.value, motDePasse.value)
    router.push(session.enAttente ? '/attente' : '/')
  } catch (e) {
    erreur.value = messageErreur(e)
  } finally {
    chargement.value = false
  }
}
</script>
