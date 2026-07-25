<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { api, getStoredPlan } from '../api'

const props = defineProps({
  mode: { type: String, default: 'admin' },
})

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const link = ref(null)
const visits = ref([])
const visitTotal = ref(0)
const error = ref('')
const editURL = ref('')
const saving = ref(false)
const msg = ref('')

const listPath = computed(() => (props.mode === 'me' ? '/me/links' : '/admin/links'))
const canEdit = computed(() => {
  if (props.mode !== 'me') return false
  const plan = getStoredPlan()
  return !!plan?.features?.edit_target
})

async function load() {
  try {
    error.value = ''
    if (props.mode === 'me') {
      link.value = await api.meLink(route.params.id)
      const v = await api.meVisits(route.params.id, 1, 50)
      visits.value = v.items || []
      visitTotal.value = v.total || 0
    } else {
      link.value = await api.adminLink(route.params.id)
      const v = await api.adminVisits(route.params.id, 1, 50)
      visits.value = v.items || []
      visitTotal.value = v.total || 0
    }
    editURL.value = link.value?.target_url || ''
  } catch (e) {
    error.value = e.message
  }
}

async function remove() {
  if (!confirm(t('links.confirmDelete'))) return
  if (props.mode === 'me') await api.meDeleteLink(route.params.id)
  else await api.adminDeleteLink(route.params.id)
  router.push(listPath.value)
}

async function saveTarget() {
  if (!canEdit.value) return
  saving.value = true
  msg.value = ''
  try {
    link.value = await api.meUpdateLink(route.params.id, { target_url: editURL.value })
    msg.value = t('links.saved')
  } catch (e) {
    error.value = e.message
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="detail">
    <div class="head">
      <h1>{{ t('links.detailTitle') }}</h1>
      <div class="actions">
        <RouterLink :to="listPath">{{ t('links.back') }}</RouterLink>
        <button type="button" @click="remove">{{ t('links.delete') }}</button>
      </div>
    </div>
    <p v-if="error" class="err">{{ error }}</p>
    <p v-if="msg" class="ok">{{ msg }}</p>

    <div v-if="link" class="meta">
      <p><b>Code</b> {{ link.code }}</p>
      <p><b>{{ t('links.short') }}</b> <a :href="`/s/${link.code}`" target="_blank">/s/{{ link.code }}</a></p>
      <p v-if="mode === 'admin'">
        <b>{{ t('links.owner') }}</b>
        <RouterLink v-if="link.user_id" :to="`/admin/users/${link.user_id}`">
          #{{ link.user_id }}
        </RouterLink>
        <span v-else>{{ t('links.guest') }}</span>
        <template v-if="link.creator_ip">
          · <b>IP</b> {{ link.creator_ip }}
        </template>
      </p>
      <template v-if="canEdit">
        <label class="edit">
          <b>{{ t('links.target') }}</b>
          <input v-model="editURL" type="url" />
          <button type="button" :disabled="saving" @click="saveTarget">
            {{ saving ? t('common.loading') : t('links.save') }}
          </button>
        </label>
      </template>
      <p v-else class="break"><b>{{ t('links.target') }}</b> {{ link.target_url }}</p>
      <p><b>{{ t('links.features') }}</b> {{ link.features }}</p>
      <p>
        <b>{{ t('links.visits') }}</b> {{ link.visit_count }} ·
        <b>{{ t('links.status') }}</b> {{ link.status }} ·
        <b>{{ t('links.expires') }}</b>
        {{ link.expires_at ? new Date(link.expires_at).toLocaleString() : t('links.never') }}
      </p>
    </div>

    <h2>{{ t('links.visitLog') }}（{{ visitTotal }}）</h2>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>{{ t('links.time') }}</th>
            <th>IP</th>
            <th>{{ t('links.region') }}</th>
            <th>{{ t('links.device') }}</th>
            <th>{{ t('links.platform') }}</th>
            <th>{{ t('links.screen') }}</th>
            <th>{{ t('links.ok') }}</th>
            <th>{{ t('links.reason') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="v in visits" :key="v.id">
            <td>{{ v.created_at ? new Date(v.created_at).toLocaleString() : '-' }}</td>
            <td>{{ v.ip }}</td>
            <td>{{ [v.country, v.province, v.city].filter(Boolean).join(' ') || '-' }}</td>
            <td>{{ v.device_type }}</td>
            <td>{{ v.platform }}</td>
            <td>{{ v.screen_width }}×{{ v.screen_height }}</td>
            <td>{{ v.success ? t('common.yes') : t('common.no') }}</td>
            <td>{{ v.fail_reason || '-' }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.detail h1 {
  margin: 0;
  font-family: Syne, Manrope, sans-serif;
}
.head {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}
.actions {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}
.actions button {
  padding: 0.4rem 0.75rem;
  border-radius: 8px;
  border: 1px solid var(--line);
  background: #fff;
  cursor: pointer;
}
.meta {
  display: grid;
  gap: 0.5rem;
  padding: 1rem;
  border: 1px solid var(--line);
  border-radius: 12px;
  background: #fff;
  margin-bottom: 1.25rem;
}
.break { word-break: break-all; }
.edit {
  display: grid;
  gap: 0.4rem;
}
.edit input {
  padding: 0.5rem 0.65rem;
  border: 1px solid var(--line);
  border-radius: 8px;
}
.edit button {
  width: fit-content;
  padding: 0.4rem 0.75rem;
  border-radius: 8px;
  border: 1px solid var(--line);
  background: #0f766e;
  color: #fff;
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
  font-size: 0.88rem;
}
th, td {
  padding: 0.55rem 0.65rem;
  text-align: left;
  border-bottom: 1px solid var(--line);
}
.err { color: #b91c1c; }
.ok { color: #0f766e; }
</style>
