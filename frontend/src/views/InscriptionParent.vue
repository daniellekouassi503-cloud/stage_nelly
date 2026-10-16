<template>
  <AuthLayout>
    <h4 class="mb-3">Compte parent</h4>
    <p class="text-muted">Le compte reste en attente jusqu’à la vérification par l’administration.</p>
    <p v-if="erreur" class="etat-erreur">{{ erreur }}</p>
    <p v-if="succes" class="text-success">{{ succes }}</p>
    <form v-if="!succes" @submit.prevent="envoyer">
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
        <label class="form-label">Téléphone</label>
        <input v-model="form.telephone" class="form-control" required />
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
      <button class="btn btn-primary w-100" :disabled="chargement">Envoyer la demande</button>
    </form>
    <p class="mt-3 mb-0 text-center"><router-link to="/connexion">Retour à la connexion</router-link></p>
  </AuthLayout>
</template>

<script setup>
import { reactive, ref } from 'vue'
import AuthLayout from '../layouts/AuthLayout.vue'
import http, { messageErreur } from '../api'

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
    await http.post('/api/auth/inscription-parent', {
      nom: form.nom,
      prenoms: form.prenoms,
      email: form.email,
      telephone: form.telephone,
      mot_de_passe: form.mot_de_passe,
      mot_de_passe_confirmation: form.mot_de_passe_confirmation,
      enfants: [{ matricule: form.matricule, lien_parente: form.lien }],
    })
    succes.value = 'Demande enregistrée. Vous pourrez vous connecter, mais l’accès aux résultats attend la validation.'
  } catch (e) {
    erreur.value = messageErreur(e)
  } finally {
    chargement.value = false
  }
}
</script>
