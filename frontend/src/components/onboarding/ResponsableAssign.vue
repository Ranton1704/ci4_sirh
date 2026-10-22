<template>
  <div class="row responsable-assign">
    <select v-model="selected" :disabled="loading || saving" aria-label="Responsable">
      <option :value="null">— Aucun —</option>
      <option v-for="u in utilisateurs" :key="u.id" :value="Number(u.id)">
        {{ u.email }}<template v-if="u.role"> ({{ u.role }})</template>
      </option>
    </select>
    <button v-if="itemId" type="button" :disabled="!canAssign" @click="assign">
      {{ saving ? '…' : 'Assigner' }}
    </button>
    <span v-if="error" class="error">{{ error }}</span>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { onboardingApi } from '../../services/onboardingApi'

const props = defineProps({
  itemId: { type: Number, default: null },
  modelValue: { type: Number, default: null },
})
const emit = defineEmits(['update:modelValue', 'assigned'])

const utilisateurs = ref([])
const selected = ref(props.modelValue)
const loading = ref(false)
const saving = ref(false)
const error = ref('')

const canAssign = computed(() => !saving.value && selected.value !== null && selected.value !== props.modelValue)

watch(() => props.modelValue, (v) => { selected.value = v })
watch(selected, (v) => { if (!props.itemId) emit('update:modelValue', v) })

onMounted(async () => {
  loading.value = true
  try {
    utilisateurs.value = await onboardingApi.getUtilisateurs()
  } catch (e) {
    error.value = 'Utilisateurs indisponibles'
  } finally {
    loading.value = false
  }
})

async function assign() {
  if (!canAssign.value) return
  saving.value = true
  error.value = ''
  try {
    const item = await onboardingApi.assignResponsable(props.itemId, selected.value)
    emit('update:modelValue', selected.value)
    emit('assigned', item)
  } catch (e) {
    error.value = e.message
  } finally {
    saving.value = false
  }
}

defineExpose({ assign })
</script>

<style scoped>
.error { color: #b91c1c; font-size: .85rem; }
select { min-width: 180px; }
</style>
