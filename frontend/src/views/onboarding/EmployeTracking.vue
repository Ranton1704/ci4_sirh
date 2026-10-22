<template>
  <section>
    <h1>Suivi par salarié</h1>
    <div v-if="error" class="alert error">{{ error }}</div>

    <div class="card row">
      <label for="employe">Salarié :</label>
      <select id="employe" :value="employeId ?? ''" @change="choisir($event.target.value)">
        <option value="">— Tous les salariés —</option>
        <option v-for="s in suivi" :key="s.employe_id" :value="String(s.employe_id)">Employé #{{ s.employe_id }}</option>
      </select>
      <button class="secondary" type="button" @click="fetchTracking">Actualiser</button>
    </div>

    <p v-if="loading" class="muted">Chargement…</p>

    <template v-else-if="!employeId">
      <table v-if="suivi.length">
        <thead><tr><th>Salarié</th><th>Progression</th><th></th></tr></thead>
        <tbody>
          <tr v-for="s in suivi" :key="s.employe_id">
            <td>Employé #{{ s.employe_id }}</td>
            <td><ProgressDisplay :items="s.items" /></td>
            <td><RouterLink :to="`/onboarding/suivi/${s.employe_id}`">Voir la checklist</RouterLink></td>
          </tr>
        </tbody>
      </table>
      <p v-else class="muted">Aucun salarié suivi.</p>
    </template>

    <template v-else-if="detail">
      <div class="card">
        <h2 style="margin-top: 0">Employé #{{ detail.employe_id }}</h2>
        <ProgressDisplay :items="detail.items" />
      </div>
      <table v-if="detail.items.length">
        <thead><tr><th>Item</th><th>Échéance</th><th>Statut</th><th>Changer le statut</th><th>Responsable</th></tr></thead>
        <tbody>
          <tr v-for="item in detail.items" :key="item.id">
            <td><strong>{{ item.titre }}</strong><div class="muted">{{ item.categorie }}</div>
              <div v-if="item.commentaire" class="muted">Commentaire : {{ item.commentaire }}</div></td>
            <td>{{ formatDate(item.date_echeance) }} <StatusBadge v-if="isEnRetard(item)" statut="en_retard" /></td>
            <td><StatusBadge :statut="item.statut" /></td>
            <td>
              <select :value="item.statut" :disabled="item.statut === 'valide'" @change="changerStatut(item, $event.target.value)">
                <option value="a_faire">À faire</option>
                <option value="en_cours">En cours</option>
                <option value="termine">Terminé</option>
                <option value="valide" disabled>Validé</option>
              </select>
            </td>
            <td><ResponsableAssign :item-id="item.id" :model-value="item.responsable_id" @assigned="fetchTracking" /></td>
          </tr>
        </tbody>
      </table>
      <p v-else class="muted">Aucun item pour ce salarié.</p>
    </template>
  </section>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import ProgressDisplay from '../../components/onboarding/ProgressDisplay.vue'
import ResponsableAssign from '../../components/onboarding/ResponsableAssign.vue'
import StatusBadge from '../../components/onboarding/StatusBadge.vue'
import { onboardingApi } from '../../services/onboardingApi'
import { formatDate, isEnRetard } from '../../utils/onboarding'

const props = defineProps({
  employeId: { type: String, default: null },
})

const router = useRouter()
const suivi = ref([])
const detail = ref(null)
const loading = ref(false)
const error = ref('')

async function fetchTracking() {
  loading.value = true
  error.value = ''
  try {
    const [tous, unSeul] = await Promise.all([
      onboardingApi.getTracking(),
      props.employeId ? onboardingApi.getEmployeTracking(props.employeId) : Promise.resolve(null),
    ])
    suivi.value = tous
    detail.value = unSeul
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

function choisir(id) {
  router.push(id ? `/onboarding/suivi/${id}` : '/onboarding/suivi')
}

async function changerStatut(item, statut) {
  try {
    await onboardingApi.updateItem(item.id, { statut })
    await fetchTracking()
  } catch (e) {
    error.value = e.message
  }
}

watch(() => props.employeId, fetchTracking)
onMounted(fetchTracking)
defineExpose({ fetchTracking })
</script>
