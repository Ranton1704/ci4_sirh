const BASE = import.meta.env.VITE_API_URL ?? '/api'

export class ApiError extends Error {
  constructor(message, status, errors = {}) {
    super(message)
    this.status = status
    this.errors = errors
  }
}

function headers() {
  const h = { 'Content-Type': 'application/json', Accept: 'application/json' }
  const utilisateurId = localStorage.getItem('utilisateur_id')
  if (utilisateurId) h['X-Utilisateur-Id'] = utilisateurId
  return h
}

async function request(method, path, body) {
  const response = await fetch(`${BASE}${path}`, {
    method,
    headers: headers(),
    body: body === undefined ? undefined : JSON.stringify(body),
  })
  const text = await response.text()
  const data = text ? JSON.parse(text) : null
  if (!response.ok) {
    const errors = data?.messages ?? {}
    const message = Object.values(errors).join(' ') || data?.message || `Erreur HTTP ${response.status}`
    throw new ApiError(message, response.status, errors)
  }
  return data
}

function query(params = {}) {
  const qs = new URLSearchParams(Object.entries(params).filter(([, v]) => v !== undefined && v !== null && v !== ''))
  return qs.toString() ? `?${qs}` : ''
}

export const onboardingApi = {
  // Modèle de checklist
  getChecklist: (params) => request('GET', `/onboarding/checklist${query(params)}`),
  createChecklistItem: (data) => request('POST', '/onboarding/checklist', data),
  updateChecklistItem: (id, data) => request('PUT', `/onboarding/checklist/${id}`, data),
  deleteChecklistItem: (id) => request('DELETE', `/onboarding/checklist/${id}`),
  linkToEmploye: (employeId, data = {}) => request('POST', `/onboarding/employes/${employeId}/checklist`, data),

  // Items par employé
  getItems: (params) => request('GET', `/onboarding/items${query(params)}`),
  getEmployeItems: (employeId) => request('GET', `/onboarding/employes/${employeId}/items`),
  createItem: (data) => request('POST', '/onboarding/items', data),
  updateItem: (id, data) => request('PUT', `/onboarding/items/${id}`, data),
  deleteItem: (id) => request('DELETE', `/onboarding/items/${id}`),
  validateItem: (id, approuve, commentaire) => request('POST', `/onboarding/items/${id}/valider`, { approuve, commentaire }),
  assignResponsable: (id, responsableId) => request('PUT', `/onboarding/items/${id}/responsable`, { responsable_id: responsableId }),
  notifyResponsables: (id) => request('POST', `/onboarding/items/${id}/notifier`),

  // Suivi / tableau de bord / historique
  getTracking: () => request('GET', '/onboarding/suivi'),
  getEmployeTracking: (employeId) => request('GET', `/onboarding/employes/${employeId}/suivi`),
  getDashboard: () => request('GET', '/onboarding/dashboard'),
  getHistory: (params) => request('GET', `/onboarding/historique${query(params)}`),

  // Référentiel commun
  getUtilisateurs: () => request('GET', '/utilisateurs'),
}
