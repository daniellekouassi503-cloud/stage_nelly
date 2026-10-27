<template>
  <div>
    <p v-if="erreur" class="etat-erreur">{{ erreur }}</p>
    <p v-if="message" class="text-success">{{ message }}</p>

    <div class="card mb-3"><div class="card-body">
      <p class="text-muted mb-3">
        Un enseignant peut programmer un devoir ou une interrogation pour une matière et une classe où il est affecté.
        Les étudiants voient alors la date dans <strong>Devoirs programmés</strong> ; les notes restent invisibles tant qu’elles ne sont pas publiées.
        <template v-if="session.role === 'administrateur'"> L’affectation se fait dans <router-link to="/personnes">Personnes → Affectations</router-link>.</template>
      </p>
      <form class="row g-3" @submit.prevent="creer">
        <div class="col-md-2"><label class="form-label">Type</label><select v-model="form.type_evaluation" class="form-select" required><option value="devoir">Devoir</option><option value="interrogation">Interrogation</option></select></div>
        <div class="col-md-2"><label class="form-label">Date</label><input v-model="form.date_evaluation" type="date" class="form-control" required /></div>
        <div class="col-md-2"><label class="form-label">Semestre</label><select v-model="form.semestre" class="form-select"><option :value="1">Semestre 1</option><option :value="2">Semestre 2</option></select></div>
        <div class="col-md-2"><label class="form-label">Classe</label><select v-model="form.code_classe" class="form-select" required><option value="">Choisir</option><option v-for="a in classesUniques" :key="a.code" :value="a.code">{{ a.libelle }}</option></select></div>
        <div class="col-md-2"><label class="form-label">Matière</label><select v-model="form.code_matiere" class="form-select" required><option value="">Choisir</option><option v-for="a in matieresFiltrees" :key="a.code" :value="a.code">{{ a.libelle }}</option></select></div>
        <div v-if="session.role === 'administrateur'" class="col-md-2">
          <label class="form-label">Enseignant</label>
          <select v-model="form.code_enseignant" class="form-select" required>
            <option value="">Choisir</option>
            <option v-for="e in enseignantsFiltres" :key="e.code" :value="e.code">{{ e.nom }}</option>
          </select>
        </div>
        <div class="col-md-2"><label class="form-label">Année</label><select v-model="form.code_annee" class="form-select" required><option value="">Choisir</option><option v-for="a in annees" :key="a.code_annee" :value="a.code_annee">{{ a.libelle_annee }}</option></select></div>
        <div class="col-12"><button class="btn btn-primary" :disabled="envoi">Programmer</button></div>
      </form>
    </div></div>

    <div class="row">
      <div class="col-lg-5">
        <div class="card"><div class="card-body">
          <p v-if="!evaluations.length" class="etat-vide">Aucune évaluation.</p>
          <button v-for="ev in evaluations" :key="ev.num_evaluation" class="btn btn-light w-100 text-start mb-2" type="button" @click="ouvrir(ev)">
            <strong>{{ ev.matiere?.libelle_matiere }}</strong> · {{ ev.classe?.libelle_classe }}<br>
            <small>{{ ev.type_evaluation }} · semestre {{ ev.semestre }}</small>
            <span class="badge-statut ms-2" :class="ev.statut_publication === 'publiee' ? 'positif' : 'ambre'">{{ libelleStatut(ev.statut_publication) }}</span>
          </button>
        </div></div>
      </div>
      <div class="col-lg-7">
        <div class="card"><div class="card-body">
          <p v-if="!selection" class="etat-vide">Choisissez une évaluation pour saisir les notes.</p>
          <template v-else>
            <h5>{{ selection.matiere?.libelle_matiere }} — {{ libelleStatut(selection.statut_publication) }}</h5>
            <p class="text-muted">Une case vide signifie l’absence de note. Elle n’est pas comptée comme zéro.</p>
            <div v-for="ligne in notes" :key="ligne.matricule" class="row g-2 mb-2 align-items-center">
              <div class="col-7">{{ ligne.nom_complet }} <small class="text-muted">{{ ligne.matricule }}</small></div>
              <div class="col-5"><input v-model="ligne.note" class="form-control" type="number" min="0" max="20" step="0.01" :disabled="selection.statut_publication === 'publiee'" /></div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
              <button v-if="selection.statut_publication !== 'publiee'" class="btn btn-outline-primary" type="button" @click="sauver">Enregistrer le brouillon</button>
              <button v-if="selection.statut_publication === 'brouillon'" class="btn btn-primary" type="button" @click="soumettre">Soumettre</button>
              <button v-if="selection.statut_publication === 'non_publiee'" class="btn btn-primary" type="button" @click="publier">Publier</button>
            </div>
          </template>
        </div></div>
        <div class="card mt-3"><div class="card-header"><h5 class="mb-0">Signalements</h5></div><div class="card-body">
          <p v-if="!signalements.length" class="etat-vide mb-0">Aucun signalement.</p>
          <div v-for="s in signalements" :key="s.num_signalement" class="border-bottom py-2">
            <strong>{{ s.etudiant?.utilisateur?.nom_complet || s.matricule }}</strong> — {{ s.statut_signalement }}<br>
            {{ s.motif_signalement }}
            <div v-if="s.statut_signalement === 'en_attente'" class="row g-2 mt-1">
              <div class="col-md-5"><input v-model="s.reponseSaisie" class="form-control" placeholder="Réponse" /></div>
              <div class="col-md-3"><input v-model="s.noteSaisie" class="form-control" type="number" min="0" max="20" step="0.01" placeholder="Nouvelle note" /></div>
              <div class="col-md-3"><button class="btn btn-primary btn-sm" type="button" @click="traiter(s)">Traiter</button></div>
            </div>
          </div>
        </div></div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import http, { messageErreur } from '../api'
