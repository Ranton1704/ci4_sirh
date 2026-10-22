import { describe, expect, it, vi, beforeEach, afterEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import ProgressBar from '../src/components/onboarding/ProgressBar.vue'
import StatusBadge from '../src/components/onboarding/StatusBadge.vue'
import ProgressDisplay from '../src/components/onboarding/ProgressDisplay.vue'
import ResponsableAssign from '../src/components/onboarding/ResponsableAssign.vue'
import ItemValidate from '../src/views/onboarding/ItemValidate.vue'
import { calculateProgress, isEnRetard } from '../src/utils/onboarding'

const RouterLink = { props: ['to'], template: '<a><slot /></a>' }

function mockFetch(routes) {
  const calls = []
  globalThis.fetch = vi.fn(async (url, opts = {}) => {
    calls.push({ url, method: opts.method, body: opts.body ? JSON.parse(opts.body) : undefined })
    const key = `${opts.method} ${url}`
    const body = typeof routes[key] === 'function' ? routes[key](opts) : routes[key]
    return { ok: body !== undefined, status: body !== undefined ? 200 : 404, text: async () => JSON.stringify(body ?? { messages: { error: 'introuvable' } }) }
  })
  return calls
}

describe('calculateProgress', () => {
  it('calcule le pourcentage terminé/validé et les retards', () => {
    const items = [
      { statut: 'valide', date_echeance: '2026-11-01' },
      { statut: 'termine', date_echeance: '2026-11-01' },
      { statut: 'en_cours', date_echeance: '2026-11-05' },
      { statut: 'a_faire', date_echeance: '2026-11-20' },
    ]
    expect(calculateProgress(items, '2026-11-10')).toEqual({ total: 4, termines: 2, valides: 1, en_retard: 1, pourcentage: 50 })
  })

  it('retourne 0 % pour une liste vide', () => {
    expect(calculateProgress([]).pourcentage).toBe(0)
  })

  it("ne considère pas un item terminé comme en retard", () => {
    expect(isEnRetard({ statut: 'termine', date_echeance: '2020-01-01' })).toBe(false)
    expect(isEnRetard({ statut: 'a_faire', date_echeance: '2020-01-01' })).toBe(true)
  })
})

describe('ProgressBar', () => {
  it('borne la valeur entre 0 et 100', () => {
    expect(mount(ProgressBar, { props: { value: 140 } }).text()).toContain('100%')
    expect(mount(ProgressBar, { props: { value: -5 } }).text()).toContain('0%')
    expect(mount(ProgressBar, { props: { value: 66.6 } }).find('.progress-fill').attributes('style')).toContain('width: 67%')
  })
})

describe('StatusBadge', () => {
  it('affiche le libellé et la couleur du statut', () => {
    const w = mount(StatusBadge, { props: { statut: 'valide' } })
    expect(w.text()).toBe('Validé')
    expect(w.classes()).toContain('green')
    expect(mount(StatusBadge, { props: { statut: 'inconnu' } }).text()).toBe('inconnu')
  })
})

describe('ProgressDisplay', () => {
  it('expose calculateProgress et affiche le résumé', () => {
    const w = mount(ProgressDisplay, { props: { items: [{ statut: 'valide' }, { statut: 'a_faire' }] } })
    expect(w.vm.calculateProgress().pourcentage).toBe(50)
    expect(w.text()).toContain('1/2 terminé(s)')
  })
})

describe('ResponsableAssign', () => {
  afterEach(() => vi.restoreAllMocks())

  it("assigne le responsable choisi via l'API", async () => {
    const calls = mockFetch({
      'GET /api/utilisateurs': [{ id: '1', email: 'it@sirh.local' }, { id: '2', email: 'rh@sirh.local' }],
      'PUT /api/onboarding/items/5/responsable': { id: 5, responsable_id: 2 },
    })
    const w = mount(ResponsableAssign, { props: { itemId: 5, modelValue: 1 } })
    await flushPromises()
    await w.find('select').setValue(2)
    await w.find('button').trigger('click')
    await flushPromises()

    expect(calls.at(-1)).toMatchObject({ method: 'PUT', body: { responsable_id: 2 } })
    expect(w.emitted('assigned')[0][0]).toEqual({ id: 5, responsable_id: 2 })
  })
})

describe('ItemValidate', () => {
  beforeEach(() => localStorage.clear())

  it('liste les items terminés et les valide', async () => {
    const calls = mockFetch({
      'GET /api/onboarding/items?statut=termine': [{ id: 9, employe_id: 3, titre: 'Badge', statut: 'termine' }],
      'POST /api/onboarding/items/9/valider': { id: 9, statut: 'valide' },
    })
    const w = mount(ItemValidate, { global: { stubs: { RouterLink } } })
    await flushPromises()
    expect(w.text()).toContain('Badge')

    await w.find('button.success').trigger('click')
    await flushPromises()

    expect(calls.at(-1)).toMatchObject({ method: 'POST', body: { approuve: true, commentaire: null } })
    expect(w.text()).toContain('Aucun item à valider')
  })
})
