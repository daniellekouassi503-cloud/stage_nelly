<template>
  <AuthLayout>
    <h4>Compte en attente</h4>
    <p>Bonjour {{ session.utilisateur?.nom_complet }}. L’administration doit encore vérifier votre lien avec l’étudiant indiqué dans le dossier.</p>
    <p class="text-muted">Tant que le compte n’est pas validé, les notes, les frais et les supports restent inaccessibles.</p>
    <button class="btn btn-outline-primary" @click="sortir">Se déconnecter</button>
  </AuthLayout>
</template>

<script setup>
import { useRouter } from 'vue-router'
import AuthLayout from '../layouts/AuthLayout.vue'
import { useSession } from '../stores/session'

const session = useSession()
const router = useRouter()
async function sortir() {
  await session.deconnecter()
  router.push('/connexion')
}
</script>
