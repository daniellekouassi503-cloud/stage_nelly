<template>
  <div>
    <h3 class="page-title mb-3">Personnes et inscriptions</h3>
    <ul class="nav nav-tabs mb-3">
      <li v-for="o in ['Étudiants', 'Enseignants', 'Parents', 'Inscriptions', 'Affectations']" :key="o" class="nav-item">
        <button class="nav-link" :class="{ active: onglet === o }" type="button" @click="onglet = o">{{ o }}</button>
      </li>
    </ul>
    <p v-if="erreur" class="etat-erreur">{{ erreur }}</p>
    <p v-if="message" class="text-success">{{ message }}</p>

    <div class="card mb-3"><div class="card-body">
      <form v-if="onglet === 'Étudiants'" class="row g-2" @submit.prevent="creerEtudiant">
        <div class="col-md-2"><input v-model="etudiant.matricule" class="form-control" placeholder="Matricule" required /></div>
        <div class="col-md-2"><input v-model="etudiant.nom" class="form-control" placeholder="Nom" required /></div>
        <div class="col-md-2"><input v-model="etudiant.prenoms" class="form-control" placeholder="Prénoms" required /></div>
        <div class="col-md-3"><input v-model="etudiant.email" type="email" class="form-control" placeholder="E-mail" required /></div>
        <div class="col-md-2"><input v-model="etudiant.mot_de_passe" type="password" class="form-control" placeholder="Mot de passe" required /></div>
        <div class="col-md-3"><input v-model="etudiant.nom_parent" class="form-control" placeholder="Nom du parent (dossier)" /></div>
        <div class="col-md-2"><input v-model="etudiant.telephone_parent" class="form-control" placeholder="Tél. parent" /></div>
        <div class="col-md-2"><button class="btn btn-primary">Créer</button></div>
      </form>
      <form v-else-if="onglet === 'Enseignants'" class="row g-2" @submit.prevent="creerEnseignant">
        <div class="col-md-2"><input v-model="enseignant.code_enseignant" class="form-control" placeholder="Code" required /></div>
        <div class="col-md-2"><input v-model="enseignant.nom" class="form-control" placeholder="Nom" required /></div>
        <div class="col-md-2"><input v-model="enseignant.prenoms" class="form-control" placeholder="Prénoms" required /></div>
        <div class="col-md-3"><input v-model="enseignant.email" type="email" class="form-control" placeholder="E-mail" required /></div>
        <div class="col-md-2"><input v-model="enseignant.mot_de_passe" type="password" class="form-control" placeholder="Mot de passe" required /></div>
        <div class="col-md-1"><button class="btn btn-primary">Créer</button></div>
      </form>
      <form v-else-if="onglet === 'Inscriptions'" class="row g-2" @submit.prevent="inscrire">
        <div class="col-md-3"><select v-model="inscription.matricule" class="form-select" required><option value="">Étudiant</option><option v-for="e in etudiants" :key="e.matricule" :value="e.matricule">{{ e.matricule }} — {{ e.utilisateur?.nom_complet }}</option></select></div>
        <div class="col-md-3"><select v-model="inscription.code_annee" class="form-select" required><option value="">Année</option><option v-for="a in annees" :key="a.code_annee" :value="a.code_annee">{{ a.libelle_annee }}</option></select></div>
        <div class="col-md-3"><select v-model="inscription.code_classe" class="form-select" required><option value="">Classe</option><option v-for="c in classes" :key="c.code_classe" :value="c.code_classe">{{ c.libelle_classe }}</option></select></div>
        <div class="col-md-2"><button class="btn btn-primary">Inscrire</button></div>
      </form>
      <form v-else-if="onglet === 'Affectations'" class="row g-2" @submit.prevent="affecter">
        <div class="col-md-3"><select v-model="affectation.code_enseignant" class="form-select" required><option value="">Enseignant</option><option v-for="e in enseignants" :key="e.code_enseignant" :value="e.code_enseignant">{{ e.utilisateur?.nom_complet }}</option></select></div>
        <div class="col-md-3"><select v-model="affectation.code_matiere" class="form-select" required><option value="">Matière</option><option v-for="m in matieres" :key="m.code_matiere" :value="m.code_matiere">{{ m.libelle_matiere }}</option></select></div>
        <div class="col-md-3"><select v-model="affectation.code_classe" class="form-select" required><option value="">Classe</option><option v-for="c in classes" :key="c.code_classe" :value="c.code_classe">{{ c.libelle_classe }}</option></select></div>
        <div class="col-md-2"><button class="btn btn-primary">Affecter</button></div>
      </form>
      <p v-else class="mb-0 text-muted">Comparez le téléphone déclaré avec celui du dossier avant de valider un parent.</p>
    </div></div>

    <div class="card"><div class="card-body table-responsive">
      <p v-if="chargement" class="etat-chargement">Chargement…</p>
      <table v-else class="table">
        <tbody>
          <tr v-for="e in etudiants" v-show="onglet === 'Étudiants'" :key="e.matricule">
            <td>{{ e.matricule }}</td>
            <td>{{ e.utilisateur?.nom_complet }}</td>
            <td>{{ e.nom_parent || '—' }} · {{ e.telephone_parent || '—' }}</td>
            <td>{{ e.inscriptions.map((i) => i.libelle_classe).join(', ') || 'Non inscrit' }}</td>
          </tr>
          <tr v-for="e in enseignants" v-show="onglet === 'Enseignants'" :key="e.code_enseignant">
            <td>{{ e.code_enseignant }}</td>
            <td>{{ e.utilisateur?.nom_complet }}</td>
            <td>{{ e.utilisateur?.email }}</td>
          </tr>
          <tr v-for="p in parents" v-show="onglet === 'Parents'" :key="p.code_parent">
            <td>
              <strong>{{ p.utilisateur?.nom_complet }}</strong><br>
              <span class="text-muted">{{ p.utilisateur?.statut_compte }} · {{ p.utilisateur?.telephone }}</span>
              <ul class="mb-2">
                <li v-for="d in p.demandes" :key="d.matricule">
                  {{ d.matricule }} {{ d.nom_complet }} ({{ d.lien_parente || 'lien non précisé' }}) — dossier : {{ d.nom_parent_dossier || '—' }} / {{ d.telephone_parent_dossier || '—' }} — {{ d.statut }}
                </li>
              </ul>
              <div v-if="p.utilisateur?.statut_compte === 'en_attente'" class="d-flex gap-2">
                <button class="btn btn-primary btn-sm" type="button" @click="decider(p, 'valide')">Valider les demandes en attente</button>
                <button class="btn btn-outline-danger btn-sm" type="button" @click="decider(p, 'rejete')">Rejeter</button>
              </div>
            </td>
          </tr>
          <tr v-for="i in inscriptions" v-show="onglet === 'Inscriptions'" :key="i.matricule + i.code_annee">
            <td>{{ i.etudiant?.utilisateur?.nom_complet || i.matricule }}</td>
            <td>{{ i.annee?.libelle_annee }}</td>
            <td>{{ i.classe?.libelle_classe }}</td>
          </tr>
          <tr v-for="(a, index) in affectations" v-show="onglet === 'Affectations'" :key="index">
            <td>{{ a.enseignant?.utilisateur?.nom_complet }}</td>
            <td>{{ a.matiere?.libelle_matiere }}</td>
            <td>{{ a.classe?.libelle_classe }}</td>
          </tr>
        </tbody>
      </table>
      <p v-if="vide" class="etat-vide">Aucune ligne.</p>
    </div></div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import http, { messageErreur } from '../api'

