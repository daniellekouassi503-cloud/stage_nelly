<template>
  <div>
    <div class="card"><div class="card-body table-responsive">
      <BarreListe v-model:recherche="recherche" v-model:page="page" :pages="pages" :total="filtre.length" id="filtre-journal" />
      <p v-if="!filtre.length" class="etat-vide">Aucune action enregistrée.</p>
      <table v-else class="table">
        <thead><tr><th>Date</th><th>Auteur</th><th>Action</th><th>Description</th></tr></thead>
        <tbody>
          <tr v-for="l in visibles" :key="l.id_journal">
            <td>{{ l.date_action }}</td>
            <td>{{ l.auteur || '—' }}</td>
            <td>{{ l.action }}</td>
            <td>{{ l.description }}</td>
          </tr>
        </tbody>
      </table>
    </div></div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import http from '../api'
import BarreListe from '../components/BarreListe.vue'
import { useListe } from '../composables/useListe'

const lignes = ref([])
const source = computed(() => lignes.value)
const { recherche, page, pages, filtre, visibles } = useListe(source, (ligne) => `${ligne.auteur} ${ligne.action} ${ligne.description}`)
onMounted(async () => {
  const { data } = await http.get('/api/journal')
  lignes.value = data.donnees
})
</script>