import { useSession } from '../stores/session'

const session = useSession()
const evaluations = ref([])
const affectations = ref([])
const annees = ref([])
const signalements = ref([])
const notes = ref([])
const selection = ref(null)
const erreur = ref('')
const message = ref('')
const envoi = ref(false)
const form = reactive({ type_evaluation: 'devoir', date_evaluation: '', semestre: 1, code_matiere: '', code_classe: '', code_annee: '', code_enseignant: '' })

function libelleStatut(statut) {
  return { brouillon: 'brouillon', non_publiee: 'non publiée', publiee: 'publiée' }[statut] || statut
}
const classesUniques = computed(() => uniqueParCode(affectations.value.map((a) => ({
  code: a.code_classe,
  libelle: a.classe?.libelle_classe || a.code_classe,
}))))
const matieresFiltrees = computed(() => uniqueParCode(affectations.value
  .filter((a) => !form.code_classe || a.code_classe === form.code_classe)
  .map((a) => ({
    code: a.code_matiere,
    libelle: a.matiere?.libelle_matiere || a.code_matiere,
  }))))
const enseignantsFiltres = computed(() => uniqueParCode(affectations.value
  .filter((a) => (!form.code_classe || a.code_classe === form.code_classe)
    && (!form.code_matiere || a.code_matiere === form.code_matiere))
  .map((a) => ({
    code: a.code_enseignant,
    nom: a.enseignant?.utilisateur?.nom_complet || a.code_enseignant,
  }))))

function uniqueParCode(lignes) {
  const vus = new Set()
  return lignes.filter((ligne) => {
    if (!ligne.code || vus.has(ligne.code)) return false
    vus.add(ligne.code)
    return true
  })
}

watch(() => form.code_classe, () => {
  if (!matieresFiltrees.value.some((m) => m.code === form.code_matiere)) form.code_matiere = ''
})
watch([() => form.code_classe, () => form.code_matiere], () => {
  if (enseignantsFiltres.value.length === 1) {
    form.code_enseignant = enseignantsFiltres.value[0].code
  } else if (!enseignantsFiltres.value.some((e) => e.code === form.code_enseignant)) {
    form.code_enseignant = ''
  }
})

async function charger() {
  const appels = [http.get('/api/evaluations'), http.get('/api/affectations'), http.get('/api/signalements')]
  if (session.role === 'administrateur') appels.push(http.get('/api/annees'))
  const [ev, af, si, an] = await Promise.all(appels)
  evaluations.value = ev.data.donnees
  affectations.value = af.data.donnees
  signalements.value = si.data.donnees
  annees.value = an?.data.donnees ?? [{ code_annee: affectations.value[0] ? '' : '' }]
  if (!annees.value.length || !annees.value[0].code_annee) {
    const liste = await http.get('/api/tableau-de-bord')
    if (liste.data.donnees.annee_courante) annees.value = [liste.data.donnees.annee_courante]
  }
}

async function creer() {
  erreur.value = ''
  envoi.value = true
  try {
    const charge = { ...form }
    if (session.role !== 'administrateur') delete charge.code_enseignant
    await http.post('/api/evaluations', charge)
    message.value = 'Évaluation programmée. Les étudiants voient la date ; les notes restent masquées jusqu’à publication.'
    await charger()
  } catch (e) {
    erreur.value = messageErreur(e)
  } finally {
    envoi.value = false
  }
}
async function ouvrir(ev) {
  selection.value = ev
  const { data } = await http.get(`/api/evaluations/${ev.num_evaluation}/notes`)
  notes.value = data.donnees.notes
  selection.value = data.donnees.evaluation
}
async function sauver() {
  try {
    await http.put(`/api/evaluations/${selection.value.num_evaluation}/notes`, {
      notes: notes.value.map((n) => ({ matricule: n.matricule, note: n.note === '' ? null : n.note })),
    })
    message.value = 'Notes enregistrées.'
  } catch (e) {
    erreur.value = messageErreur(e)
  }
}
async function soumettre() {
  await action('soumettre', 'Évaluation soumise. Elle n’est pas encore visible.')
}
async function publier() {
  await action('publier', 'Notes publiées. Les étudiants et les parents concernés sont notifiés.')
}
async function action(verbe, texte) {
  try {
    await http.post(`/api/evaluations/${selection.value.num_evaluation}/${verbe}`)
    message.value = texte
    await charger()
    const ev = evaluations.value.find((e) => e.num_evaluation === selection.value.num_evaluation)
    if (ev) await ouvrir(ev)
  } catch (e) {
    erreur.value = messageErreur(e)
  }
}
async function traiter(signalement) {
  try {
    await http.post(`/api/signalements/${signalement.num_signalement}/traiter`, {
      reponse: signalement.reponseSaisie,
      note: signalement.noteSaisie === '' ? null : signalement.noteSaisie,
    })
    message.value = 'Signalement traité.'
    await charger()
  } catch (e) {
    erreur.value = messageErreur(e)
  }
}
onMounted(charger)
</script>
