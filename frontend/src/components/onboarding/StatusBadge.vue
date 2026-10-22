<template>
  <span class="badge" :class="info.color">{{ info.label }}</span>
</template>

<script setup>
import { computed } from 'vue'
import { STATUTS } from '../../utils/onboarding'

const props = defineProps({
  statut: { type: String, required: true },
})

const info = computed(() => STATUTS[props.statut] ?? { label: props.statut, color: 'grey' })
</script>
