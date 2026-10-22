<template>
  <section>
    <div class="row" style="justify-content: space-between">
      <h1>Tableau de bord onboarding</h1>
      <button class="secondary" type="button" @click="fetchDashboard">Actualiser</button>
    </div>
    <div v-if="error" class="alert error">{{ error }}</div>
    <p v-if="loading" class="muted">Chargement…</p>

    <template v-if="dashboard">
      <div class="grid">
        <div class="card"><div class="muted">Salariés en onboarding</div><div class="stat">{{ dashboard.nb_employes }}</div></div>
        <div class="card"><div class="muted">Onboardings terminés</div><div class="stat">{{ dashboard.nb_employes_termine }}</div></div>
        <div class="card"><div class="muted">Items au total</div><div class="stat">{{ dashboard.nb_items }}</div></div>
        <div class="card"><div class="muted">Items en retard</div><div class="stat" :style="{ color: dashboard.items_en_retard ? '#b91c1c' : undefined }">{{ dashboard.items_en_retard }}</div></div>
      </div>

      <div class="card">
        <h2 style="margin-top: 0">Progression moyenne</h2>
        <ProgressBar :value="dashboard.progression_moyenne" />
        <div class="row" style="margin-top: .8rem">
          <span v-for="(nb, statut) in dashboard.items_par_statut" :key="statut" class="row">
            <StatusBadge :statut="statut" /> {{ nb }}
          </span>
        </div>
      </div>

      <h2>Salariés</h2>
      <table v-if="dashboard.employes.length">
        <thead><tr><th>Salarié</th><th>Progression</th><th>Items</th><th>En retard</th><th></th></tr></thead>
        <tbody>
          <tr v-for="e in dashboard.employes" :key="e.employe_id">
            <td>Employé #{{ e.employe_id }}</td>
            <td><ProgressBar :value="e.progression.pourcentage" /></td>
            <td>{{ e.progression.termines }}/{{ e.progression.total }}</td>
            <td>{{ e.progression.en_retard }}</td>
            <td><RouterLink :to="`/onboarding/suivi/${e.employe_id}`">Détail</RouterLink></td>
          </tr>
        </tbody>
      </table>
      <p v-else class="muted">Aucun salarié en onboarding. Liez la checklist à un salarié depuis la page Checklist.</p>

      <h2>Dernières actions</h2>
      <ul class="card" style="list-style: none; padding-left: 1.25rem">
        <li v-for="h in dashboard.historique_recent" :key="h.id">
          <span class="muted">{{ formatDateTime(h.date_action) }}</span> — <strong>{{ h.action }}</strong> {{ h.details }}
        </li>
        <li v-if="!dashboard.historique_recent.length" class="muted">Aucune action.</li>
      </ul>
    </template>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import ProgressBar from '../../components/onboarding/ProgressBar.vue'
import StatusBadge from '../../components/onboarding/StatusBadge.vue'
import { onboardingApi } from '../../services/onboardingApi'
import { formatDateTime } from '../../utils/onboarding'

const dashboard = ref(null)
const loading = ref(false)
const error = ref('')

async function fetchDashboard() {
  loading.value = true
  error.value = ''
  try {
    dashboard.value = await onboardingApi.getDashboard()
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

onMounted(fetchDashboard)
defineExpose({ fetchDashboard })
</script>
