<template>
  <div>
    <p v-if="erreur" class="etat-erreur">{{ erreur }}</p>
    <p v-if="message" class="text-success">{{ message }}</p>

    <div v-if="session.role === 'administrateur'" class="card mb-3">
      <div class="card-body">
        <h5 class="mb-2">Publier un planning de composition</h5>
        <p class="text-muted">Image ou PDF du planning (lundi au samedi). Il est destiné à une filière et un niveau, par exemple BTS IDA 2.</p>
        <form class="row g-3" @submit.prevent="publier">
          <div class="col-md-6">
            <label class="form-label">Titre</label>
            <input v-model="form.titre" class="form-control" required placeholder="BTS IDA2 devoir commun n°4" />
          </div>
          <div class="col-md-3">
            <label class="form-label">Du</label>
            <input v-model="form.date_debut" type="date" class="form-control" required />
          </div>
          <div class="col-md-3">
            <label class="form-label">Au</label>
            <input v-model="form.date_fin" type="date" class="form-control" required />
          </div>
          <div class="col-md-3">
            <label class="form-label">Filière</label>
            <select v-model="form.code_filiere" class="form-select" required>
              <option value="">Choisir</option>
              <option v-for="f in filieres" :key="f.code_filiere" :value="f.code_filiere">{{ f.libelle_filiere }}</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Niveau</label>
            <select v-model="form.code_niveau" class="form-select" required>
              <option value="">Choisir</option>
              <option v-for="n in niveaux" :key="n.code_niveau" :value="n.code_niveau">{{ n.libelle_niveau }}</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Année</label>
            <select v-model="form.code_annee" class="form-select" required>
              <option value="">Choisir</option>
              <option v-for="a in annees" :key="a.code_annee" :value="a.code_annee">{{ a.libelle_annee }}</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Fichier ou image</label>
            <input class="form-control" type="file" accept=".pdf,.png,.jpg,.jpeg,.webp,image/*,application/pdf" required @change="choisir" />
          </div>
          <div class="col-12">
            <button class="btn btn-primary" :disabled="envoi">Publier le planning</button>
          </div>
        </form>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-body">
        <h5 class="mb-3">Planning de composition</h5>
        <p v-if="chargement" class="etat-chargement">Chargement…</p>
        <p v-else-if="!plannings.length" class="etat-vide mb-0">Aucun planning n’est encore publié pour votre filière et votre niveau.</p>
        <div v-else class="row g-3">
          <div v-for="p in plannings" :key="p.code_planning" class="col-lg-6">
            <article class="border rounded p-3 h-100">
              <strong>{{ p.titre_planning }}</strong>
              <div class="saas-meta mb-2">
                {{ p.filiere }} · {{ p.niveau }} · {{ dateFr(p.date_debut) }} → {{ dateFr(p.date_fin) }}
              </div>
              <img v-if="apercus[p.code_planning]" :src="apercus[p.code_planning]" :alt="p.titre_planning" class="img-fluid rounded mb-2" />
              <p v-else-if="p.est_image" class="text-muted">Chargement de l’image…</p>
              <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-outline-primary btn-sm" type="button" @click="ouvrirFichier(p)">
                  {{ p.est_image ? 'Ouvrir / télécharger' : 'Télécharger le PDF' }}
                </button>
                <button v-if="session.role === 'administrateur'" class="btn btn-outline-danger btn-sm" type="button" :disabled="envoi" @click="retirer(p)">Retirer</button>
              </div>
            </article>
          </div>
        </div>
      </div>
    </div>

    <div v-if="session.role === 'etudiant' || session.role === 'parent'" class="card">
      <div class="card-body">
        <h5 class="mb-3">Devoirs et interrogations de la classe</h5>
        <p v-if="!devoirs.length" class="etat-vide mb-0">Aucun devoir ni interrogation n’est encore programmé pour votre classe.</p>
        <div v-else class="table-responsive">
          <table class="table mb-0">
            <thead>
              <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Matière</th>
                <th>Classe</th>
                <th>Enseignant</th>
                <th>Statut</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="d in devoirs" :key="d.num_evaluation">
                <td>{{ dateFr(d.date_evaluation) }}</td>
                <td>{{ d.type_evaluation === 'interrogation' ? 'Interrogation' : 'Devoir' }}</td>
                <td>{{ d.matiere }}</td>
                <td>{{ d.classe }}</td>
                <td>{{ d.enseignant || '—' }}</td>
                <td>
                  <span class="badge-statut" :class="d.statut_affichage === 'notes_publiees' ? 'positif' : 'ambre'">
                    {{ d.statut_affichage === 'notes_publiees' ? 'Notes publiées' : 'Programmé' }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <p class="saas-meta mt-3 mb-0">Les notes n’apparaissent dans <router-link to="/resultats">Résultats</router-link> qu’après publication par l’enseignant.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, onUnmounted, reactive, ref } from 'vue'
