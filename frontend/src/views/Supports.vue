<template>
  <div>
    <p v-if="erreur" class="etat-erreur">{{ erreur }}</p>
    <p v-if="message" class="text-success">{{ message }}</p>
    <div v-if="peutDeposer" class="card mb-3"><div class="card-body">
      <form class="row g-3" @submit.prevent="deposer">
        <div class="col-md-4"><label class="form-label">Titre</label><input v-model="form.titre" class="form-control" required /></div>
        <div class="col-md-4"><label class="form-label">Description</label><input v-model="form.description" class="form-control" /></div>
        <div class="col-md-4"><label class="form-label">Fichier</label><input type="file" class="form-control" required @change="choisir" /></div>
        <div class="col-md-3"><label class="form-label">Matière</label><select v-model="form.code_matiere" class="form-select" required><option value="">Choisir</option><option v-for="a in matieres" :key="a" :value="a">{{ a }}</option></select></div>
        <div class="col-md-3"><label class="form-label">Filière</label><select v-model="form.code_filiere" class="form-select" required><option value="">Choisir</option><option v-for="a in filieres" :key="a" :value="a">{{ a }}</option></select></div>
        <div class="col-md-3"><label class="form-label">Niveau</label><select v-model="form.code_niveau" class="form-select" required><option value="">Choisir</option><option v-for="a in niveaux" :key="a" :value="a">{{ a }}</option></select></div>
        <div class="col-md-3 d-flex align-items-end"><button class="btn btn-primary w-100" :disabled="envoi">Publier</button></div>
      </form>
    </div></div>
    <div class="card"><div class="card-body">
      <p v-if="chargement" class="etat-chargement">Chargement…</p>
      <p v-else-if="!supports.length" class="etat-vide">Aucun support disponible.</p>
      <table v-else class="table">
        <thead><tr><th>Titre</th><th>Matière</th><th>Public</th><th></th></tr></thead>
        <tbody>
          <tr v-for="s in supports" :key="s.code_support">
            <td>{{ s.titre_support }}<br><small class="text-muted">{{ s.description_support }}</small></td>
            <td>{{ s.matiere?.libelle_matiere }}</td>
            <td>{{ s.filiere?.libelle_filiere }} - {{ s.niveau?.libelle_niveau }}</td>
            <td><button class="btn btn-outline-primary btn-sm" type="button" @click="telechargerSupport(s)">Télécharger</button></td>
          </tr>
        </tbody>
      </table>
    </div></div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import http, { messageErreur, telecharger } from '../api'
import { useSession } from '../stores/session'

const session = useSession()
const supports = ref([])
const affectations = ref([])
const erreur = ref('')
const message = ref('')
const chargement = ref(true)
const envoi = ref(false)
const fichier = ref(null)
const form = reactive({ titre: '', description: '', code_matiere: '', code_filiere: '', code_niveau: '' })
const peutDeposer = computed(() => ['enseignant', 'administrateur'].includes(session.role))
const matieres = computed(() => [...new Set(affectations.value.map((a) => a.code_matiere))])
const filieres = computed(() => [...new Set(affectations.value.map((a) => a.classe?.code_filiere).filter(Boolean))])
const niveaux = computed(() => [...new Set(affectations.value.map((a) => a.classe?.code_niveau).filter(Boolean))])

function choisir(event) { fichier.value = event.target.files[0] }

async function charger() {
  try {
    const { data } = await http.get('/api/supports')
    supports.value = data.donnees
    if (peutDeposer.value) {
      const af = await http.get('/api/affectations')
      affectations.value = af.data.donnees
    }
  } catch (e) {
    erreur.value = messageErreur(e)
  } finally {
    chargement.value = false
  }
}
async function deposer() {
  const charge = new FormData()
  Object.entries(form).forEach(([cle, valeur]) => charge.append(cle, valeur))
  charge.append('fichier', fichier.value)
  envoi.value = true
  try {
    await http.post('/api/supports', charge)
    message.value = 'Support publié. Les étudiants concernés sont notifiés.'
    await charger()
  } catch (e) {
    erreur.value = messageErreur(e)
  } finally {
    envoi.value = false
  }
}
async function telechargerSupport(support) {
  try {
    await telecharger(`/api/supports/${support.code_support}/telecharger`, support.nom_original || support.titre_support)
  } catch (e) {
    erreur.value = messageErreur(e)
  }
}
onMounted(charger)
</script>
