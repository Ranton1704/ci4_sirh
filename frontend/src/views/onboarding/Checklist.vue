<template>
  <section>
    <div class="row" style="justify-content: space-between">
      <h1>Checklist d'onboarding</h1>
      <RouterLink to="/onboarding/items/nouveau"><button type="button">+ Ajouter un item</button></RouterLink>
    </div>
    <div v-if="error" class="alert error">{{ error }}</div>
    <div v-if="message" class="alert success">{{ message }}</div>

    <form class="card row" @submit.prevent="link">
      <strong>Lier la checklist à un salarié :</strong>
      <input v-model.number="employeId" type="number" min="1" placeholder="ID employé" required />
      <input v-model="dateDebut" type="date" title="Date d'arrivée" />
      <button type="submit" :disabled="linking || !actifs.length">Lier</button>
    </form>

    <p v-if="loading" class="muted">Chargement…</p>
    <table v-else-if="items.length">
      <thead>
        <tr><th>#</th><th>Item</th><th>Catégorie</th><th>Délai</th><th>Obligatoire</th><th>Actif</th><th></th></tr>
      </thead>
      <tbody>
        <tr v-for="item in items" :key="item.id" :style="{ opacity: item.actif ? 1 : .5 }">
          <td>{{ item.ordre }}</td>
          <td><strong>{{ item.titre }}</strong><div v-if="item.description" class="muted">{{ item.description }}</div></td>
          <td>{{ item.categorie }}</td>
          <td>J+{{ item.delai_jours }}</td>
          <td>{{ item.obligatoire ? 'Oui' : 'Non' }}</td>
          <td><input type="checkbox" :checked="item.actif" @change="toggleActif(item)" /></td>
          <td><button class="danger" type="button" @click="remove(item)">Supprimer</button></td>
        </tr>
      </tbody>
    </table>
    <p v-else class="muted">La checklist est vide.</p>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { onboardingApi } from '../../services/onboardingApi'

const router = useRouter()
const items = ref([])
const loading = ref(false)
const linking = ref(false)
const error = ref('')
const message = ref('')
const employeId = ref(null)
const dateDebut = ref(new Date().toISOString().slice(0, 10))

const actifs = computed(() => items.value.filter((i) => i.actif))

async function fetchChecklist() {
  loading.value = true
  error.value = ''
  try {
    items.value = await onboardingApi.getChecklist()
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

async function toggleActif(item) {
  try {
    Object.assign(item, await onboardingApi.updateChecklistItem(item.id, { actif: !item.actif }))
  } catch (e) {
    error.value = e.message
  }
}

async function remove(item) {
  if (!confirm(`Supprimer « ${item.titre} » de la checklist ?`)) return
  try {
    await onboardingApi.deleteChecklistItem(item.id)
    await fetchChecklist()
  } catch (e) {
    error.value = e.message
  }
}

async function link() {
  linking.value = true
  error.value = ''
  message.value = ''
  try {
    const res = await onboardingApi.linkToEmploye(employeId.value, { date_debut: dateDebut.value })
    message.value = `${res.crees} item(s) lié(s) à l'employé #${employeId.value}.`
    if (res.crees > 0) router.push(`/onboarding/suivi/${employeId.value}`)
  } catch (e) {
    error.value = e.message
  } finally {
    linking.value = false
  }
}

onMounted(fetchChecklist)
defineExpose({ fetchChecklist })
</script>
