<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { clearAuth, getStoredUser } from '../../api'
import LangSwitch from '../../components/LangSwitch.vue'

const props = defineProps({
  variant: { type: String, default: 'admin' }, // admin | me
})

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const drawerOpen = ref(false)
const user = computed(() => getStoredUser())

const base = computed(() => (props.variant === 'me' ? '/me' : '/admin'))
const brand = computed(() => (props.variant === 'me' ? t('me.brand') : t('admin.brand')))

function logout() {
  drawerOpen.value = false
  clearAuth()
  router.push(props.variant === 'me' ? { name: 'login' } : { name: 'admin-login' })
}

function active(path) {
  if (path === base.value) return route.path === path
  return route.path.startsWith(path)
}

watch(() => route.fullPath, () => { drawerOpen.value = false })
</script>

<template>
  <div class="admin-shell" :class="{ 'drawer-open': drawerOpen }">
    <aside class="side desktop-side">
      <div class="side-brand">{{ brand }}</div>
      <p v-if="user" class="side-user">{{ user.username }}</p>
      <nav class="side-nav">
        <template v-if="variant === 'admin'">
          <RouterLink :class="{ on: active('/admin') && route.path === '/admin' }" to="/admin">
            {{ t('admin.overview') }}
          </RouterLink>
          <RouterLink :class="{ on: active('/admin/links') }" to="/admin/links">
            {{ t('admin.links') }}
          </RouterLink>
          <RouterLink :class="{ on: active('/admin/users') }" to="/admin/users">
            {{ t('admin.users') }}
          </RouterLink>
          <RouterLink :class="{ on: active('/admin/plans') }" to="/admin/plans">
            {{ t('admin.plans') }}
          </RouterLink>
          <RouterLink :class="{ on: active('/admin/guest-limits') }" to="/admin/guest-limits">
            {{ t('admin.guestLimits') }}
          </RouterLink>
        </template>
        <template v-else>
          <RouterLink :class="{ on: active('/me') && route.path === '/me' }" to="/me">
            {{ t('me.overview') }}
          </RouterLink>
          <RouterLink :class="{ on: active('/me/links') }" to="/me/links">
            {{ t('me.links') }}
          </RouterLink>
        </template>
        <a href="/" target="_blank" rel="noopener">{{ t('common.home') }}</a>
      </nav>
      <div class="side-foot">
        <LangSwitch />
        <button type="button" class="logout" @click="logout">{{ t('common.logout') }}</button>
      </div>
    </aside>

    <div class="content">
      <header class="mobile-bar">
        <button type="button" class="menu-btn" :aria-label="t('common.menu')" :aria-expanded="drawerOpen" @click="drawerOpen = !drawerOpen">
          <span class="menu-icon" aria-hidden="true" />
        </button>
        <span class="mobile-brand">{{ brand }}</span>
        <LangSwitch />
      </header>
      <main class="main">
        <router-view />
      </main>
    </div>

    <div class="drawer-root" :aria-hidden="(!drawerOpen).toString()">
      <button type="button" class="drawer-backdrop" :aria-label="t('common.closeMenu')" tabindex="-1" @click="drawerOpen = false" />
      <aside class="side drawer-panel" role="dialog" :aria-modal="drawerOpen" :aria-label="t('common.menu')" @click.stop>
        <div class="drawer-head">
          <div class="side-brand">{{ brand }}</div>
          <button type="button" class="drawer-close" :aria-label="t('common.closeMenu')" @click="drawerOpen = false">×</button>
        </div>
        <nav class="side-nav" @click="drawerOpen = false">
          <template v-if="variant === 'admin'">
            <RouterLink to="/admin">{{ t('admin.overview') }}</RouterLink>
            <RouterLink to="/admin/links">{{ t('admin.links') }}</RouterLink>
            <RouterLink to="/admin/users">{{ t('admin.users') }}</RouterLink>
            <RouterLink to="/admin/plans">{{ t('admin.plans') }}</RouterLink>
            <RouterLink to="/admin/guest-limits">{{ t('admin.guestLimits') }}</RouterLink>
          </template>
          <template v-else>
            <RouterLink to="/me">{{ t('me.overview') }}</RouterLink>
            <RouterLink to="/me/links">{{ t('me.links') }}</RouterLink>
          </template>
          <a href="/" rel="noopener">{{ t('common.home') }}</a>
        </nav>
        <div class="side-foot">
          <button type="button" class="logout" @click="logout">{{ t('common.logout') }}</button>
        </div>
      </aside>
    </div>
  </div>
