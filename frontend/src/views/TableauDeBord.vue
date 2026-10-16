<template>
  <div>
    <div class="mb-3">
      <h3 class="page-title mb-1">Tableau de bord</h3>
      <p class="text-muted mb-0">{{ session.utilisateur?.nom_complet }} · {{ annee }}</p>
    </div>
    <p v-if="erreur" class="etat-erreur">{{ erreur }}</p>
    <p v-else-if="chargement" class="etat-chargement">Chargement des indicateurs…</p>
    <template v-else>
      <div class="row">
        <div v-for="carte in cartes" :key="carte.libelle" class="col-xl-3 col-md-6 d-flex">
          <div class="card flex-fill">
            <div class="card-body">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <p class="mb-1 text-muted">{{ carte.libelle }}</p>
                  <h4 class="mb-0">{{ carte.unite === 'FCFA' ? fcfa(carte.valeur) : carte.valeur }}</h4>
                </div>
                <span class="avatar avatar-md bg-light text-primary"><i :class="'ti ' + carte.icone"></i></span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div v-if="finances" class="card">
        <div class="card-header"><h5 class="mb-0">Situation financière</h5></div>
        <div class="card-body">
          <p>Montant dû : <strong>{{ fcfa(finances.montant_du) }}</strong></p>
          <p>Encaissé après validation : <strong>{{ fcfa(finances.montant_encaisse) }}</strong></p>
          <p>Reste à recouvrer : <strong>{{ fcfa(finances.solde_restant) }}</strong></p>
          <p class="mb-0 text-muted">Aucune passerelle bancaire n’est connectée. Les montants simulés restent identifiés comme tels.</p>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import http, { fcfa, messageErreur } from '../api'
import { useSession } from '../stores/session'

const session = useSession()
const cartes = ref([])
const finances = ref(null)
const annee = ref('')
const erreur = ref('')
const chargement = ref(true)

onMounted(async () => {
  try {
    const { data } = await http.get('/api/tableau-de-bord')
    cartes.value = data.donnees.cartes
    finances.value = data.donnees.finances
    annee.value = data.donnees.annee_courante?.libelle_annee ?? ''
  } catch (e) {
    erreur.value = messageErreur(e)
  } finally {
    chargement.value = false
  }
})
</script>
