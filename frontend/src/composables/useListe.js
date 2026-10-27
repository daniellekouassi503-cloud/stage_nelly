import { computed, ref, watch } from 'vue'

export function useListe(source, texteDe, taille = 8) {
  const recherche = ref('')
  const page = ref(1)

  const filtre = computed(() => {
    const liste = source.value ?? []
    const terme = recherche.value.trim().toLowerCase()
    if (!terme) return liste
    return liste.filter((element) => texteDe(element).toLowerCase().includes(terme))
  })

  const pages = computed(() => Math.max(1, Math.ceil(filtre.value.length / taille)))
  const visibles = computed(() => filtre.value.slice((page.value - 1) * taille, page.value * taille))

  watch(recherche, () => { page.value = 1 })
  watch(pages, (total) => { if (page.value > total) page.value = total })

  return { recherche, page, pages, filtre, visibles }
}
