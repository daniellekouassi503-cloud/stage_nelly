<template>
  <div>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <p class="mb-0 text-muted">Messages reçus pour votre compte.</p>
      <button class="btn btn-outline-primary btn-sm" type="button" @click="toutLire">Tout marquer comme lu</button>
    </div>
    <p v-if="erreur" class="etat-erreur">{{ erreur }}</p>
    <div class="card"><div class="card-body">
      <p v-if="!notifications.length" class="etat-vide mb-0">Aucune notification.</p>
      <button v-for="n in notifications" :key="n.num_notification" class="list-group-item list-group-item-action w-100 text-start border-0 border-bottom" type="button" @click="lire(n)">
        <strong>{{ n.type_notification }}</strong>
        <span v-if="n.statut_lecture === 'non_lue'" class="badge-statut ambre">Non lue</span>
        <div>{{ n.message }}</div>
        <small class="text-muted">{{ n.date_envoi }}</small>
      </button>
    </div></div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import http, { messageErreur } from '../api'

const notifications = ref([])
const erreur = ref('')
async function charger() {
  try {
    const { data } = await http.get('/api/notifications')
    notifications.value = data.donnees
  } catch (e) {
    erreur.value = messageErreur(e)
  }
}
async function lire(notification) {
  await http.post(`/api/notifications/${notification.num_notification}/lire`)
  notification.statut_lecture = 'lue'
}
async function toutLire() {
  await http.post('/api/notifications/tout-lire')
  await charger()
}
onMounted(charger)
</script>
