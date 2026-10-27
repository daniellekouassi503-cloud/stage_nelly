<template>
  <AuthLayout>
    <h4>Compte indisponible</h4>
    <p>Bonjour {{ session.utilisateur?.nom_complet }}. Ce compte parent n’est pas actif.</p>
    <p class="text-muted">Créez un nouveau compte avec un matricule d’étudiant présent dans la base de l’établissement, ou contactez l’administration.</p>
    <button class="btn btn-outline-primary" @click="sortir">Retour à l’accueil</button>
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
  router.push('/')
}
</script>
