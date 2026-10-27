<template>
  <div class="barre-liste">
    <label class="visually-hidden" :for="id">Filtrer</label>
    <input :id="id" :value="recherche" class="form-control" type="search" placeholder="Filtrer cette liste" @input="$emit('update:recherche', $event.target.value)" />
    <div class="barre-pages">
      <button class="btn btn-outline-primary btn-sm" type="button" :disabled="page <= 1" @click="$emit('update:page', page - 1)">Précédent</button>
      <span>{{ page }} / {{ pages }} · {{ total }}</span>
      <button class="btn btn-outline-primary btn-sm" type="button" :disabled="page >= pages" @click="$emit('update:page', page + 1)">Suivant</button>
    </div>
  </div>
</template>

<script setup>
defineProps({
  recherche: { type: String, default: '' },
  page: { type: Number, default: 1 },
  pages: { type: Number, default: 1 },
  total: { type: Number, default: 0 },
  id: { type: String, default: 'filtre-liste' },
})
defineEmits(['update:recherche', 'update:page'])
</script>
