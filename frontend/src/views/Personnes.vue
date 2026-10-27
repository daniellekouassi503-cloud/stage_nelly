<template>
  <div>
    <ul class="nav nav-tabs mb-3">
      <li v-for="o in ['Étudiants', 'Enseignants', 'Parents', 'Inscriptions', 'Affectations']" :key="o" class="nav-item">
        <button class="nav-link" :class="{ active: onglet === o }" type="button" @click="onglet = o">{{ o }}</button>
      </li>
    </ul>
    <p v-if="erreur" class="etat-erreur">{{ erreur }}</p>
    <p v-if="message" class="text-success">{{ message }}</p>

    <div class="card mb-3"><div class="card-body">
      <form v-if="onglet === 'Étudiants'" class="row g-3" @submit.prevent="enregistrerEtudiant">
        <div class="col-md-2"><label class="form-label">Matricule</label><input v-model="etudiant.matricule" class="form-control" required :readonly="!!editionEtudiant" /></div>
        <div class="col-md-2"><label class="form-label">Nom</label><input v-model="etudiant.nom" class="form-control" required /></div>
        <div class="col-md-2"><label class="form-label">Prénoms</label><input v-model="etudiant.prenoms" class="form-control" required /></div>
        <div class="col-md-3"><label class="form-label">E-mail</label><input v-model="etudiant.email" type="email" class="form-control" required /></div>
        <div v-if="!editionEtudiant" class="col-md-2"><label class="form-label">Mot de passe</label><input v-model="etudiant.mot_de_passe" type="password" class="form-control" required /></div>
        <div class="col-md-3"><label class="form-label">Nom du parent (dossier)</label><input v-model="etudiant.nom_parent" class="form-control" /></div>
        <div class="col-md-2"><label class="form-label">Tél. parent</label><input v-model="etudiant.telephone_parent" class="form-control" /></div>
        <div class="col-md-2 d-flex align-items-end gap-2">
          <button class="btn btn-primary w-100" :disabled="envoi">{{ editionEtudiant ? 'Enregistrer' : 'Créer' }}</button>
          <button v-if="editionEtudiant" class="btn btn-outline-secondary" type="button" @click="annulerEditionEtudiant">Annuler</button>
        </div>
      </form>
      <form v-else-if="onglet === 'Enseignants'" class="row g-3" @submit.prevent="enregistrerEnseignant">
        <div class="col-md-2"><label class="form-label">Code</label><input v-model="enseignant.code_enseignant" class="form-control" required :readonly="!!editionEnseignant" /></div>
        <div class="col-md-2"><label class="form-label">Nom</label><input v-model="enseignant.nom" class="form-control" required /></div>
        <div class="col-md-2"><label class="form-label">Prénoms</label><input v-model="enseignant.prenoms" class="form-control" required /></div>
        <div class="col-md-3"><label class="form-label">E-mail</label><input v-model="enseignant.email" type="email" class="form-control" required /></div>
        <div v-if="!editionEnseignant" class="col-md-2"><label class="form-label">Mot de passe</label><input v-model="enseignant.mot_de_passe" type="password" class="form-control" required /></div>
        <div class="col-md-2 d-flex align-items-end gap-2">
          <button class="btn btn-primary w-100" :disabled="envoi">{{ editionEnseignant ? 'Enregistrer' : 'Créer' }}</button>
          <button v-if="editionEnseignant" class="btn btn-outline-secondary" type="button" @click="annulerEditionEnseignant">Annuler</button>
        </div>
      </form>
      <form v-else-if="onglet === 'Parents' && editionParent" class="row g-3" @submit.prevent="enregistrerParent">
        <div class="col-md-2"><label class="form-label">Nom</label><input v-model="parentForm.nom" class="form-control" required /></div>
        <div class="col-md-2"><label class="form-label">Prénoms</label><input v-model="parentForm.prenoms" class="form-control" required /></div>
        <div class="col-md-3"><label class="form-label">E-mail</label><input v-model="parentForm.email" type="email" class="form-control" required /></div>
        <div class="col-md-2"><label class="form-label">Téléphone</label><input v-model="parentForm.telephone" class="form-control" /></div>
        <div class="col-md-3 d-flex align-items-end gap-2">
          <button class="btn btn-primary" :disabled="envoi">Enregistrer</button>
          <button class="btn btn-outline-secondary" type="button" @click="editionParent = ''">Annuler</button>
        </div>
      </form>
      <form v-else-if="onglet === 'Inscriptions'" class="row g-2" @submit.prevent="inscrire">
        <div class="col-md-3"><select v-model="inscription.matricule" class="form-select" required><option value="">Étudiant</option><option v-for="e in etudiants" :key="e.matricule" :value="e.matricule">{{ e.matricule }} — {{ e.utilisateur?.nom_complet }}</option></select></div>
        <div class="col-md-3"><select v-model="inscription.code_annee" class="form-select" required><option value="">Année</option><option v-for="a in annees" :key="a.code_annee" :value="a.code_annee">{{ a.libelle_annee }}</option></select></div>
        <div class="col-md-3"><select v-model="inscription.code_classe" class="form-select" required><option value="">Classe</option><option v-for="c in classes" :key="c.code_classe" :value="c.code_classe">{{ c.libelle_classe }}</option></select></div>
        <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100" :disabled="envoi">Inscrire</button></div>
      </form>
      <form v-else-if="onglet === 'Affectations'" class="row g-2" @submit.prevent="affecter">
        <div class="col-md-3"><select v-model="affectation.code_enseignant" class="form-select" required><option value="">Enseignant</option><option v-for="e in enseignants" :key="e.code_enseignant" :value="e.code_enseignant">{{ e.utilisateur?.nom_complet }}</option></select></div>
        <div class="col-md-3"><select v-model="affectation.code_matiere" class="form-select" required><option value="">Matière</option><option v-for="m in matieres" :key="m.code_matiere" :value="m.code_matiere">{{ m.libelle_matiere }}</option></select></div>
        <div class="col-md-3"><select v-model="affectation.code_classe" class="form-select" required><option value="">Classe</option><option v-for="c in classes" :key="c.code_classe" :value="c.code_classe">{{ c.libelle_classe }}</option></select></div>
        <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100" :disabled="envoi">Affecter</button></div>
      </form>
      <p v-else class="mb-0 text-muted">Les nouveaux comptes parents s’activent automatiquement dès que le matricule saisi existe. Cette section sert surtout aux anciens dossiers encore en attente.</p>
    </div></div>

    <div class="card"><div class="card-body">
      <BarreListe v-model:recherche="recherche" v-model:page="page" :pages="pages" :total="filtre.length" id="filtre-personnes" />
      <p v-if="chargement" class="etat-chargement">Chargement…</p>
      <p v-else-if="!filtre.length" class="etat-vide">Aucune ligne ne correspond.</p>
      <div v-else class="table-responsive">
      <table class="table">
        <thead>
          <tr v-if="onglet === 'Étudiants'"><th>Matricule</th><th>Nom</th><th>Dossier parent</th><th>Classe</th><th></th></tr>
          <tr v-else-if="onglet === 'Enseignants'"><th>Code</th><th>Nom</th><th>E-mail</th><th></th></tr>
          <tr v-else-if="onglet === 'Inscriptions'"><th>Étudiant</th><th>Année</th><th>Classe</th></tr>
          <tr v-else-if="onglet === 'Affectations'"><th>Enseignant</th><th>Matière</th><th>Classe</th></tr>
          <tr v-else><th>Compte parent</th><th></th></tr>
        </thead>
        <tbody>
          <template v-if="onglet === 'Étudiants'">
            <tr v-for="e in visibles" :key="e.matricule">
              <td>{{ e.matricule }}</td>
              <td>{{ e.utilisateur?.nom_complet }}</td>
              <td>{{ e.nom_parent || '—' }} · {{ e.telephone_parent || '—' }}</td>
              <td>{{ e.inscriptions.map((i) => i.libelle_classe).join(', ') || 'Non inscrit' }}</td>
              <td class="text-end text-nowrap">
                <button class="btn btn-outline-primary btn-sm me-1" type="button" @click="editerEtudiant(e)">Modifier</button>
                <button class="btn btn-outline-secondary btn-sm me-1" type="button" :disabled="envoi" @click="resetMotDePasse('etudiants', e.matricule, e.utilisateur?.nom_complet)">Mot de passe</button>
                <button class="btn btn-outline-danger btn-sm" type="button" :disabled="envoi" @click="supprimerEtudiantCompte(e)">Supprimer</button>
              </td>
            </tr>
          </template>
          <template v-else-if="onglet === 'Enseignants'">
            <tr v-for="e in visibles" :key="e.code_enseignant">
              <td>{{ e.code_enseignant }}</td>
              <td>{{ e.utilisateur?.nom_complet }}</td>
              <td>{{ e.utilisateur?.email }}</td>
              <td class="text-end text-nowrap">
                <button class="btn btn-outline-primary btn-sm me-1" type="button" @click="editerEnseignant(e)">Modifier</button>
                <button class="btn btn-outline-secondary btn-sm me-1" type="button" :disabled="envoi" @click="resetMotDePasse('enseignants', e.code_enseignant, e.utilisateur?.nom_complet)">Mot de passe</button>
                <button class="btn btn-outline-danger btn-sm" type="button" :disabled="envoi" @click="supprimerEnseignantCompte(e)">Supprimer</button>
              </td>
            </tr>
          </template>
          <template v-else-if="onglet === 'Parents'">
            <tr v-for="p in visibles" :key="p.code_parent">
              <td>
                <strong>{{ p.utilisateur?.nom_complet }}</strong>
                <span class="badge-statut ms-2" :class="p.utilisateur?.statut_compte === 'valide' ? 'positif' : 'ambre'">{{ p.utilisateur?.statut_compte }}</span>
                <div class="saas-meta">{{ p.utilisateur?.email }} · {{ p.utilisateur?.telephone || 'sans téléphone' }}</div>
                <ul class="mb-2">
                  <li v-for="d in p.demandes" :key="d.matricule">
                    {{ d.matricule }} {{ d.nom_complet }} ({{ d.lien_parente || 'lien non précisé' }}) — dossier : {{ d.nom_parent_dossier || '—' }} / {{ d.telephone_parent_dossier || '—' }} — {{ d.statut }}
                  </li>
                </ul>
                <div v-if="p.utilisateur?.statut_compte === 'en_attente'" class="d-flex gap-2">
                  <button class="btn btn-primary btn-sm" type="button" :disabled="envoi" @click="decider(p, 'valide')">Valider les demandes en attente</button>
                  <button class="btn btn-outline-danger btn-sm" type="button" :disabled="envoi" @click="decider(p, 'rejete')">Rejeter</button>
                </div>
              </td>
              <td class="text-end align-top text-nowrap">
                <button class="btn btn-outline-primary btn-sm me-1" type="button" @click="editerParent(p)">Modifier</button>
                <button class="btn btn-outline-secondary btn-sm me-1" type="button" :disabled="envoi" @click="resetMotDePasse('parents', p.code_parent, p.utilisateur?.nom_complet)">Mot de passe</button>
                <button class="btn btn-outline-danger btn-sm" type="button" :disabled="envoi" @click="supprimerParentCompte(p)">Supprimer</button>
              </td>
            </tr>
          </template>
          <template v-else-if="onglet === 'Inscriptions'">
            <tr v-for="i in visibles" :key="i.matricule + i.code_annee">
              <td>{{ i.etudiant?.utilisateur?.nom_complet || i.matricule }}</td>
              <td>{{ i.annee?.libelle_annee }}</td>
              <td>{{ i.classe?.libelle_classe }}</td>
            </tr>
          </template>
          <template v-else>
            <tr v-for="(a, index) in visibles" :key="index">
              <td>{{ a.enseignant?.utilisateur?.nom_complet }}</td>
              <td>{{ a.matiere?.libelle_matiere }}</td>
              <td>{{ a.classe?.libelle_classe }}</td>
            </tr>
          </template>
        </tbody>
      </table>
      </div>
    </div></div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import http, { messageErreur } from '../api'
