<template>
  <div>
    <div class="card mb-3">
      <div class="card-body">
        <form class="row g-2 align-items-end" @submit.prevent="charger">
          <div v-if="session.role === 'parent'" class="col-md-4">
            <label class="form-label">Enfant</label>
            <select v-model="matricule" class="form-select" required>
              <option value="">Enfant</option>
              <option v-for="enfant in session.utilisateur?.enfants ?? []" :key="enfant.matricule" :value="enfant.matricule">
                {{ enfant.nom_complet }} · {{ enfant.matricule }}
              </option>
            </select>
          </div>
          <div v-if="selectClasse" class="col-md-4">
            <label class="form-label">Classe</label>
            <select v-model="codeClasse" class="form-select" required>
              <option value="">Choisir une classe</option>
              <option v-for="c in classes" :key="c.code_classe" :value="c.code_classe">
                {{ c.libelle_classe }} ({{ c.code_classe }})
              </option>
            </select>
          </div>
          <div class="col-md-3">
            <button class="btn btn-primary" type="submit" :disabled="chargement">Afficher</button>
          </div>
        </form>
      </div>
    </div>

    <p v-if="erreur" class="etat-erreur">{{ erreur }}</p>
    <p v-if="message" class="text-success">{{ message }}</p>
    <p v-else-if="chargement" class="etat-chargement">Chargement de l’emploi du temps…</p>
    <template v-else-if="donnees">
      <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
          <h5 class="mb-1">{{ donnees.classe.libelle_classe }}</h5>
          <p class="text-muted mb-0">
            {{ donnees.classe.filiere }} · {{ donnees.classe.niveau }} · {{ donnees.code_annee }}
            <span v-if="donnees.provisoire" class="badge bg-warning text-dark ms-2">Provisoire</span>
          </p>
        </div>
      </div>

      <div v-if="donnees.peut_modifier" class="card mb-3">
        <div class="card-body">
          <h6 class="mb-3">{{ form.id_seance ? 'Modifier une séance' : 'Ajouter une séance' }}</h6>
          <form class="row g-2" @submit.prevent="enregistrerSeance">
            <div class="col-md-2">
              <label class="form-label">Jour</label>
              <select v-model.number="form.jour_semaine" class="form-select" required>
                <option :value="1">Lundi</option>
                <option :value="2">Mardi</option>
                <option :value="3">Mercredi</option>
                <option :value="4">Jeudi</option>
                <option :value="5">Vendredi</option>
                <option :value="6">Samedi</option>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label">Début</label>
              <input v-model="form.heure_debut" type="time" class="form-control" required />
            </div>
            <div class="col-md-2">
              <label class="form-label">Fin</label>
              <input v-model="form.heure_fin" type="time" class="form-control" required />
            </div>
            <div class="col-md-3">
              <label class="form-label">Matière</label>
              <select v-model="form.code_matiere" class="form-select" @change="remplirLibelle">
                <option value="">Choisir</option>
                <option v-for="m in donnees.matieres" :key="m.code_matiere" :value="m.code_matiere">{{ m.libelle_matiere }}</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Libellé</label>
              <input v-model="form.libelle_matiere" class="form-control" required />
            </div>
            <div class="col-md-3">
              <label class="form-label">Enseignant</label>
              <input v-model="form.enseignant" class="form-control" />
            </div>
            <div class="col-md-2">
              <label class="form-label">Salle</label>
              <input v-model="form.salle" class="form-control" />
            </div>
            <div class="col-md-2 form-check mt-4">
              <input id="provisoire" v-model="form.provisoire" class="form-check-input" type="checkbox" />
              <label class="form-check-label" for="provisoire">Provisoire</label>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
              <button class="btn btn-primary" type="submit" :disabled="envoi">{{ form.id_seance ? 'Enregistrer' : 'Ajouter' }}</button>
              <button v-if="form.id_seance" class="btn btn-outline-secondary" type="button" @click="viderForm">Annuler</button>
            </div>
          </form>
        </div>
      </div>

      <div class="table-responsive card">
        <table class="table table-bordered mb-0 emploi-grille">
          <thead>
            <tr>
              <th style="width: 7rem">Horaire</th>
              <th v-for="jour in donnees.jours" :key="jour.numero">{{ jour.libelle }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="creneau in creneaux" :key="creneau">
              <th>{{ creneau }}</th>
              <td v-for="jour in donnees.jours" :key="`${jour.numero}-${creneau}`">
                <div v-for="s in seancesAu(jour, creneau)" :key="s.id_seance" class="emploi-cellule">
                  <strong>{{ s.libelle_matiere }}</strong>
                  <small v-if="s.enseignant">{{ s.enseignant }}</small>
                  <small v-if="s.salle">{{ s.salle }}</small>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="card mt-3">
        <div class="card-body">
          <h6>Liste chronologique</h6>
          <table class="table table-sm mb-0">
            <thead>
              <tr>
                <th>Jour</th><th>Horaires</th><th>Matière</th><th>Enseignant</th><th>Salle</th>
                <th v-if="donnees.peut_modifier"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="s in donnees.seances" :key="s.id_seance">
                <td>{{ s.jour }}</td>
                <td>{{ s.heure_debut }} – {{ s.heure_fin }}</td>
                <td>{{ s.libelle_matiere }}</td>
                <td>{{ s.enseignant || '—' }}</td>
                <td>{{ s.salle || '—' }}</td>
                <td v-if="donnees.peut_modifier" class="text-end text-nowrap">
                  <button class="btn btn-outline-primary btn-sm me-1" type="button" @click="editerSeance(s)">Modifier</button>
                  <button class="btn btn-outline-danger btn-sm" type="button" :disabled="envoi" @click="supprimerSeance(s)">Retirer</button>
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
import { computed, onMounted, reactive, ref } from 'vue'
import http, { messageErreur } from '../api'
import { useSession } from '../stores/session'

const session = useSession()
const donnees = ref(null)
const classes = ref([])
const erreur = ref('')
const message = ref('')
const chargement = ref(false)
const envoi = ref(false)
const matricule = ref(session.enfantActif?.matricule || '')
const codeClasse = ref('')
const selectClasse = computed(() => ['administrateur', 'enseignant'].includes(session.role))
const form = reactive(formVide())

const creneaux = computed(() => {
  if (!donnees.value?.seances?.length) return []
  const set = new Set()
  for (const s of donnees.value.seances) {
    set.add(`${s.heure_debut} – ${s.heure_fin}`)
  }
  return [...set].sort()
})

function formVide() {
  return {
    id_seance: null,
    jour_semaine: 1,
    heure_debut: '07:15',
    heure_fin: '10:00',
    code_matiere: '',
    libelle_matiere: '',
    enseignant: '',
    salle: '',
    provisoire: true,
  }
}

function viderForm() {
  Object.assign(form, formVide())
}

function seancesAu(jour, creneau) {
  return (jour.seances || []).filter((s) => `${s.heure_debut} – ${s.heure_fin}` === creneau)
}

function remplirLibelle() {
  const m = (donnees.value?.matieres || []).find((x) => x.code_matiere === form.code_matiere)
  if (m) form.libelle_matiere = m.libelle_matiere
}

function editerSeance(s) {
  Object.assign(form, {
    id_seance: s.id_seance,
    jour_semaine: s.jour_semaine,
    heure_debut: s.heure_debut,
    heure_fin: s.heure_fin,
    code_matiere: s.code_matiere || '',
    libelle_matiere: s.libelle_matiere,
    enseignant: s.enseignant || '',
    salle: s.salle || '',
    provisoire: !!s.provisoire,
  })
}

async function chargerClasses() {
  if (!selectClasse.value) return
  const { data } = await http.get('/api/classes-disponibles')
  classes.value = data.donnees || []
  if (!codeClasse.value && classes.value.length) {
    codeClasse.value = classes.value[0].code_classe
  }
}

async function charger() {
  erreur.value = ''
  message.value = ''
  chargement.value = true
  try {
    const params = {}
    if (session.role === 'parent' && matricule.value) params.matricule = matricule.value
    if (selectClasse.value && codeClasse.value) params.code_classe = codeClasse.value
    const { data } = await http.get('/api/emploi-du-temps', { params })
    donnees.value = data.donnees
    if (donnees.value?.classe?.code_classe) codeClasse.value = donnees.value.classe.code_classe
  } catch (e) {
    donnees.value = null
    erreur.value = messageErreur(e)
  } finally {
    chargement.value = false
  }
}

async function enregistrerSeance() {
  erreur.value = ''
  message.value = ''
  envoi.value = true
  try {
    const charge = {
      jour_semaine: form.jour_semaine,
      heure_debut: form.heure_debut,
      heure_fin: form.heure_fin,
      code_matiere: form.code_matiere || null,
      libelle_matiere: form.libelle_matiere,
      enseignant: form.enseignant,
      salle: form.salle,
      provisoire: form.provisoire,
    }
    if (form.id_seance) {
      await http.put(`/api/emploi-du-temps/${form.id_seance}`, charge)
      message.value = 'Séance mise à jour.'
    } else {
      await http.post('/api/emploi-du-temps', {
        ...charge,
        code_classe: codeClasse.value || donnees.value?.classe?.code_classe,
        code_annee: donnees.value?.code_annee,
      })
      message.value = 'Séance ajoutée.'
    }
    viderForm()
    await charger()
  } catch (e) {
    erreur.value = messageErreur(e)
  } finally {
    envoi.value = false
  }
}

async function supprimerSeance(s) {
  if (!window.confirm(`Retirer ${s.libelle_matiere} (${s.jour} ${s.heure_debut}) ?`)) return
  envoi.value = true
  erreur.value = ''
  try {
    await http.delete(`/api/emploi-du-temps/${s.id_seance}`)
    message.value = 'Séance retirée.'
    if (form.id_seance === s.id_seance) viderForm()
    await charger()
  } catch (e) {
    erreur.value = messageErreur(e)
  } finally {
    envoi.value = false
  }
}

onMounted(async () => {
  try {
    await chargerClasses()
  } catch (e) {
    erreur.value = messageErreur(e)
  }
  await charger()
})
</script>

<style scoped>
.emploi-grille th, .emploi-grille td { vertical-align: top; min-width: 8rem; }
.emploi-cellule { display: flex; flex-direction: column; gap: 0.15rem; margin-bottom: 0.35rem; }
.emploi-cellule small { color: #64748b; }
</style>