import http, { messageErreur, telecharger } from '../api'
import { useSession } from '../stores/session'

const session = useSession()
const devoirs = ref([])
const plannings = ref([])
const apercus = reactive({})
const filieres = ref([])
const niveaux = ref([])
const annees = ref([])
const erreur = ref('')
const message = ref('')
const chargement = ref(true)
const envoi = ref(false)
const fichier = ref(null)
const form = reactive({
  titre: '',
  date_debut: '',
  date_fin: '',
  code_filiere: '',
  code_niveau: '',
  code_annee: '',
})

function dateFr(valeur) {
  if (!valeur) return '—'
  return new Date(`${valeur}T00:00:00`).toLocaleDateString('fr-FR')
}

function choisir(event) {
  fichier.value = event.target.files[0]
}

async function charger() {
  chargement.value = true
  erreur.value = ''
  try {
    const appels = [http.get('/api/plannings')]
    if (session.role !== 'administrateur') appels.push(http.get('/api/evaluations'))
    if (session.role === 'administrateur') {
      appels.push(http.get('/api/filieres'), http.get('/api/niveaux'), http.get('/api/annees'))
    }
    const [pl, evOuFil, niv, an] = await Promise.all(appels)
    plannings.value = pl.data.donnees || []
    if (session.role === 'administrateur') {
      filieres.value = evOuFil.data.donnees || []
      niveaux.value = niv.data.donnees || []
      annees.value = an.data.donnees || []
      if (!form.code_annee) {
        form.code_annee = annees.value.find((a) => a.est_courante)?.code_annee || annees.value[0]?.code_annee || ''
      }
    } else {
      devoirs.value = evOuFil.data.donnees || []
    }
    await chargerApercus()
  } catch (e) {
    erreur.value = messageErreur(e)
  } finally {
    chargement.value = false
  }
}

async function chargerApercus() {
  for (const p of plannings.value) {
    if (!p.est_image || apercus[p.code_planning]) continue
    try {
      const { data } = await http.get(`/api/plannings/${p.code_planning}/fichier`, { responseType: 'blob' })
      apercus[p.code_planning] = URL.createObjectURL(data)
    } catch {
      /* aperçu facultatif */
    }
  }
}

async function publier() {
  if (!fichier.value) {
    erreur.value = 'Ajoutez le fichier ou l’image du planning.'
    return
  }
  const charge = new FormData()
  Object.entries(form).forEach(([cle, valeur]) => charge.append(cle, valeur))
  charge.append('fichier', fichier.value)
  envoi.value = true
  erreur.value = ''
  try {
    await http.post('/api/plannings', charge)
    message.value = 'Planning publié. Les étudiants, parents et enseignants concernés le voient dans Devoirs programmés.'
    fichier.value = null
    await charger()
  } catch (e) {
    erreur.value = messageErreur(e)
  } finally {
    envoi.value = false
  }
}

async function ouvrirFichier(planning) {
  await telecharger(`/api/plannings/${planning.code_planning}/fichier`, planning.nom_original)
}

async function retirer(planning) {
  if (!window.confirm(`Retirer le planning « ${planning.titre_planning} » ?`)) return
  envoi.value = true
  try {
    await http.delete(`/api/plannings/${planning.code_planning}`)
    if (apercus[planning.code_planning]) {
      URL.revokeObjectURL(apercus[planning.code_planning])
      delete apercus[planning.code_planning]
    }
    message.value = 'Planning retiré.'
    await charger()
  } catch (e) {
    erreur.value = messageErreur(e)
  } finally {
    envoi.value = false
  }
}

onMounted(charger)
onUnmounted(() => {
  Object.values(apercus).forEach((url) => URL.revokeObjectURL(url))
})
</script>
