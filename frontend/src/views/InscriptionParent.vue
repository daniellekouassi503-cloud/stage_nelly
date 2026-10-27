<template>
  <AuthLayout>
    <h4 class="mb-3">Compte parent</h4>
    <p class="text-muted">
      Indiquez le matricule de votre enfant et le numéro de téléphone que vous avez donné à l’établissement lors de son inscription.
      Si les deux correspondent au dossier de l’étudiant, votre compte est <strong>actif immédiatement</strong>. Sinon, contactez l’administration.
    </p>
    <p v-if="erreur" class="etat-erreur">{{ erreur }}</p>
    <div v-if="succes" class="text-success">
      <p class="mb-2">{{ succes }}</p>
      <router-link class="btn btn-primary w-100" to="/connexion">Se connecter maintenant</router-link>
    </div>
    <form v-else @submit.prevent="envoyer">
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Nom</label>
          <input v-model="form.nom" class="form-control" required />
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Prénoms</label>
          <input v-model="form.prenoms" class="form-control" required />
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">E-mail</label>
        <input v-model="form.email" type="email" class="form-control" required />
      </div>
      <div class="mb-3">
        <label class="form-label">Téléphone (celui donné à l’établissement)</label>
        <input v-model="form.telephone" type="tel" class="form-control" placeholder="07 00 00 00 01" required />
      </div>
      <div class="mb-3">
        <label class="form-label">Mot de passe</label>
        <input v-model="form.mot_de_passe" type="password" class="form-control" minlength="8" required />
      </div>
      <div class="mb-3">
        <label class="form-label">Confirmation</label>
        <input v-model="form.mot_de_passe_confirmation" type="password" class="form-control" required />
      </div>
      <div class="mb-3">
        <label class="form-label">Matricule de l’enfant</label>
        <input v-model="form.matricule" class="form-control" required />
      </div>
      <div class="mb-3">
        <label class="form-label">Lien de parenté</label>
        <input v-model="form.lien" class="form-control" placeholder="père, mère, tuteur" />
      </div>
      <button class="btn btn-primary w-100" :disabled="chargement">
        {{ chargement ? 'Création…' : 'Créer mon compte' }}
      </button>
    </form>
    <p class="mt-3 mb-0 text-center"><router-link to="/connexion">Retour à la connexion</router-link></p>
    <p class="mt-2 mb-0 text-center"><router-link to="/">Retour à l’accueil</router-link></p>
  </AuthLayout>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import AuthLayout from '../layouts/AuthLayout.vue'
import http, { messageErreur } from '../api'
import { useSession } from '../stores/session'

const session = useSession()
const router = useRouter()
const form = reactive({
  nom: '', prenoms: '', email: '', telephone: '', mot_de_passe: '', mot_de_passe_confirmation: '', matricule: '', lien: '',
})
const erreur = ref('')
const succes = ref('')
const chargement = ref(false)

async function envoyer() {
  erreur.value = ''
  chargement.value = true
  try {
    const { data } = await http.post('/api/auth/inscription-parent', {
      nom: form.nom,
      prenoms: form.prenoms,
      email: form.email,
      telephone: form.telephone,
      mot_de_passe: form.mot_de_passe,
      mot_de_passe_confirmation: form.mot_de_passe_confirmation,
      enfants: [{ matricule: form.matricule, lien_parente: form.lien }],
    })
    if (data.donnees?.statut_compte !== 'valide') {
      erreur.value = 'Le compte n’a pas pu être activé. Vérifiez le matricule et le téléphone.'
      return
    }
    await session.connecter(form.email, form.mot_de_passe, form.matricule.trim())
    router.push('/tableau')
  } catch (e) {
    erreur.value = messageErreur(e)
    succes.value = ''
  } finally {
    chargement.value = false
  }
}
</script>
