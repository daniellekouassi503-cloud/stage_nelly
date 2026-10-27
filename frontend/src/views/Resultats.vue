<template>
  <div>
    <div class="card mb-3"><div class="card-body">
      <form class="row g-2" @submit.prevent="charger">
        <div v-if="session.role === 'parent'" class="col-md-4">
          <label class="form-label">Enfant</label>
          <select v-model="matricule" class="form-select" required>
            <option value="">Enfant</option>
            <option v-for="enfant in session.utilisateur?.enfants ?? []" :key="enfant.matricule" :value="enfant.matricule">{{ enfant.nom_complet }} · {{ enfant.matricule }}</option>
          </select>
        </div>
        <div v-else-if="session.role !== 'etudiant'" class="col-md-4">
          <label class="form-label">Matricule</label>
          <input v-model="matricule" class="form-control" required />
        </div>
        <div class="col-md-3">
          <label class="form-label">Période</label>
          <select v-model="semestre" class="form-select">
            <option value="">Année entière</option>
            <option value="1">Semestre 1</option>
            <option value="2">Semestre 2</option>
          </select>
        </div>
        <div class="col-md-3"><button class="btn btn-primary">Afficher</button></div>
        <div class="col-md-2"><button class="btn btn-outline-primary" type="button" @click="bulletin">Relevé PDF</button></div>
      </form>
    </div></div>
    <p v-if="erreur" class="etat-erreur">{{ erreur }}</p>
    <p v-else-if="chargement" class="etat-chargement">Calcul des moyennes…</p>
    <div v-else-if="releve" class="card"><div class="card-body">
      <h5>{{ releve.nom_complet }} — {{ releve.classe }}</h5>
      <p>{{ releve.filiere }} · {{ releve.niveau }} · {{ releve.libelle_annee }}</p>
      <div class="table-responsive">
        <table class="table">
          <thead><tr><th>Matière</th><th>Coef.</th><th>Notes</th><th>Moyenne</th></tr></thead>
          <tbody>
            <tr v-for="m in releve.matieres" :key="m.code_matiere">
              <td>{{ m.libelle_matiere }}</td>
              <td>{{ m.coefficient }}</td>
              <td>
                <span v-for="ev in m.evaluations" :key="ev.num_evaluation" class="me-2">
                  {{ ev.type_evaluation }} {{ noteFr(ev.note) }}
                </span>
              </td>
              <td>{{ m.moyenne_libelle === 'Moyenne non disponible' ? m.moyenne_libelle : noteFr(m.moyenne) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <h5>Moyenne générale : {{ releve.moyenne_generale ? noteFr(releve.moyenne_generale) : 'Moyenne non disponible' }}</h5>
      <div v-if="session.role === 'etudiant'" class="mt-3">
        <h6>Signaler une erreur</h6>
        <div class="row g-2">
          <div class="col-md-4"><label class="form-label">Évaluation</label><select v-model="signalement.num" class="form-select"><option value="">Choisir</option><option v-for="ev in evaluationsPubliees" :key="ev.num_evaluation" :value="ev.num_evaluation">{{ ev.libelle }}</option></select></div>
          <div class="col-md-5"><label class="form-label">Motif</label><input v-model="signalement.motif" class="form-control" /></div>
          <div class="col-md-3"><button class="btn btn-outline-primary" type="button" @click="signaler">Envoyer</button></div>
        </div>
      </div>
    </div></div>
    <p v-else class="etat-vide">Aucun résultat à afficher.</p>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import http, { messageErreur, noteFr, telecharger } from '../api'
import { useSession } from '../stores/session'

const session = useSession()
const matricule = ref(session.utilisateur?.matricule || '')
const semestre = ref('')
const releve = ref(null)
const erreur = ref('')
const chargement = ref(false)
const signalement = reactive({ num: '', motif: '' })
const evaluationsPubliees = computed(() => (releve.value?.matieres ?? []).flatMap((m) => m.evaluations.map((ev) => ({
  num_evaluation: ev.num_evaluation,
  libelle: `${m.libelle_matiere} · ${ev.type_evaluation}`,
}))))

function params() {
  const p = new URLSearchParams()
  if (matricule.value) p.set('matricule', matricule.value)
  if (semestre.value) p.set('semestre', semestre.value)
  return p.toString()
}
async function charger() {
  chargement.value = true
  erreur.value = ''
  try {
    const { data } = await http.get('/api/resultats?' + params())
    releve.value = data.donnees
  } catch (e) {
    releve.value = null
    erreur.value = messageErreur(e)
  } finally {
    chargement.value = false
  }
}
async function bulletin() {
  try {
    await telecharger('/api/resultats/bulletin?' + params(), `releve-${matricule.value || 'notes'}.pdf`)
  } catch (e) {
    erreur.value = messageErreur(e)
  }
}
async function signaler() {
  try {
    await http.post(`/api/evaluations/${signalement.num}/signalements`, { motif: signalement.motif })
    erreur.value = ''
    signalement.motif = ''
    alert('Signalement envoyé à l’enseignant.')
  } catch (e) {
    erreur.value = messageErreur(e)
  }
}
onMounted(() => {
  if (session.role === 'parent' && !matricule.value) {
    matricule.value = session.enfantActif?.matricule || session.utilisateur?.enfants?.[0]?.matricule || ''
  }
  if (matricule.value) charger()
})
</script>
