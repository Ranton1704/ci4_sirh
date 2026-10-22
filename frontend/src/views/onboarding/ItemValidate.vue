<template>
  <section>
    <div class="row" style="justify-content: space-between">
      <h1>Validation des items</h1>
      <button class="secondary" type="button" @click="fetchItems">Actualiser</button>
    </div>
    <p class="muted">Items déclarés « terminés », en attente de validation.</p>
    <div v-if="error" class="alert error">{{ error }}</div>
    <div v-if="message" class="alert success">{{ message }}</div>

    <p v-if="loading" class="muted">Chargement…</p>
    <table v-else-if="items.length">
      <thead><tr><th>Salarié</th><th>Item</th><th>Réalisé le</th><th>Commentaire</th><th></th></tr></thead>
      <tbody>
        <tr v-for="item in items" :key="item.id">
          <td><RouterLink :to="`/onboarding/suivi/${item.employe_id}`">#{{ item.employe_id }}</RouterLink></td>
          <td><strong>{{ item.titre }}</strong> <StatusBadge :statut="item.statut" /></td>
          <td>{{ formatDateTime(item.date_realisation) }}</td>
          <td><input v-model="commentaires[item.id]" placeholder="Commentaire (requis pour rejeter)" /></td>
          <td class="row">
            <button class="success" type="button" :disabled="busy === item.id" @click="validateItem(item, true)">Valider</button>
            <button class="danger" type="button" :disabled="busy === item.id || !commentaires[item.id]" @click="validateItem(item, false)">Rejeter</button>
          </td>
        </tr>
      </tbody>
    </table>
    <p v-else class="muted">Aucun item à valider.</p>
  </section>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import StatusBadge from '../../components/onboarding/StatusBadge.vue'
import { onboardingApi } from '../../services/onboardingApi'
import { formatDateTime } from '../../utils/onboarding'

const items = ref([])
const commentaires = reactive({})
const loading = ref(false)
const busy = ref(null)
const error = ref('')
const message = ref('')

async function fetchItems() {
  loading.value = true
  error.value = ''
  try {
    items.value = await onboardingApi.getItems({ statut: 'termine' })
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

async function validateItem(item, approuve = true) {
  busy.value = item.id
  error.value = ''
  message.value = ''
  try {
    await onboardingApi.validateItem(item.id, approuve, commentaires[item.id] || null)
    message.value = `« ${item.titre} » ${approuve ? 'validé' : 'rejeté'}.`
    items.value = items.value.filter((i) => i.id !== item.id)
  } catch (e) {
    error.value = e.message
  } finally {
    busy.value = null
  }
}

onMounted(fetchItems)
defineExpose({ validateItem, fetchItems })
</script>
