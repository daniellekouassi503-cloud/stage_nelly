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
    const textes = Object.values(data.errors).flat().filter(Boolean)
    if (textes.length) {
      const brut = textes.join(' ')
      if (brut === 'validation.unique' || brut.startsWith('validation.')) {
        return 'Cette information est déjà utilisée (souvent l’e-mail). Choisissez une autre valeur ou connectez-vous.'
      }
      return brut
    }
  }
  const message = data?.message
  if (message && !String(message).startsWith('validation.')) return message
  return 'Une erreur est survenue. Réessayez.'
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
