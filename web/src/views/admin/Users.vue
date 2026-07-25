<script setup>
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { api } from '../../api'

const { t } = useI18n()
const items = ref([])
const plans = ref([])
const total = ref(0)
const page = ref(1)
const keyword = ref('')
const error = ref('')
const loading = ref(false)

async function load() {
  loading.value = true
  error.value = ''
  try {
    const [users, planCfg] = await Promise.all([
      api.adminUsers({ page: page.value, pageSize: 20, keyword: keyword.value }),
      api.adminGetPlans(),
    ])
    items.value = users.items || []
    total.value = users.total || 0
    plans.value = planCfg.plans || []
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

async function setPlan(user, planId) {
  await api.adminSetUserPlan(user.id, planId)
  user.plan_id = planId
}

async function toggleStatus(user) {
  const next = user.status === 1 ? 0 : 1
  await api.adminSetUserStatus(user.id, next)
  user.status = next
}

onMounted(load)
</script>

<template>
  <div class="users">
    <div class="head">
      <h1>{{ t('admin.users') }}</h1>
      <div class="search">
        <input v-model="keyword" :placeholder="t('admin.userSearch')" @keyup.enter="page = 1; load()" />
        <button type="button" @click="page = 1; load()">{{ t('links.search') }}</button>
      </div>
    </div>
    <p v-if="error" class="err">{{ error }}</p>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>{{ t('auth.username') }}</th>
            <th>{{ t('admin.role') }}</th>
            <th>{{ t('me.plan') }}</th>
            <th>{{ t('links.status') }}</th>
            <th />
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading"><td colspan="6">{{ t('common.loading') }}</td></tr>
          <tr v-for="u in items" :key="u.id">
            <td>{{ u.id }}</td>
            <td>{{ u.username }}</td>
            <td>{{ u.role }}</td>
            <td>
              <select :value="u.plan_id" @change="setPlan(u, $event.target.value)">
                <option v-for="p in plans" :key="p.id" :value="p.id">{{ p.name }}</option>
              </select>
            </td>
            <td>{{ u.status === 1 ? t('admin.active') : t('admin.disabled') }}</td>
            <td class="actions">
              <RouterLink :to="`/admin/users/${u.id}`">{{ t('links.detail') }}</RouterLink>
              <button type="button" @click="toggleStatus(u)">
                {{ u.status === 1 ? t('admin.disable') : t('admin.enable') }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <p class="hint">{{ t('admin.usersHint') }} · {{ total }}</p>
  </div>
</template>

<style scoped>
.users h1 { margin: 0; font-family: Syne, Manrope, sans-serif; }
.head {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}
.search { display: flex; gap: 0.4rem; }
.search input, select, button {
  padding: 0.45rem 0.65rem;
  border: 1px solid var(--line);
  border-radius: 8px;
  background: #fff;
}
.table-wrap {
  overflow-x: auto;
  border: 1px solid var(--line);
  border-radius: 12px;
  background: #fff;
}
table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
th, td { padding: 0.65rem 0.75rem; text-align: left; border-bottom: 1px solid var(--line); }
.actions { display: flex; gap: 0.5rem; align-items: center; white-space: nowrap; }
.err { color: #b91c1c; }
.hint { opacity: 0.7; font-size: 0.85rem; }
</style>
