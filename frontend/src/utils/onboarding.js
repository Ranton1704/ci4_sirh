export const STATUTS = {
  a_faire: { label: 'À faire', color: 'grey' },
  en_cours: { label: 'En cours', color: 'blue' },
  termine: { label: 'Terminé', color: 'orange' },
  valide: { label: 'Validé', color: 'green' },
  en_retard: { label: 'En retard', color: 'red' },
}

export const CATEGORIES = ['general', 'administratif', 'acces', 'informatique', 'materiel', 'formation', 'paie']

const TERMINES = ['termine', 'valide']

export function today() {
  return new Date().toISOString().slice(0, 10)
}

export function isEnRetard(item, ref = today()) {
  return !TERMINES.includes(item.statut) && !!item.date_echeance && item.date_echeance < ref
}

/**
 * Progression d'une checklist (même règle que OnboardingService::calculateProgress)
 */
export function calculateProgress(items = [], ref = today()) {
  const total = items.length
  const termines = items.filter((i) => TERMINES.includes(i.statut)).length
  const valides = items.filter((i) => i.statut === 'valide').length
  const enRetard = items.filter((i) => isEnRetard(i, ref)).length
  return {
    total,
    termines,
    valides,
    en_retard: enRetard,
    pourcentage: total > 0 ? Math.round((termines * 100) / total) : 0,
  }
}

export function formatDate(value) {
  if (!value) return '—'
  const d = new Date(value.replace(' ', 'T'))
  return Number.isNaN(d.getTime()) ? value : d.toLocaleDateString('fr-FR')
}

export function formatDateTime(value) {
  if (!value) return '—'
  const d = new Date(value.replace(' ', 'T'))
  return Number.isNaN(d.getTime()) ? value : d.toLocaleString('fr-FR')
}
