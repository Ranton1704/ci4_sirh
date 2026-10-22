<template>
  <div class="progress-display">
    <ProgressBar :value="progression.pourcentage" />
    <div v-if="!compact" class="muted details">
      {{ progression.termines }}/{{ progression.total }} terminé(s)
      · {{ progression.valides }} validé(s)
      <span v-if="progression.en_retard" class="retard">· {{ progression.en_retard }} en retard</span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import ProgressBar from './ProgressBar.vue'
import { calculateProgress as computeProgress } from '../../utils/onboarding'

const props = defineProps({
  items: { type: Array, default: () => [] },
  compact: { type: Boolean, default: false },
})

function calculateProgress() {
  return computeProgress(props.items)
}

const progression = computed(calculateProgress)

defineExpose({ calculateProgress })
</script>

<style scoped>
.details { margin-top: .25rem; }
.retard { color: #b91c1c; font-weight: 600; }
</style>
