<template>
  <div>
    <h3 class="page-title mb-3">Journal d’activité</h3>
    <div class="card"><div class="card-body table-responsive">
      <p v-if="!lignes.length" class="etat-vide">Aucune action enregistrée.</p>
      <table v-else class="table">
        <thead><tr><th>Date</th><th>Auteur</th><th>Action</th><th>Description</th></tr></thead>
        <tbody>
          <tr v-for="l in lignes" :key="l.id_journal">
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
import { onMounted, ref } from 'vue'
import http from '../api'

const lignes = ref([])
onMounted(async () => {
  const { data } = await http.get('/api/journal')
  lignes.value = data.donnees
})
</script>
