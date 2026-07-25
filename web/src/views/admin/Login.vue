<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { api, setAuth } from '../../api'
import LangSwitch from '../../components/LangSwitch.vue'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const username = ref('admin')
const password = ref('')
const error = ref('')
const loading = ref(false)

async function login() {
  error.value = ''
  loading.value = true
  try {
    const data = await api.adminLogin(username.value, password.value)
    setAuth(data)
    router.replace(route.query.redirect || '/admin')
  } catch (e) {
    error.value = e.message || t('admin.loginFail')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  if (localStorage.getItem('auth_token') || localStorage.getItem('admin_token')) {
    // stay on login unless already admin session — user can re-login
  }
})
</script>

<template>
  <div class="page-center">
    <div class="lang-wrap">
      <LangSwitch />
    </div>
    <form class="page-panel login" @submit.prevent="login">
      <h1>{{ t('admin.loginTitle') }}</h1>
      <label>
        <span>{{ t('admin.username') }}</span>
        <input v-model="username" autocomplete="username" />
      </label>
      <label>
        <span>{{ t('admin.password') }}</span>
        <input v-model="password" type="password" autocomplete="current-password" />
      </label>
      <p v-if="error" class="err">{{ error }}</p>
      <button type="submit" :disabled="loading">
        {{ loading ? t('admin.loggingIn') : t('admin.login') }}
      </button>
      <RouterLink to="/">{{ t('admin.backFront') }}</RouterLink>
    </form>
  </div>
</template>

<style scoped>
.lang-wrap {
  position: absolute;
  top: 1rem;
  right: 1.25rem;
}
.page-center {
  position: relative;
}
.login {
  display: grid;
  gap: 0.75rem;
}
.login h1 {
  line-height: 1.25;
}
.login label {
  display: grid;
  gap: 0.3rem;
}
.login span {
  font-size: 0.85rem;
  font-weight: 650;
  color: var(--ink);
}
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
.login button:disabled {
  opacity: 0.6;
}
.err {
  color: #b91c1c;
  margin: 0;
}
</style>
