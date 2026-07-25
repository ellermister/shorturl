<script setup>
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { api } from '../../api'

const { t } = useI18n()
const cfg = ref(null)
const raw = ref('')
const error = ref('')
const msg = ref('')
const saving = ref(false)

async function load() {
  error.value = ''
  try {
    cfg.value = await api.adminGetPlans()
    raw.value = JSON.stringify(cfg.value, null, 2)
  } catch (e) {
    error.value = e.message
  }
}

async function save() {
  saving.value = true
  error.value = ''
  msg.value = ''
  try {
    const parsed = JSON.parse(raw.value)
    cfg.value = await api.adminSavePlans(parsed)
    raw.value = JSON.stringify(cfg.value, null, 2)
    msg.value = t('admin.plansSaved')
  } catch (e) {
    error.value = e.message || t('admin.plansInvalid')
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="plans">
    <h1>{{ t('admin.plans') }}</h1>
    <p class="lead">{{ t('admin.plansLead') }}</p>
    <p v-if="error" class="err">{{ error }}</p>
    <p v-if="msg" class="ok">{{ msg }}</p>
    <textarea v-model="raw" rows="22" spellcheck="false" />
    <div class="actions">
      <button type="button" :disabled="saving" @click="save">
        {{ saving ? t('common.loading') : t('admin.savePlans') }}
      </button>
      <button type="button" class="ghost" @click="load">{{ t('admin.reload') }}</button>
    </div>
  </div>
</template>

<style scoped>
.plans h1 { margin: 0 0 0.5rem; font-family: Syne, Manrope, sans-serif; }
.lead { margin: 0 0 1rem; opacity: 0.8; max-width: 40rem; }
textarea {
  width: 100%;
  max-width: 48rem;
  font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
  font-size: 0.85rem;
  padding: 0.85rem;
  border: 1px solid var(--line);
  border-radius: 12px;
  background: #fff;
  resize: vertical;
}
.actions { display: flex; gap: 0.5rem; margin-top: 0.75rem; }
button {
  padding: 0.55rem 0.9rem;
  border-radius: 10px;
  border: 0;
  background: #0f766e;
  color: #fff;
  font-weight: 650;
  cursor: pointer;
}
button.ghost {
  background: #fff;
  color: var(--ink);
  border: 1px solid var(--line);
}
.err { color: #b91c1c; }
.ok { color: #0f766e; }
</style>