</template>

<style scoped>
.admin-shell {
  min-height: 100vh;
  display: flex;
  background: #eef3f6;
  color: var(--ink);
}
.side {
  width: 15.5rem;
  background: #0c1b24;
  color: #e8eef2;
  display: flex;
  flex-direction: column;
  padding: 1.25rem 1rem;
}
.desktop-side {
  position: sticky;
  top: 0;
  height: 100vh;
  flex-shrink: 0;
}
.side-brand {
  font-family: Syne, Manrope, sans-serif;
  font-weight: 780;
  font-size: 1.15rem;
  letter-spacing: -0.02em;
}
.side-user {
  margin: 0.35rem 0 0;
  font-size: 0.8rem;
  opacity: 0.7;
}
.side-nav {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  margin-top: 1.25rem;
  flex: 1;
  min-height: 0;
}
.side-nav a {
  color: inherit;
  text-decoration: none;
  display: block;
  padding: 0.5rem 0.7rem;
  line-height: 1.35;
  border-radius: 8px;
  opacity: 0.85;
  flex: none;
}
.side-nav a.on,
.side-nav a.router-link-active {
  background: rgba(255, 255, 255, 0.1);
  opacity: 1;
}
.side-foot {
  display: grid;
  gap: 0.6rem;
  margin-top: auto;
  flex-shrink: 0;
}
.logout {
  padding: 0.5rem 0.7rem;
  border-radius: 8px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  background: transparent;
  color: inherit;
  cursor: pointer;
}
.content {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
}
.main {
  padding: 1.25rem 1.5rem 2rem;
}
.mobile-bar {
  display: none;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  background: #0c1b24;
  color: #e8eef2;
}
.menu-btn {
  width: 2.25rem;
  height: 2.25rem;
  border: 0;
  background: transparent;
  cursor: pointer;
}
.menu-icon,
.menu-icon::before,
.menu-icon::after {
  display: block;
  width: 1.15rem;
  height: 2px;
  background: currentColor;
  position: relative;
}
.menu-icon::before,
.menu-icon::after {
  content: '';
  position: absolute;
  left: 0;
}
.menu-icon::before { top: -6px; }
.menu-icon::after { top: 6px; }
.mobile-brand {
  flex: 1;
  font-family: Syne, Manrope, sans-serif;
  font-weight: 700;
}
.drawer-root {
  display: none;
}
@media (max-width: 860px) {
  .desktop-side { display: none; }
  .mobile-bar { display: flex; }
  .drawer-root {
    display: block;
    position: fixed;
    inset: 0;
    z-index: 40;
    pointer-events: none;
  }
  .drawer-open .drawer-root { pointer-events: auto; }
  .drawer-backdrop {
    position: absolute;
    inset: 0;
    border: 0;
    background: rgba(0, 0, 0, 0.45);
    opacity: 0;
    transition: opacity 0.2s;
  }
  .drawer-open .drawer-backdrop { opacity: 1; }
  .drawer-panel {
    position: absolute;
    top: 0;
    left: 0;
    bottom: 0;
    transform: translateX(-105%);
    transition: transform 0.22s ease;
    z-index: 1;
  }
  .drawer-open .drawer-panel { transform: translateX(0); }
  .drawer-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  .drawer-close {
    border: 0;
    background: transparent;
    color: inherit;
    font-size: 1.5rem;
    cursor: pointer;
  }
}
</style>
