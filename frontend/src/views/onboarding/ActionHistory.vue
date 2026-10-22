<template>
  <section>
    <h1>Historique des actions</h1>
    <form class="card row" @submit.prevent="fetchHistory">
      <input v-model.number="filters.employe_id" type="number" min="1" placeholder="ID employé" />
      <select v-model="filters.action">
        <option value="">Toutes les actions</option>
        <option v-for="a in ACTIONS" :key="a" :value="a">{{ a }}</option>
      </select>
      <button type="submit">Filtrer</button>
      <button class="secondary" type="button" @click="reset">Réinitialiser</button>
    </form>
    <div v-if="error" class="alert error">{{ error }}</div>

    <p v-if="loading" class="muted">Chargement…</p>
    <table v-else-if="historique.length">
      <thead><tr><th>Date</th><th>Action</th><th>Salarié</th><th>Item</th><th>Détails</th><th>Par</th></tr></thead>
      <tbody>
        <tr v-for="h in historique" :key="h.id">
          <td>{{ formatDateTime(h.date_action) }}</td>
          <td><strong>{{ h.action }}</strong></td>
          <td>{{ h.employe_id ? '#' + h.employe_id : '—' }}</td>
          <td>{{ h.item_id ? '#' + h.item_id : '—' }}</td>
          <td>{{ h.details }}</td>
          <td>{{ h.utilisateur_id ? 'Utilisateur #' + h.utilisateur_id : '—' }}</td>
        </tr>
      </tbody>
    </table>
    <p v-else class="muted">Aucune action enregistrée.</p>
  </section>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { onboardingApi } from '../../services/onboardingApi'
import { formatDateTime } from '../../utils/onboarding'

const ACTIONS = [
  'liaison', 'creation', 'modification', 'suppression', 'validation', 'rejet', 'assignation',
  'notification', 'notification_echec', 'modele_creation', 'modele_modification', 'modele_suppression',
]

const historique = ref([])
const filters = reactive({ employe_id: null, action: '' })
const loading = ref(false)
const error = ref('')

async function fetchHistory() {
  loading.value = true
  error.value = ''
  try {
    historique.value = await onboardingApi.getHistory({ ...filters, limit: 200 })
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

function reset() {
  filters.employe_id = null
  filters.action = ''
  fetchHistory()
}

onMounted(fetchHistory)
defineExpose({ fetchHistory })
</script>
