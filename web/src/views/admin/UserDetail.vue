<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { api } from '../../api'

const { t } = useI18n()
const route = useRoute()

const user = ref(null)
const plan = ref(null)
const plans = ref([])
const links = ref([])
const linkTotal = ref(0)
const page = ref(1)
const error = ref('')
const loading = ref(false)

const userId = computed(() => route.params.id)

async function loadUser() {
  const data = await api.adminUser(userId.value)
  user.value = data.user
  plan.value = data.plan || null
}

async function loadLinks() {
  const data = await api.adminLinks({ page: page.value, pageSize: 20, userId: userId.value })
  links.value = data.items || []
  linkTotal.value = data.total || 0
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const [, planCfg] = await Promise.all([
      loadUser().then(() => loadLinks()),
      api.adminGetPlans(),
    ])
    plans.value = planCfg.plans || []
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

async function setPlan(planId) {
  await api.adminSetUserPlan(user.value.id, planId)
  user.value.plan_id = planId
  plan.value = plans.value.find((p) => p.id === planId) || plan.value
}

async function toggleStatus() {
  const next = user.value.status === 1 ? 0 : 1
  await api.adminSetUserStatus(user.value.id, next)
  user.value.status = next
}

function statusText(s) {
  return { 1: t('links.statusActive'), 0: t('links.statusDisabled'), 2: t('links.statusBurned') }[s] || String(s)
}

const pages = () => Math.max(1, Math.ceil(linkTotal.value / 20))

onMounted(load)
watch(userId, () => { page.value = 1; load() })
watch(page, async () => {
  try {
    await loadLinks()
  } catch (e) {
    error.value = e.message
  }
})
</script>

<template>
  <div class="detail">
    <div class="head">
      <h1>{{ t('admin.userDetail') }}</h1>
      <RouterLink to="/admin/users">{{ t('links.back') }}</RouterLink>
    </div>
    <p v-if="error" class="err">{{ error }}</p>
    <p v-if="loading && !user">{{ t('common.loading') }}</p>

    <div v-if="user" class="meta">
      <p><b>ID</b> {{ user.id }}</p>
      <p><b>{{ t('auth.username') }}</b> {{ user.username }}</p>
      <p><b>{{ t('admin.role') }}</b> {{ user.role }}</p>
      <p>
        <b>{{ t('me.plan') }}</b>
        <select :value="user.plan_id" @change="setPlan($event.target.value)">
          <option v-for="p in plans" :key="p.id" :value="p.id">{{ p.name }}</option>
        </select>
      </p>
      <p>
        <b>{{ t('links.status') }}</b>
        {{ user.status === 1 ? t('admin.active') : t('admin.disabled') }}
        <button type="button" @click="toggleStatus">
          {{ user.status === 1 ? t('admin.disable') : t('admin.enable') }}
        </button>
      </p>
      <p>
        <b>{{ t('links.created') }}</b>
        {{ user.created_at ? new Date(user.created_at).toLocaleString() : '-' }}
      </p>
    </div>

    <h2>{{ t('admin.userLinks') }}（{{ linkTotal }}）</h2>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Code</th>
            <th>{{ t('links.target') }}</th>
            <th>{{ t('links.visits') }}</th>
            <th>{{ t('links.status') }}</th>
            <th />
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in links" :key="item.id">
            <td>{{ item.id }}</td>
            <td><code>{{ item.code }}</code></td>
            <td class="url" :title="item.target_url">{{ item.target_url }}</td>
            <td>{{ item.visit_count }}</td>
            <td>{{ statusText(item.status) }}</td>
            <td>
              <RouterLink :to="`/admin/links/${item.id}`">{{ t('links.detail') }}</RouterLink>
            </td>
          </tr>
          <tr v-if="!links.length">
            <td colspan="6">{{ t('admin.noLinks') }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="pager">
      <button type="button" :disabled="page <= 1" @click="page--">{{ t('links.prev') }}</button>
      <span>{{ page }} / {{ pages() }}</span>
      <button type="button" :disabled="page >= pages()" @click="page++">{{ t('links.next') }}</button>
    </div>
  </div>
</template>

<style scoped>
.detail h1 { margin: 0; font-family: Syne, Manrope, sans-serif; }
.head {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}
.meta {
  display: grid;
  gap: 0.55rem;
  padding: 1rem;
  border: 1px solid var(--line);
  border-radius: 12px;
  background: #fff;
  margin-bottom: 1.25rem;
}
.meta select, .meta button, .pager button {
  margin-left: 0.5rem;
  padding: 0.35rem 0.65rem;
  border-radius: 8px;
  border: 1px solid var(--line);
  background: #fff;
  cursor: pointer;
}
h2 { font-size: 1.05rem; margin: 0 0 0.6rem; }
.table-wrap {
  overflow-x: auto;
  border: 1px solid var(--line);
  border-radius: 12px;
  background: #fff;
}
table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
th, td { padding: 0.55rem 0.65rem; text-align: left; border-bottom: 1px solid var(--line); }
.url {
  max-width: 16rem;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.pager {
  display: flex;
  gap: 0.75rem;
  align-items: center;
  margin-top: 0.85rem;
}
.err { color: #b91c1c; }
</style>
