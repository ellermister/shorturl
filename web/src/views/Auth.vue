<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { api, setAuth } from '../api'
import LangSwitch from '../components/LangSwitch.vue'

const props = defineProps({
  mode: { type: String, default: 'login' }, // login | register
})

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const username = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

async function submit() {
  error.value = ''
  loading.value = true
  try {
    const data = props.mode === 'register'
      ? await api.register(username.value, password.value)
      : await api.login(username.value, password.value)
    setAuth(data)
    const redirect = route.query.redirect
    if (redirect) {
      router.replace(String(redirect))
      return
    }
    if (data.user?.role === 'admin') router.replace('/admin')
    else router.replace('/me')
  } catch (e) {
    error.value = e.message || t('auth.fail')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="page-center">
    <div class="lang-wrap"><LangSwitch /></div>
    <form class="page-panel login" @submit.prevent="submit">
      <h1>{{ mode === 'register' ? t('auth.registerTitle') : t('auth.loginTitle') }}</h1>
      <label>
        <span>{{ t('auth.username') }}</span>
        <input v-model="username" autocomplete="username" required minlength="3" />
      </label>
      <label>
        <span>{{ t('auth.password') }}</span>
        <input v-model="password" type="password" autocomplete="current-password" required minlength="6" />
      </label>
      <p v-if="error" class="err">{{ error }}</p>
      <button type="submit" :disabled="loading">
        {{ loading ? t('common.loading') : (mode === 'register' ? t('auth.register') : t('auth.login')) }}
      </button>
      <p class="switch">
        <template v-if="mode === 'login'">
          {{ t('auth.noAccount') }}
          <RouterLink to="/register">{{ t('auth.goRegister') }}</RouterLink>
        </template>
        <template v-else>
          {{ t('auth.hasAccount') }}
          <RouterLink to="/login">{{ t('auth.goLogin') }}</RouterLink>
        </template>
      </p>
      <RouterLink to="/">{{ t('admin.backFront') }}</RouterLink>
    </form>
  </div>
</template>

<style scoped>
.lang-wrap { position: absolute; top: 1rem; right: 1.25rem; }
.page-center { position: relative; }
.login { display: grid; gap: 0.75rem; }
.login h1 { line-height: 1.25; }
.login label { display: grid; gap: 0.3rem; }
.login span { font-size: 0.85rem; font-weight: 650; }
.login input {
  padding: 0.55rem 0.7rem;
  border: 1px solid var(--line);
  border-radius: 10px;
  font: inherit;
}
.login button {
  margin-top: 0.25rem;
  padding: 0.65rem 1rem;
  border: 0;
  border-radius: 10px;
  background: #0f766e;
  color: #fff;
  font-weight: 650;
  cursor: pointer;
}
.err { color: #b91c1c; margin: 0; }
.switch { margin: 0; font-size: 0.9rem; }
</style>