import BarreListe from '../components/BarreListe.vue'
import { useListe } from '../composables/useListe'

const onglet = ref('Étudiants')
const erreur = ref('')
const message = ref('')
const chargement = ref(true)
const envoi = ref(false)
const etudiants = ref([])
const enseignants = ref([])
const parents = ref([])
const inscriptions = ref([])
const affectations = ref([])
const annees = ref([])
const classes = ref([])
const matieres = ref([])
const editionEtudiant = ref('')
const editionEnseignant = ref('')
const editionParent = ref('')
const etudiant = reactive({ matricule: '', nom: '', prenoms: '', email: '', mot_de_passe: '', nom_parent: '', telephone_parent: '' })
const enseignant = reactive({ code_enseignant: '', nom: '', prenoms: '', email: '', mot_de_passe: '' })
const parentForm = reactive({ nom: '', prenoms: '', email: '', telephone: '' })
const inscription = reactive({ matricule: '', code_annee: '', code_classe: '' })
const affectation = reactive({ code_enseignant: '', code_matiere: '', code_classe: '' })
const source = computed(() => ({
  Étudiants: etudiants.value,
  Enseignants: enseignants.value,
  Parents: parents.value,
  Inscriptions: inscriptions.value,
  Affectations: affectations.value,
}[onglet.value] ?? []))
const { recherche, page, pages, filtre, visibles } = useListe(source, (ligne) => JSON.stringify(ligne))
watch(onglet, () => { recherche.value = ''; page.value = 1 })

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

