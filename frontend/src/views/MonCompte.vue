<template>
  <div class="row">
    <div class="col-lg-6">
      <div class="card"><div class="card-body">
        <h5 class="mb-3">Mes informations</h5>
        <p class="mb-1"><strong>Nom :</strong> {{ session.utilisateur?.nom_complet }}</p>
        <p class="mb-1"><strong>E-mail :</strong> {{ session.utilisateur?.email }}</p>
        <p class="mb-0"><strong>Rôle :</strong> {{ session.role }}</p>
      </div></div>
    </div>
    <div class="col-lg-6">
      <div class="card"><div class="card-body">
        <h5 class="mb-3">Changer mon mot de passe</h5>
        <p class="text-muted">Si votre compte a été créé par l’administration, remplacez le mot de passe reçu par un mot de passe personnel.</p>
        <p v-if="erreur" class="etat-erreur">{{ erreur }}</p>
        <p v-if="succes" class="text-success">{{ succes }}</p>
        <form @submit.prevent="envoyer">
          <div class="mb-3">
            <label class="form-label">Mot de passe actuel</label>
            <input v-model="form.mot_de_passe_actuel" type="password" class="form-control" autocomplete="current-password" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Nouveau mot de passe</label>
            <input v-model="form.mot_de_passe" type="password" class="form-control" minlength="8" autocomplete="new-password" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Confirmation</label>
            <input v-model="form.mot_de_passe_confirmation" type="password" class="form-control" autocomplete="new-password" required />
          </div>
          <button class="btn btn-primary w-100" :disabled="chargement">
            {{ chargement ? 'Enregistrement…' : 'Enregistrer' }}
          </button>
        </form>
      </div></div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import http, { messageErreur } from '../api'
import { useSession } from '../stores/session'

const session = useSession()
const form = reactive({ mot_de_passe_actuel: '', mot_de_passe: '', mot_de_passe_confirmation: '' })
const erreur = ref('')
const succes = ref('')
const chargement = ref(false)

async function envoyer() {
  erreur.value = ''
  succes.value = ''
  chargement.value = true
  try {
    const { data } = await http.post('/api/auth/mot-de-passe', form)
    succes.value = data.message
    Object.assign(form, { mot_de_passe_actuel: '', mot_de_passe: '', mot_de_passe_confirmation: '' })
  } catch (e) {
    erreur.value = messageErreur(e)
  } finally {
    chargement.value = false
  }
}
</script>
