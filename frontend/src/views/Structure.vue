<template>
  <div>
    <ul class="nav nav-tabs mb-3">
      <li v-for="onglet in onglets" :key="onglet.cle" class="nav-item">
        <button class="nav-link" :class="{ active: actif === onglet.cle }" type="button" @click="actif = onglet.cle">{{ onglet.libelle }}</button>
      </li>
    </ul>
    <p v-if="erreur" class="etat-erreur">{{ erreur }}</p>
    <p v-if="message" class="text-success">{{ message }}</p>

    <div class="card mb-3">
      <div class="card-body">
        <form class="row g-2" @submit.prevent="creer">
          <template v-if="actif === 'annees'">
            <div class="col-md-3"><label class="form-label">Code</label><input v-model="form.code_annee" class="form-control" required /></div>
            <div class="col-md-4"><label class="form-label">Libellé</label><input v-model="form.libelle_annee" class="form-control" required /></div>
            <div class="col-md-3 form-check mt-4"><input id="courante" v-model="form.est_courante" class="form-check-input" type="checkbox" /><label class="form-check-label" for="courante">Année courante</label></div>
          </template>
          <template v-else-if="actif === 'filieres'">
            <div class="col-md-3"><label class="form-label">Code</label><input v-model="form.code_filiere" class="form-control" required /></div>
            <div class="col-md-5"><label class="form-label">Libellé</label><input v-model="form.libelle_filiere" class="form-control" required /></div>
          </template>
          <template v-else-if="actif === 'niveaux'">
            <div class="col-md-3"><label class="form-label">Code</label><input v-model="form.code_niveau" class="form-control" required /></div>
            <div class="col-md-5"><label class="form-label">Libellé</label><input v-model="form.libelle_niveau" class="form-control" required /></div>
          </template>
          <template v-else-if="actif === 'classes'">
            <div class="col-md-2"><label class="form-label">Code</label><input v-model="form.code_classe" class="form-control" required /></div>
            <div class="col-md-3"><label class="form-label">Libellé</label><input v-model="form.libelle_classe" class="form-control" required /></div>
            <div class="col-md-3"><label class="form-label">Filière</label><select v-model="form.code_filiere" class="form-select" required><option value="">Choisir</option><option v-for="f in refs.filieres" :key="f.code_filiere" :value="f.code_filiere">{{ f.libelle_filiere }}</option></select></div>
            <div class="col-md-2"><label class="form-label">Niveau</label><select v-model="form.code_niveau" class="form-select" required><option value="">Choisir</option><option v-for="n in refs.niveaux" :key="n.code_niveau" :value="n.code_niveau">{{ n.libelle_niveau }}</option></select></div>
          </template>
          <template v-else-if="actif === 'matieres'">
            <div class="col-md-3"><label class="form-label">Code</label><input v-model="form.code_matiere" class="form-control" required /></div>
            <div class="col-md-5"><label class="form-label">Libellé</label><input v-model="form.libelle_matiere" class="form-control" required /></div>
          </template>
          <template v-else-if="actif === 'programmes'">
            <div class="col-md-3"><label class="form-label">Matière</label><select v-model="form.code_matiere" class="form-select" required><option value="">Choisir</option><option v-for="m in refs.matieres" :key="m.code_matiere" :value="m.code_matiere">{{ m.libelle_matiere }}</option></select></div>
            <div class="col-md-3"><label class="form-label">Filière</label><select v-model="form.code_filiere" class="form-select" required><option value="">Choisir</option><option v-for="f in refs.filieres" :key="f.code_filiere" :value="f.code_filiere">{{ f.code_filiere }}</option></select></div>
            <div class="col-md-2"><label class="form-label">Niveau</label><select v-model="form.code_niveau" class="form-select" required><option value="">Choisir</option><option v-for="n in refs.niveaux" :key="n.code_niveau" :value="n.code_niveau">{{ n.code_niveau }}</option></select></div>
            <div class="col-md-2"><label class="form-label">Coefficient</label><input v-model="form.coefficient" type="number" min="0.01" step="0.01" class="form-control" required /></div>
          </template>
          <template v-else>
            <div class="col-md-2"><label class="form-label">Code</label><input v-model="form.code_tranche" class="form-control" required /></div>
            <div class="col-md-2"><label class="form-label">Libellé</label><input v-model="form.libelle_tranche" class="form-control" required /></div>
            <div class="col-md-2"><label class="form-label">Montant FCFA</label><input v-model="form.montant_tranche" type="number" min="1" class="form-control" required /></div>
            <div class="col-md-2"><label class="form-label">Date limite</label><input v-model="form.date_limite" type="date" class="form-control" required /></div>
            <div class="col-md-2"><label class="form-label">Filière</label><select v-model="form.code_filiere" class="form-select" required><option value="">Choisir</option><option v-for="f in refs.filieres" :key="f.code_filiere" :value="f.code_filiere">{{ f.code_filiere }}</option></select></div>
            <div class="col-md-1"><label class="form-label">Niveau</label><select v-model="form.code_niveau" class="form-select" required><option value="">Niv.</option><option v-for="n in refs.niveaux" :key="n.code_niveau" :value="n.code_niveau">{{ n.code_niveau }}</option></select></div>
            <div class="col-md-2"><label class="form-label">Année</label><select v-model="form.code_annee" class="form-select" required><option value="">Choisir</option><option v-for="a in refs.annees" :key="a.code_annee" :value="a.code_annee">{{ a.libelle_annee }}</option></select></div>
          </template>
          <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100" :disabled="envoi">Enregistrer</button></div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-body table-responsive">
        <p v-if="chargement" class="etat-chargement">Chargement…</p>
        <p v-else-if="!filtre.length" class="etat-vide">Aucune donnée pour cet onglet.</p>
        <template v-else>
          <BarreListe v-model:recherche="recherche" v-model:page="page" :pages="pages" :total="filtre.length" id="filtre-structure" />
          <table class="table">
            <thead><tr><th>Élément</th></tr></thead>
            <tbody>
              <tr v-for="(ligne, index) in visibles" :key="index">
                <td>{{ resume(ligne) }}</td>
              </tr>
            </tbody>
          </table>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import http, { fcfa, messageErreur } from '../api'
