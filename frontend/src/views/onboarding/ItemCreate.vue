<template>
  <section>
    <h1>Créer un item d'onboarding</h1>
    <div v-if="error" class="alert error">{{ error }}</div>
    <div v-if="message" class="alert success">{{ message }}</div>

    <form class="card" @submit.prevent="handleSubmit">
      <div class="field">
        <label>Ajouter à</label>
        <div class="row">
          <label><input v-model="cible" type="radio" value="modele" /> La checklist type (tous les nouveaux salariés)</label>
          <label><input v-model="cible" type="radio" value="employe" /> Un salarié précis</label>
        </div>
      </div>
      <div v-if="cible === 'employe'" class="field">
        <label for="employe_id">ID employé *</label>
        <input id="employe_id" v-model.number="form.employe_id" type="number" min="1" required />
      </div>
      <div class="field">
        <label for="titre">Titre *</label>
        <input id="titre" v-model.trim="form.titre" maxlength="150" required />
        <small v-if="errors.titre" class="muted">{{ errors.titre }}</small>
      </div>
      <div class="field">
        <label for="description">Description</label>
        <textarea id="description" v-model="form.description" rows="3" />
      </div>
      <div class="row">
        <div class="field">
          <label for="categorie">Catégorie</label>
          <select id="categorie" v-model="form.categorie">
            <option v-for="c in CATEGORIES" :key="c" :value="c">{{ c }}</option>
          </select>
        </div>
        <div v-if="cible === 'modele'" class="field">
          <label for="delai">Délai (jours après l'arrivée)</label>
          <input id="delai" v-model.number="form.delai_jours" type="number" min="0" />
        </div>
        <div v-if="cible === 'modele'" class="field">
          <label for="ordre">Ordre</label>
          <input id="ordre" v-model.number="form.ordre" type="number" />
        </div>
        <div v-else class="field">
          <label for="echeance">Échéance</label>
          <input id="echeance" v-model="form.date_echeance" type="date" />
        </div>
      </div>
      <div class="field">
        <label>Responsable</label>
        <ResponsableAssign v-model="form.responsable_id" />
      </div>
      <div class="field">
        <label><input v-model="form.obligatoire" type="checkbox" /> Obligatoire</label>
      </div>
      <button type="submit" :disabled="submitting">{{ submitting ? 'Enregistrement…' : 'Créer l\'item' }}</button>
    </form>
  </section>
</template>

<script setup>
import { reactive, ref } from 'vue'
import ResponsableAssign from '../../components/onboarding/ResponsableAssign.vue'
import { onboardingApi } from '../../services/onboardingApi'
import { CATEGORIES } from '../../utils/onboarding'

const initial = () => ({
  employe_id: null,
  titre: '',
  description: '',
  categorie: 'general',
  delai_jours: 7,
  ordre: 0,
  date_echeance: '',
  responsable_id: null,
  obligatoire: true,
})

const cible = ref('modele')
const form = reactive(initial())
const submitting = ref(false)
const error = ref('')
const message = ref('')
const errors = ref({})

async function handleSubmit() {
  submitting.value = true
  error.value = ''
  message.value = ''
  errors.value = {}
  try {
    const { employe_id, delai_jours, ordre, date_echeance, ...commun } = form
    if (cible.value === 'modele') {
      await onboardingApi.createChecklistItem({ ...commun, delai_jours, ordre })
      message.value = `« ${form.titre} » ajouté à la checklist type.`
    } else {
      await onboardingApi.createItem({ ...commun, employe_id, date_echeance })
      message.value = `« ${form.titre} » ajouté à l'employé #${employe_id}.`
    }
    Object.assign(form, initial())
  } catch (e) {
    error.value = e.message
    errors.value = e.errors ?? {}
  } finally {
    submitting.value = false
  }
}

defineExpose({ handleSubmit, form, cible })
</script>
