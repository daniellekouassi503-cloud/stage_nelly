import axios from 'axios'

const http = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '',
  headers: { Accept: 'application/json' },
})

http.interceptors.request.use((config) => {
  const jeton = localStorage.getItem('jeton')
  if (jeton) {
    config.headers.Authorization = `Bearer ${jeton}`
  }
  return config
})

export function messageErreur(erreur) {
  const data = erreur?.response?.data
  if (data?.errors) {
    return Object.values(data.errors).flat().join(' ')
  }
  return data?.message || 'Une erreur est survenue. Réessayez.'
}

export function fcfa(montant) {
  return `${new Intl.NumberFormat('fr-FR').format(montant ?? 0)} FCFA`
}

export function noteFr(valeur) {
  if (valeur === null || valeur === undefined || valeur === '') return '—'
  return Number(valeur).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

export async function telecharger(url, nom) {
  const reponse = await http.get(url, { responseType: 'blob' })
  const lien = document.createElement('a')
  lien.href = URL.createObjectURL(reponse.data)
  lien.download = nom
  lien.click()
  URL.revokeObjectURL(lien.href)
}

export default http