const onglet = ref('Étudiants')
const erreur = ref('')
const message = ref('')
const chargement = ref(true)
const etudiants = ref([])
const enseignants = ref([])
const parents = ref([])
const inscriptions = ref([])
const affectations = ref([])
const annees = ref([])
const classes = ref([])
const matieres = ref([])
const etudiant = reactive({ matricule: '', nom: '', prenoms: '', email: '', mot_de_passe: '', nom_parent: '', telephone_parent: '' })
const enseignant = reactive({ code_enseignant: '', nom: '', prenoms: '', email: '', mot_de_passe: '' })
const inscription = reactive({ matricule: '', code_annee: '', code_classe: '' })
const affectation = reactive({ code_enseignant: '', code_matiere: '', code_classe: '' })
const vide = computed(() => !chargement.value && (
  (onglet.value === 'Étudiants' && !etudiants.value.length) ||
  (onglet.value === 'Enseignants' && !enseignants.value.length) ||
  (onglet.value === 'Parents' && !parents.value.length)
))

async function charger() {
  chargement.value = true
  try {
    const [e, ens, p, i, a, an, cl, ma] = await Promise.all([
      http.get('/api/etudiants'), http.get('/api/enseignants'), http.get('/api/parents'),
      http.get('/api/inscriptions'), http.get('/api/affectations'), http.get('/api/annees'),
      http.get('/api/classes'), http.get('/api/matieres'),
    ])
    etudiants.value = e.data.donnees
    enseignants.value = ens.data.donnees
    parents.value = p.data.donnees
    inscriptions.value = i.data.donnees
    affectations.value = a.data.donnees
    annees.value = an.data.donnees
    classes.value = cl.data.donnees
    matieres.value = ma.data.donnees
  } catch (e) {
    erreur.value = messageErreur(e)
  } finally {
    chargement.value = false
  }
}

async function creerEtudiant() {
  await envoyer('/api/etudiants', { ...etudiant })
}
async function creerEnseignant() {
  await envoyer('/api/enseignants', { ...enseignant })
}
async function inscrire() {
  await envoyer('/api/inscriptions', { ...inscription })
}
async function affecter() {
  await envoyer('/api/affectations', { ...affectation })
}
async function envoyer(url, charge) {
  erreur.value = ''
  message.value = ''
  try {
    await http.post(url, charge)
    message.value = 'Enregistrement effectué.'
    await charger()
  } catch (e) {
    erreur.value = messageErreur(e)
  }
}
async function decider(parent, decision) {
  const motif = decision === 'rejete' ? window.prompt('Motif du rejet') : null
  if (decision === 'rejete' && !motif) return
  try {
    await http.post(`/api/parents/${parent.code_parent}/decision`, {
      decision,
      motif,
      rattachements: parent.demandes.filter((d) => d.statut === 'en_attente').map((d) => ({
        matricule: d.matricule,
        lien_parente: d.lien_parente,
        accepter: decision === 'valide',
      })),
    })
    message.value = 'Décision enregistrée.'
    await charger()
  } catch (e) {
    erreur.value = messageErreur(e)
  }
}

onMounted(charger)
</script>
