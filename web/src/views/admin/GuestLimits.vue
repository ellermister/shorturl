<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { api } from '../../api'

const { t } = useI18n()
const form = reactive({
  max_create_per_ip_24h: 10,
  max_active_per_ip: 10,
})
const error = ref('')
const msg = ref('')
const saving = ref(false)

async function load() {
  error.value = ''
  try {
    const data = await api.adminGetGuestLimits()
    form.max_create_per_ip_24h = data.max_create_per_ip_24h ?? 10
    form.max_active_per_ip = data.max_active_per_ip ?? 10
  } catch (e) {
    error.value = e.message
  }
}

async function save() {
  saving.value = true
  error.value = ''
  msg.value = ''
  try {
    const data = await api.adminSaveGuestLimits({
      max_create_per_ip_24h: Number(form.max_create_per_ip_24h) || 0,
      max_active_per_ip: Number(form.max_active_per_ip) || 0,
    })
    form.max_create_per_ip_24h = data.max_create_per_ip_24h
    form.max_active_per_ip = data.max_active_per_ip
    msg.value = t('admin.guestSaved')
  } catch (e) {
    error.value = e.message
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="guest">
    <h1>{{ t('admin.guestLimits') }}</h1>
    <p class="lead">{{ t('admin.guestLead') }}</p>
    <p v-if="error" class="err">{{ error }}</p>
    <p v-if="msg" class="ok">{{ msg }}</p>
    <form class="form" @submit.prevent="save">
      <label>
        <span>{{ t('admin.guestCreate24h') }}</span>
        <input v-model.number="form.max_create_per_ip_24h" type="number" min="0" step="1" />
      </label>
      <label>
        <span>{{ t('admin.guestActive') }}</span>
        <input v-model.number="form.max_active_per_ip" type="number" min="0" step="1" />
      </label>
      <p class="hint">{{ t('admin.guestZeroHint') }}</p>
      <div class="actions">
        <button type="submit" :disabled="saving">
          {{ saving ? t('common.loading') : t('admin.saveGuest') }}
        </button>
        <button type="button" class="ghost" @click="load">{{ t('admin.reload') }}</button>
      </div>
    </form>
  </div>
</template>

<style scoped>
.guest h1 { margin: 0 0 0.5rem; font-family: Syne, Manrope, sans-serif; }
.lead { margin: 0 0 1rem; opacity: 0.8; max-width: 40rem; }
.form {
  display: grid;
  gap: 0.85rem;
  max-width: 28rem;
  padding: 1rem;
  border: 1px solid var(--line);
  border-radius: 12px;
  background: #fff;
}
label {
  display: grid;
  gap: 0.35rem;
  font-weight: 650;
  font-size: 0.9rem;
}
input {
  padding: 0.5rem 0.65rem;
  border: 1px solid var(--line);
  border-radius: 8px;
}
.hint { margin: 0; font-size: 0.85rem; opacity: 0.7; font-weight: 400; }
.actions { display: flex; gap: 0.5rem; }
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
