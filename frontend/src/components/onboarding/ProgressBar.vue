<template>
  <div class="progress" role="progressbar" :aria-valuenow="pct" aria-valuemin="0" aria-valuemax="100">
    <div class="progress-track">
      <div class="progress-fill" :class="color" :style="{ width: pct + '%' }" />
    </div>
    <span v-if="showLabel" class="progress-label">{{ label ?? pct + '%' }}</span>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  value: { type: Number, default: 0 },
  label: { type: String, default: null },
  showLabel: { type: Boolean, default: true },
})

const pct = computed(() => Math.min(100, Math.max(0, Math.round(Number(props.value) || 0))))
const color = computed(() => (pct.value >= 100 ? 'green' : pct.value >= 50 ? 'blue' : pct.value > 0 ? 'orange' : 'grey'))
</script>

<style scoped>
.progress { display: flex; align-items: center; gap: .6rem; min-width: 140px; }
.progress-track { flex: 1; height: 10px; background: #e5e7eb; border-radius: 999px; overflow: hidden; }
.progress-fill { height: 100%; transition: width .3s ease; }
.progress-fill.green { background: #16a34a; }
.progress-fill.blue { background: #2563eb; }
.progress-fill.orange { background: #f59e0b; }
.progress-fill.grey { background: #9ca3af; }
.progress-label { font-size: .85rem; font-weight: 600; min-width: 3rem; text-align: right; }
</style>
