<template>
  <div>
    <p class="text-muted">Un paiement n’est validé qu’après votre décision. La simulation ne remplace pas la banque.</p>
    <p v-if="erreur" class="etat-erreur">{{ erreur }}</p>
    <p v-if="message" class="text-success">{{ message }}</p>
    <div v-if="finances" class="row mb-3">
      <div class="col-md-3"><div class="card"><div class="card-body">Dû<br><strong>{{ fcfa(finances.montant_du) }}</strong></div></div></div>
      <div class="col-md-3"><div class="card"><div class="card-body">Encaissé<br><strong>{{ fcfa(finances.montant_encaisse) }}</strong></div></div></div>
      <div class="col-md-3"><div class="card"><div class="card-body">Reste<br><strong>{{ fcfa(finances.solde_restant) }}</strong></div></div></div>
      <div class="col-md-3"><div class="card"><div class="card-body">Simulations en attente<br><strong>{{ finances.dont_simulations_en_attente }}</strong></div></div></div>
    </div>
    <div class="card"><div class="card-body table-responsive">
      <p v-if="!fiches.length" class="etat-vide">Aucune fiche.</p>
      <table v-else class="table">
        <thead><tr><th>Fiche</th><th>Étudiant</th><th>Tranche</th><th>Statut</th><th>Mode</th><th></th></tr></thead>
        <tbody>
          <tr v-for="f in fiches" :key="f.numero_fiche">
            <td>{{ f.numero_fiche }}</td>
            <td>{{ f.etudiant?.utilisateur?.nom_complet }}<br><small>{{ f.matricule }}</small></td>
            <td>{{ f.tranche?.libelle_tranche }} · {{ fcfa(f.tranche?.montant_tranche) }}</td>
            <td><span class="badge-statut" :class="f.statut_paiement === 'valide' ? 'positif' : (f.statut_paiement === 'rejete' ? 'erreur' : 'ambre')">{{ libelleStatut(f.statut_paiement) }}</span></td>
            <td><span v-if="f.mode_justificatif === 'simulation'" class="pastille-simulation">Simulation</span><span v-else>{{ f.mode_justificatif }}</span></td>
            <td v-if="f.statut_paiement === 'en_attente_paiement'">
              <label class="form-label">Date de paiement</label>
              <input v-model="f.date_paiement" type="date" class="form-control form-control-sm mb-1" />
              <label class="form-label">Motif si rejet</label>
              <input v-model="f.motif_rejet" class="form-control form-control-sm mb-1" />
              <button class="btn btn-primary btn-sm" type="button" :disabled="envoi" @click="decider(f, 'valide')">Valider</button>
              <button class="btn btn-outline-danger btn-sm ms-1" type="button" :disabled="envoi" @click="decider(f, 'rejete')">Rejeter</button>
            </td>
            <td v-else>{{ f.numero_recu || f.motif_rejet }}</td>
          </tr>
        </tbody>
      </table>
    </div></div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import http, { fcfa, messageErreur } from '../api'

const fiches = ref([])
const finances = ref(null)
const erreur = ref('')
const message = ref('')

const envoi = ref(false)

function libelleStatut(statut) {
  return { en_attente_paiement: 'En attente', valide: 'Validé', rejete: 'Rejeté' }[statut] || statut
}

async function charger() {
  const [f, fin] = await Promise.all([http.get('/api/fiches'), http.get('/api/finances')])
  fiches.value = f.data.donnees
  finances.value = fin.data.donnees
}

async function decider(fiche, decision) {
  if (envoi.value) return
  envoi.value = true
  erreur.value = ''
  try {
    await http.post(`/api/fiches/${fiche.numero_fiche}/decision`, {
      decision,
      date_paiement: fiche.date_paiement,
      motif_rejet: fiche.motif_rejet,
    })
    message.value = decision === 'valide' ? 'Paiement validé. Le reçu est disponible.' : 'Paiement rejeté.'
    await charger()
  } catch (e) {
    erreur.value = messageErreur(e)
  } finally {
    envoi.value = false
  }
}
onMounted(charger)
</script>
