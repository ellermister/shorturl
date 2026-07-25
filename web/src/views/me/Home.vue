<script setup>
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { api } from '../../api'

const { t } = useI18n()
const profile = ref(null)
const error = ref('')

onMounted(async () => {
  try {
    profile.value = await api.me()
    if (profile.value?.user) localStorage.setItem('auth_user', JSON.stringify(profile.value.user))
    if (profile.value?.plan) localStorage.setItem('auth_plan', JSON.stringify(profile.value.plan))
  } catch (e) {
    error.value = e.message
  }
})
</script>

<template>
  <div class="me-home">
    <h1>{{ t('me.overview') }}</h1>
    <p v-if="error" class="err">{{ error }}</p>
    <div v-if="profile" class="card">
      <p><b>{{ t('me.username') }}</b> {{ profile.user?.username }}</p>
      <p><b>{{ t('me.plan') }}</b> {{ profile.plan?.name || profile.user?.plan_id }}</p>
      <p>
        <b>{{ t('me.quota') }}</b>
        {{ profile.links_used }}
        /
        {{ profile.plan?.features?.max_links === -1 ? t('me.unlimited') : profile.plan?.features?.max_links }}
      </p>
      <ul class="feats">
        <li>{{ t('me.featCustomCode') }}：{{ profile.plan?.features?.custom_code ? '✓' : '—' }}</li>
        <li>{{ t('me.featEditTarget') }}：{{ profile.plan?.features?.edit_target ? '✓' : '—' }}</li>
        <li>
          {{ t('me.featExpire') }}：
          {{ profile.plan?.features?.max_expire_days }}{{ t('me.days') }}
          <span v-if="profile.plan?.features?.allow_never_expire"> / {{ t('me.neverExpire') }}</span>
        </li>
      </ul>
      <RouterLink class="btn" to="/me/links">{{ t('me.manageLinks') }}</RouterLink>
    </div>
  </div>
</template>

<style scoped>
.me-home h1 {
  margin: 0 0 1rem;
  font-family: Syne, Manrope, sans-serif;
}
.card {
  display: grid;
  gap: 0.55rem;
  max-width: 28rem;
  padding: 1.15rem;
  border-radius: 14px;
  border: 1px solid var(--line);
  background: #fff;
}
.feats {
  margin: 0.35rem 0;
  padding-left: 1.1rem;
  opacity: 0.9;
}
.btn {
  margin-top: 0.5rem;
  width: fit-content;
  padding: 0.5rem 0.9rem;
  border-radius: 10px;
  background: #0f766e;
  color: #fff;
  text-decoration: none;
  font-weight: 650;
}
.err { color: #b91c1c; }
</style>