import BarreListe from '../components/BarreListe.vue'
import { useListe } from '../composables/useListe'

const onglets = [
  { cle: 'annees', libelle: 'Années' },
  { cle: 'filieres', libelle: 'Filières' },
  { cle: 'niveaux', libelle: 'Niveaux' },
  { cle: 'classes', libelle: 'Classes' },
  { cle: 'matieres', libelle: 'Matières' },
  { cle: 'programmes', libelle: 'Coefficients' },
  { cle: 'tranches', libelle: 'Tranches' },
]
const actif = ref('annees')
const lignes = ref([])
const refs = reactive({ annees: [], filieres: [], niveaux: [], matieres: [] })
const form = reactive({})
const erreur = ref('')
const message = ref('')
const chargement = ref(false)
const envoi = ref(false)
const source = computed(() => lignes.value)
const { recherche, page, pages, filtre, visibles } = useListe(source, (ligne) => resume(ligne))

function resume(ligne) {
  if (ligne.libelle_annee) return `${ligne.code_annee} — ${ligne.libelle_annee}${ligne.est_courante ? ' (courante)' : ''}`
  if (ligne.libelle_filiere) return `${ligne.code_filiere} — ${ligne.libelle_filiere}`
  if (ligne.libelle_niveau) return `${ligne.code_niveau} — ${ligne.libelle_niveau}`
  if (ligne.libelle_classe) return `${ligne.libelle_classe} (${ligne.filiere?.libelle_filiere ?? ligne.code_filiere}, ${ligne.niveau?.libelle_niveau ?? ligne.code_niveau})`
  if (ligne.libelle_matiere && !ligne.coefficient) return `${ligne.code_matiere} — ${ligne.libelle_matiere}`
  if (ligne.coefficient) return `${ligne.code_matiere} · ${ligne.code_filiere} · ${ligne.code_niveau} · coef. ${ligne.coefficient}`
  if (ligne.montant_tranche) return `${ligne.libelle_tranche} · ${ligne.code_filiere}/${ligne.code_niveau} · ${fcfa(ligne.montant_tranche)} · limite ${ligne.date_limite?.slice?.(0, 10) ?? ligne.date_limite}`
  return JSON.stringify(ligne)
}

async function chargerRefs() {
  const [annees, filieres, niveaux, matieres] = await Promise.all([
    http.get('/api/annees'), http.get('/api/filieres'), http.get('/api/niveaux'), http.get('/api/matieres'),
  ])
  refs.annees = annees.data.donnees
  refs.filieres = filieres.data.donnees
  refs.niveaux = niveaux.data.donnees
  refs.matieres = matieres.data.donnees
}

async function charger() {
  chargement.value = true
  erreur.value = ''
  try {
    const { data } = await http.get('/api/' + actif.value)
    lignes.value = data.donnees
  } catch (e) {
    erreur.value = messageErreur(e)
  } finally {
    chargement.value = false
  }
}

async function creer() {
  erreur.value = ''
  message.value = ''
  envoi.value = true
  try {
    const charge = { ...form }
    if (actif.value === 'tranches') charge.montant_tranche = Number(charge.montant_tranche)
    if (actif.value === 'programmes') charge.coefficient = Number(charge.coefficient)
    await http.post('/api/' + actif.value, charge)
    message.value = 'Enregistrement effectué.'
    Object.keys(form).forEach((cle) => { form[cle] = '' })
    await chargerRefs()
    await charger()
  } catch (e) {
    erreur.value = messageErreur(e)
  } finally {
    envoi.value = false
  }
}

watch(actif, () => { recherche.value = ''; page.value = 1; charger() })
onMounted(async () => { await chargerRefs(); await charger() })
</script>
