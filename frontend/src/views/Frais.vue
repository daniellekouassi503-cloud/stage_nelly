<template>
  <div>
    <h3 class="page-title mb-1">Frais de scolarité</h3>
    <p class="text-muted">Montants en FCFA. Le téléchargement d’une fiche ne signifie pas que la tranche est payée. Le paiement réel se fait à la banque. La simulation n’encaisse rien.</p>
    <div class="row g-2 mb-3" v-if="session.role === 'parent'">
      <div class="col-md-4">
        <select v-model="matricule" class="form-select" @change="charger">
          <option v-for="enfant in session.utilisateur?.enfants ?? []" :key="enfant.matricule" :value="enfant.matricule">{{ enfant.nom_complet }} · {{ enfant.matricule }}</option>
        </select>
      </div>
    </div>
    <div class="row g-2 mb-3" v-else-if="session.role !== 'etudiant'">
      <div class="col-md-4"><input v-model="matricule" class="form-control" placeholder="Matricule" /></div>
      <div class="col-md-2"><button class="btn btn-primary" type="button" @click="charger">Afficher</button></div>
    </div>
    <p v-if="erreur" class="etat-erreur">{{ erreur }}</p>
    <p v-if="message" class="text-success">{{ message }}</p>
    <template v-if="situation">
      <div class="row">
        <div class="col-md-4"><div class="card"><div class="card-body"><p class="text-muted mb-1">Montant dû</p><h4>{{ fcfa(situation.montant_du) }}</h4></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><p class="text-muted mb-1">Réglé</p><h4>{{ fcfa(situation.montant_paye) }}</h4></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><p class="text-muted mb-1">Solde</p><h4>{{ fcfa(situation.solde) }}</h4></div></div></div>
      </div>
      <div v-for="annee in situation.annees" :key="annee.code_annee" class="card">
        <div class="card-header"><h5 class="mb-0">{{ annee.libelle_annee }} · {{ annee.classe }}</h5></div>
        <div class="card-body table-responsive">
          <table class="table">
            <thead><tr><th>Tranche</th><th>Montant</th><th>Limite</th><th>Statut</th><th></th></tr></thead>
            <tbody>
              <tr v-for="t in annee.tranches" :key="t.code_tranche">
                <td>{{ t.libelle_tranche }} <span v-if="t.simulation" class="pastille-simulation">Simulation</span></td>
                <td>{{ fcfa(t.montant_tranche) }}</td>
                <td>{{ t.date_limite }}</td>
                <td>{{ libelleStatut(t.statut) }}</td>
                <td class="text-end">
                  <button v-if="t.statut === 'non_demandee'" class="btn btn-primary btn-sm" type="button" @click="generer(t)">Générer la fiche</button>
                  <button v-if="t.numero_fiche" class="btn btn-outline-primary btn-sm ms-1" type="button" @click="pdf(t)">Fiche PDF</button>
                  <button v-if="t.statut === 'en_attente_paiement'" class="btn btn-outline-secondary btn-sm ms-1" type="button" @click="simuler(t)">Simuler le dépôt</button>
                  <button v-if="t.numero_recu" class="btn btn-outline-success btn-sm ms-1" type="button" @click="recu(t)">Reçu</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import http, { fcfa, messageErreur, telecharger } from '../api'
import { useSession } from '../stores/session'

const session = useSession()
const matricule = ref(session.utilisateur?.matricule || '')
const situation = ref(null)
const erreur = ref('')
const message = ref('')

function libelleStatut(statut) {
  return {
    non_demandee: 'Pas encore demandée',
    en_attente_paiement: 'En attente de paiement',
    valide: 'Validé',
    rejete: 'Rejeté',
  }[statut] ?? statut
}
async function charger() {
  try {
    const params = matricule.value ? `?matricule=${encodeURIComponent(matricule.value)}` : ''
    const { data } = await http.get('/api/frais' + params)
    situation.value = data.donnees
  } catch (e) {
    erreur.value = messageErreur(e)
  }
}
async function generer(tranche) {
  try {
    await http.post('/api/fiches', { code_tranche: tranche.code_tranche, matricule: situation.value.matricule })
    message.value = 'Fiche générée. Elle ne constitue pas un paiement.'
    await charger()
  } catch (e) {
    erreur.value = messageErreur(e)
  }
}
async function pdf(tranche) {
  await telecharger(`/api/fiches/${tranche.numero_fiche}/pdf`, `fiche-${tranche.numero_fiche}.pdf`)
}
async function recu(tranche) {
  await telecharger(`/api/fiches/${tranche.numero_fiche}/recu`, `recu-${tranche.numero_recu}.pdf`)
}
async function simuler(tranche) {
  const confirmation = window.prompt('Cette action est une simulation. Aucun argent ne sera encaissé. Saisissez SIMULATION pour déposer un justificatif fictif.')
  if (!confirmation) return
  try {
    await http.post(`/api/fiches/${tranche.numero_fiche}/simuler`, { confirmation })
    message.value = 'Justificatif simulé enregistré. Le paiement reste en attente de validation administrative.'
    await charger()
  } catch (e) {
    erreur.value = messageErreur(e)
  }
}
onMounted(() => {
  if (session.role === 'parent' && !matricule.value) {
    matricule.value = session.utilisateur?.enfants?.[0]?.matricule || ''
  }
  if (session.role === 'etudiant' || session.role === 'parent') charger()
})
</script>