function editerEtudiant(e) {
  editionEtudiant.value = e.matricule
  Object.assign(etudiant, {
    matricule: e.matricule,
    nom: e.utilisateur?.nom || '',
    prenoms: e.utilisateur?.prenoms || '',
    email: e.utilisateur?.email || '',
    mot_de_passe: '',
    nom_parent: e.nom_parent || '',
    telephone_parent: e.telephone_parent || '',
  })
}
function annulerEditionEtudiant() {
  editionEtudiant.value = ''
  Object.assign(etudiant, { matricule: '', nom: '', prenoms: '', email: '', mot_de_passe: '', nom_parent: '', telephone_parent: '' })
}
function editerEnseignant(e) {
  editionEnseignant.value = e.code_enseignant
  Object.assign(enseignant, {
    code_enseignant: e.code_enseignant,
    nom: e.utilisateur?.nom || '',
    prenoms: e.utilisateur?.prenoms || '',
    email: e.utilisateur?.email || '',
    mot_de_passe: '',
  })
}
function annulerEditionEnseignant() {
  editionEnseignant.value = ''
  Object.assign(enseignant, { code_enseignant: '', nom: '', prenoms: '', email: '', mot_de_passe: '' })
}
function editerParent(p) {
  editionParent.value = p.code_parent
  Object.assign(parentForm, {
    nom: p.utilisateur?.nom || '',
    prenoms: p.utilisateur?.prenoms || '',
    email: p.utilisateur?.email || '',
    telephone: p.utilisateur?.telephone || '',
  })
}
async function enregistrerEtudiant() {
  if (editionEtudiant.value) {
    const { mot_de_passe, matricule, ...reste } = etudiant
    await envoyerPut(`/api/etudiants/${encodeURIComponent(matricule)}`, reste, 'Fiche étudiant mise à jour.')
    annulerEditionEtudiant()
    return
  }
  await envoyer('/api/etudiants', { ...etudiant })
}
async function enregistrerEnseignant() {
  if (editionEnseignant.value) {
    const { mot_de_passe, code_enseignant, ...reste } = enseignant
    await envoyerPut(`/api/enseignants/${encodeURIComponent(code_enseignant)}`, reste, 'Fiche enseignant mise à jour.')
    annulerEditionEnseignant()
    return
  }
  await envoyer('/api/enseignants', { ...enseignant })
}
async function enregistrerParent() {
  await envoyerPut(`/api/parents/${encodeURIComponent(editionParent.value)}`, { ...parentForm }, 'Fiche parent mise à jour.')
  editionParent.value = ''
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
  envoi.value = true
  try {
    await http.post(url, charge)
    message.value = 'Enregistrement effectué.'
    await charger()
  } catch (e) {
    erreur.value = messageErreur(e)
  } finally {
    envoi.value = false
  }
}
async function envoyerPut(url, charge, succes) {
  erreur.value = ''
  message.value = ''
  envoi.value = true
  try {
    await http.put(url, charge)
    message.value = succes
    await charger()
  } catch (e) {
    erreur.value = messageErreur(e)
  } finally {
    envoi.value = false
  }
}
async function resetMotDePasse(type, identifiant, nom) {
  const mot = window.prompt(`Nouveau mot de passe pour ${nom || identifiant} (8 caractères minimum)`)
  if (!mot) return
  if (mot.length < 8) {
    erreur.value = 'Le mot de passe doit contenir au moins 8 caractères.'
    return
  }
  erreur.value = ''
  message.value = ''
  envoi.value = true
  try {
    await http.post(`/api/${type}/${encodeURIComponent(identifiant)}/mot-de-passe`, { mot_de_passe: mot })
    message.value = 'Mot de passe réinitialisé. L’utilisateur devra se reconnecter.'
  } catch (e) {
    erreur.value = messageErreur(e)
  } finally {
    envoi.value = false
  }
}
async function supprimerEtudiantCompte(etudiant) {
  const nom = etudiant.utilisateur?.nom_complet || etudiant.matricule
  if (!window.confirm(`Supprimer le compte étudiant de ${nom} (${etudiant.matricule}) ? Cette action est définitive.`)) return
  await envoyerDelete(`/api/etudiants/${encodeURIComponent(etudiant.matricule)}`, 'Compte étudiant supprimé.')
}
async function supprimerParentCompte(parent) {
  const nom = parent.utilisateur?.nom_complet || parent.code_parent
  if (!window.confirm(`Supprimer le compte parent de ${nom} ? Cette action est définitive.`)) return
  await envoyerDelete(`/api/parents/${encodeURIComponent(parent.code_parent)}`, 'Compte parent supprimé.')
}
async function supprimerEnseignantCompte(ens) {
  const nom = ens.utilisateur?.nom_complet || ens.code_enseignant
  if (!window.confirm(`Supprimer le compte enseignant de ${nom} ? Ses évaluations, notes et supports seront aussi retirés.`)) return
  await envoyerDelete(`/api/enseignants/${encodeURIComponent(ens.code_enseignant)}`, 'Compte enseignant supprimé.')
}
async function envoyerDelete(url, succes) {
  erreur.value = ''
  message.value = ''
  envoi.value = true
  try {
    await http.delete(url)
    message.value = succes
    await charger()
  } catch (e) {
    erreur.value = messageErreur(e)
  } finally {
    envoi.value = false
  }
}
async function decider(parent, decision) {
  const motif = decision === 'rejete' ? window.prompt('Motif du rejet') : null
  if (decision === 'rejete' && !motif) return
  envoi.value = true
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
  } finally {
    envoi.value = false
  }
}

onMounted(charger)
</script>
