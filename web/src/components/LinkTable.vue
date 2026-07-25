<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { api } from '../api'

const props = defineProps({
  mode: { type: String, default: 'admin' }, // admin | me
})

const { t } = useI18n()
const items = ref([])
const total = ref(0)
const page = ref(1)
const pageSize = 20
const keyword = ref('')
const loading = ref(false)
const error = ref('')

const detailBase = computed(() => (props.mode === 'me' ? '/me/links' : '/admin/links'))

async function load() {
  loading.value = true
  error.value = ''
  try {
    const fn = props.mode === 'me' ? api.meLinks : api.adminLinks
    const data = await fn({ page: page.value, pageSize, keyword: keyword.value })
    items.value = data.items || []
    total.value = data.total || 0
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

async function remove(id) {
  if (!confirm(t('links.confirmDelete'))) return
  if (props.mode === 'me') await api.meDeleteLink(id)
  else await api.adminDeleteLink(id)
  await load()
}

function statusText(s) {
  return { 1: t('links.statusActive'), 0: t('links.statusDisabled'), 2: t('links.statusBurned') }[s] || String(s)
}

onMounted(load)
watch(page, load)
watch(() => props.mode, () => { page.value = 1; load() })

const pages = () => Math.max(1, Math.ceil(total.value / pageSize))
</script>

<template>
  <div class="links">
    <div class="head">
      <h1>{{ t('links.title') }}</h1>
      <div class="search">
        <input v-model="keyword" :placeholder="t('links.searchPh')" @keyup.enter="page = 1; load()" />
        <button type="button" @click="page = 1; load()">{{ t('links.search') }}</button>
      </div>
    </div>
    <p v-if="error" class="err">{{ error }}</p>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Code</th>
            <th v-if="mode === 'admin'">{{ t('links.owner') }}</th>
            <th>{{ t('links.target') }}</th>
            <th>{{ t('links.visits') }}</th>
            <th>{{ t('links.status') }}</th>
            <th>{{ t('links.expires') }}</th>
            <th>{{ t('links.created') }}</th>
            <th />
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading"><td :colspan="mode === 'admin' ? 9 : 8">{{ t('common.loading') }}</td></tr>
          <tr v-for="item in items" :key="item.id">
            <td>{{ item.id }}</td>
            <td><code>{{ item.code }}</code></td>
            <td v-if="mode === 'admin'">
              <RouterLink v-if="item.user_id" :to="`/admin/users/${item.user_id}`">
                #{{ item.user_id }}
              </RouterLink>
              <span v-else class="muted">{{ t('links.guest') }}</span>
            </td>
            <td class="url" :title="item.target_url">{{ item.target_url }}</td>
            <td>{{ item.visit_count }}</td>
            <td>{{ statusText(item.status) }}</td>
            <td>{{ item.expires_at ? new Date(item.expires_at).toLocaleString() : t('links.never') }}</td>
            <td>{{ item.created_at ? new Date(item.created_at).toLocaleString() : '-' }}</td>
            <td class="actions">
              <RouterLink :to="`${detailBase}/${item.id}`">{{ t('links.detail') }}</RouterLink>
              <button type="button" @click="remove(item.id)">{{ t('links.delete') }}</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="pager">
      <button type="button" :disabled="page <= 1" @click="page--">{{ t('links.prev') }}</button>
      <span>{{ page }} / {{ pages() }}（{{ total }}）</span>
      <button type="button" :disabled="page >= pages()" @click="page++">{{ t('links.next') }}</button>
    </div>
  </div>
</template>

<style scoped>
.links h1 {
  margin: 0;
  font-family: Syne, Manrope, sans-serif;
  color: var(--ink);
}
.head {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1rem;
}
.search {
  display: flex;
  gap: 0.4rem;
}
.search input {
  min-width: 12rem;
  padding: 0.45rem 0.65rem;
  border: 1px solid var(--line);
  border-radius: 8px;
  background: #fff;
}
.search button,
.pager button,
.actions button {
  padding: 0.45rem 0.75rem;
  border-radius: 8px;
  border: 1px solid var(--line);
  background: #fff;
  cursor: pointer;
}
.table-wrap {
  overflow-x: auto;
  border: 1px solid var(--line);
  border-radius: 12px;
  background: #fff;
}
table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}
th, td {
  padding: 0.65rem 0.75rem;
  text-align: left;
  border-bottom: 1px solid var(--line);
}
.url {
  max-width: 18rem;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.actions {
  display: flex;
  gap: 0.5rem;
  white-space: nowrap;
}
.pager {
  display: flex;
  gap: 0.75rem;
  align-items: center;
  margin-top: 1rem;
}
.err { color: #b91c1c; }
.muted { opacity: 0.65; }
</style>
