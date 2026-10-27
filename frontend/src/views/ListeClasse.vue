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
    <p v-else-if="chargement" class="etat-chargement">Chargement de la liste…</p>
    <div v-else-if="donnees" class="card">
      <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
          <div>
            <h5 class="mb-1">Liste de classe — {{ donnees.classe.libelle_classe }}</h5>
            <p class="text-muted mb-0">
              {{ donnees.classe.filiere }} · {{ donnees.classe.niveau }} · {{ donnees.code_annee }}
              · Effectif : {{ donnees.effectif }}
            </p>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-striped mb-0">
            <thead>
              <tr>
                <th style="width: 4rem">N°</th>
                <th style="width: 8rem">Matricule</th>
                <th>Nom &amp; prénoms</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="e in donnees.etudiants" :key="e.matricule">
                <td>{{ e.ordre }}</td>
                <td>{{ e.matricule }}</td>
                <td>{{ e.nom }} {{ e.prenoms }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import http, { messageErreur } from '../api'
import { useSession } from '../stores/session'

const session = useSession()
const donnees = ref(null)
const classes = ref([])
const erreur = ref('')
const chargement = ref(false)
const matricule = ref(session.enfantActif?.matricule || '')
const codeClasse = ref('')
const selectClasse = computed(() => ['administrateur', 'enseignant'].includes(session.role))

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
  chargement.value = true
  try {
    const params = {}
    if (session.role === 'parent' && matricule.value) params.matricule = matricule.value
    if (selectClasse.value && codeClasse.value) params.code_classe = codeClasse.value
    const { data } = await http.get('/api/liste-classe', { params })
    donnees.value = data.donnees
    if (donnees.value?.classe?.code_classe) codeClasse.value = donnees.value.classe.code_classe
  } catch (e) {
    donnees.value = null
    erreur.value = messageErreur(e)
  } finally {
    chargement.value = false
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
