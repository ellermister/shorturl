<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { api, getStoredPlan } from '../api'
import Tip from './Tip.vue'
import GeoPolicyEditor from './GeoPolicyEditor.vue'

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
const geoPolicy = ref({ require: '', fallback_url: '', rules: [] })
const password = ref('')
const whisper = ref('')
const expireMode = ref('keep') // keep | never | days
const expireDays = ref(7)
const saving = ref(false)
const msg = ref('')

const features = reactive({
  ban_china_browser: false,
  fake_page: false,
  once: false,
  password: false,
  pc_only: false,
  mobile_only: false,
  normal: false,
  encrypt: true,
  dynamic: false,
  whisper: false,
})

const featureKeys = {
  firewall: ['ban_china_browser', 'fake_page', 'once', 'password', 'pc_only', 'mobile_only'],
  endpoint: ['normal', 'encrypt'],
  encryptExtras: ['dynamic', 'whisper'],
}

const listPath = computed(() => (props.mode === 'me' ? '/me/links' : '/admin/links'))
const canEdit = computed(() => {
  if (props.mode === 'admin') return true
  const plan = getStoredPlan()
  return !!plan?.features?.edit_target
})

const planFeatures = computed(() => {
  if (props.mode === 'admin') {
    return { max_expire_days: 3650, allow_never_expire: true }
  }
  return getStoredPlan()?.features || { max_expire_days: 7, allow_never_expire: false }
})

const expireDayOptions = computed(() => {
  const max = planFeatures.value.max_expire_days || 7
  const opts = []
  for (const d of [1, 3, 7, 30, 90, 365, 3650]) {
    if (d <= max) opts.push(d)
  }
  if (!opts.includes(max) && max > 0) opts.push(max)
  return opts.sort((a, b) => a - b)
})

const firewallFeatures = computed(() =>
  featureKeys.firewall.map((key) => ({
    key,
    label: t(`home.features.${key}.label`),
    tip: t(`home.features.${key}.tip`),
  })),
)

const endpointFeatures = computed(() =>
  featureKeys.endpoint.map((key) => ({
    key,
    label: t(`home.features.${key}.label`),
    tip: t(`home.features.${key}.tip`),
  })),
)

const encryptExtraFeatures = computed(() =>
  featureKeys.encryptExtras.map((key) => ({
    key,
    label: t(`home.features.${key}.label`),
    tip: t(`home.features.${key}.tip`),
  })),
)

watch(() => features.pc_only, (v) => { if (v) features.mobile_only = false })
watch(() => features.mobile_only, (v) => { if (v) features.pc_only = false })

function pickEndpoint(key) {
  featureKeys.endpoint.forEach((k) => {
    features[k] = k === key
  })
  if (key === 'normal') {
    features.dynamic = false
    features.whisper = false
    features.password = false
  }
}

function parseFeatures(raw) {
  try {
    const v = JSON.parse(raw || '[]')
    return Array.isArray(v) ? v : []
  } catch {
    return []
  }
}

function parseGeoPolicy(raw, featuresRaw) {
  let p = { require: '', fallback_url: '', rules: [] }
  try {
    if (raw && String(raw).trim() && String(raw).trim() !== '{}') {
      const parsed = JSON.parse(raw)
      p = {
        require: parsed.require || '',
        fallback_url: parsed.fallback_url || '',
        rules: Array.isArray(parsed.rules) ? parsed.rules : [],
      }
    }
  } catch {
    /* ignore */
  }
  if (!p.require) {
    const feats = parseFeatures(featuresRaw)
    if (feats.includes('china_only')) p.require = 'mainland'
    else if (feats.includes('non_china_only')) p.require = 'overseas'
  }
  p.rules = (p.rules || []).map((r) => ({
    country: r.country || '',
    province: r.province || '',
    city: r.city || '',
    isp: r.isp || '',
    url: r.url || '',
  }))
  return p
}

function buildGeoPayload(p) {
  return {
    require: p.require || '',
    fallback_url: (p.fallback_url || '').trim(),
    rules: (p.rules || [])
      .filter((r) => (r.url || '').trim())
      .map((r) => ({
        country: (r.country || '').trim().toUpperCase(),
        province: (r.province || '').trim(),
        city: (r.city || '').trim(),
        isp: r.isp || '',
        url: (r.url || '').trim(),
      })),
  }
}

function selectedFeatures() {
  return Object.keys(features).filter((k) => features[k])
}

function hydrateEditor(row) {
  editURL.value = row?.target_url || ''
  password.value = row?.password || ''
  whisper.value = row?.whisper || ''
  expireMode.value = 'keep'
  expireDays.value = expireDayOptions.value[0] || 7

  const keys = Object.keys(features)
  keys.forEach((k) => { features[k] = false })
  const list = parseFeatures(row?.features)
  list.forEach((k) => {
    if (k in features) features[k] = true
  })
  if (!features.normal && !features.encrypt) features.encrypt = true
  if (features.normal) {
    features.dynamic = false
    features.whisper = false
    features.password = false
  }

  const p = parseGeoPolicy(row?.geo_policy, row?.features)
  geoPolicy.value = {
    require: p.require,
    fallback_url: p.fallback_url,
    rules: p.rules.length ? p.rules : [],
  }
}

async function load() {
  try {
    error.value = ''
    msg.value = ''
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
    hydrateEditor(link.value)
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

async function save() {
  if (!canEdit.value) return
  saving.value = true
  error.value = ''
  msg.value = ''

  const extent = {}
  if (features.password) extent.password = password.value
  if (features.whisper) extent.whisper = whisper.value

  const payload = {
    target_url: editURL.value,
    features: selectedFeatures(),
    geo_policy: buildGeoPayload(geoPolicy.value),
    extent,
  }
  if (expireMode.value === 'never') {
    payload.expire_days = 0
  } else if (expireMode.value === 'days') {
    payload.expire_days = expireDays.value
  }

  try {
    if (props.mode === 'me') {
      link.value = await api.meUpdateLink(route.params.id, payload)
    } else {
      link.value = await api.adminUpdateLink(route.params.id, payload)
    }
    hydrateEditor(link.value)
    msg.value = t('links.saved')
  } catch (e) {
    error.value = e.message
  } finally {
    saving.value = false
  }
}

function screenText(v) {
  const w = Number(v?.screen_width) || 0
  const h = Number(v?.screen_height) || 0
  if (!w && !h) return '-'
  return `${w}×${h}`
}

function cellText(v) {
  const s = String(v ?? '').trim()
  return s || '-'
}

function regionText(v) {
  const parts = [v?.country, v?.province, v?.city]
    .map((s) => String(s ?? '').trim())
    .filter(Boolean)
  const unique = []
  const seen = new Set()
  for (const p of parts) {
    const key = p.toLowerCase()
    if (seen.has(key)) continue
    seen.add(key)
    unique.push(p)
  }
  return unique.length ? unique.join(' ') : '-'
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
      <p>
        <b>{{ t('links.short') }}</b>
        <a :href="`/s/${link.code}`" target="_blank">/s/{{ link.code }}</a>
      </p>
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
        </label>

        <div class="edit">
          <b>{{ t('home.groupLimit') }}</b>
          <div class="opts">
            <label
              v-for="f in firewallFeatures"
              :key="f.key"
              class="opt"
              :class="{ on: features[f.key] }"
            >
              <input v-model="features[f.key]" type="checkbox" />
              <span>{{ f.label }}</span>
              <Tip :text="f.tip" />
            </label>
          </div>
        </div>

        <div class="edit">
          <b>{{ t('home.groupJump') }}</b>
          <div class="opts">
            <label
              v-for="f in endpointFeatures"
              :key="f.key"
              class="opt"
              :class="{ on: features[f.key] }"
              @click.prevent="pickEndpoint(f.key)"
            >
              <input :checked="features[f.key]" type="radio" name="endpoint" readonly />
              <span>{{ f.label }}</span>
              <Tip :text="f.tip" />
            </label>
          </div>
        </div>

        <div v-if="features.encrypt" class="edit">
          <b>{{ t('home.groupEncryptExtra') }}</b>
          <div class="opts">
            <label
              v-for="f in encryptExtraFeatures"
              :key="f.key"
              class="opt"
              :class="{ on: features[f.key] }"
            >
              <input v-model="features[f.key]" type="checkbox" />
              <span>{{ f.label }}</span>
              <Tip :text="f.tip" />
            </label>
          </div>
        </div>

        <div v-if="features.password" class="edit">
          <label>
            <b>{{ t('home.password') }}</b>
            <span class="muted"> {{ t('links.passwordKeepHint') }}</span>
          </label>
          <input v-model="password" type="text" autocomplete="off" :placeholder="t('home.passwordPlaceholder')" />
        </div>

        <div v-if="features.whisper" class="edit">
          <b>{{ t('home.whisper') }}</b>
          <textarea v-model="whisper" rows="3" :placeholder="t('home.whisperPlaceholder')" />
        </div>

        <div class="edit">
          <b>{{ t('home.groupExpire') }}</b>
          <p class="muted tip">
            {{ t('links.currentExpire') }}：
            {{ link.expires_at ? new Date(link.expires_at).toLocaleString() : t('links.never') }}
          </p>
          <div class="opts">
            <label class="opt" :class="{ on: expireMode === 'keep' }" @click.prevent="expireMode = 'keep'">
              <input :checked="expireMode === 'keep'" type="radio" name="expire" readonly />
              <span>{{ t('links.keepExpire') }}</span>
            </label>
            <label
              v-for="d in expireDayOptions"
              :key="d"
              class="opt"
              :class="{ on: expireMode === 'days' && expireDays === d }"
              @click.prevent="expireMode = 'days'; expireDays = d"
            >
              <input :checked="expireMode === 'days' && expireDays === d" type="radio" name="expire" readonly />
              <span>{{ t('home.expireDays', { n: d }) }}</span>
            </label>
            <label
              v-if="planFeatures.allow_never_expire"
              class="opt"
              :class="{ on: expireMode === 'never' }"
              @click.prevent="expireMode = 'never'"
            >
              <input :checked="expireMode === 'never'" type="radio" name="expire" readonly />
              <span>{{ t('home.expireNever') }}</span>
            </label>
          </div>
          <p v-if="expireMode === 'days'" class="muted tip">{{ t('links.expireFromNow') }}</p>
        </div>

        <div class="edit">
          <b>{{ t('links.geoPolicy') }}</b>
          <GeoPolicyEditor v-model="geoPolicy" />
        </div>

        <button type="button" class="save" :disabled="saving" @click="save">
          {{ saving ? t('common.loading') : t('links.save') }}
        </button>
      </template>
      <template v-else>
        <p class="break"><b>{{ t('links.target') }}</b> {{ link.target_url }}</p>
        <p class="muted">{{ t('links.editDenied') }}</p>
      </template>

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
            <td>{{ regionText(v) }}</td>
            <td>{{ cellText(v.device_type) }}</td>
            <td>{{ cellText(v.platform) }}</td>
            <td>{{ screenText(v) }}</td>
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
  gap: 0.85rem;
  padding: 1rem;
  border: 1px solid var(--line);
  border-radius: 12px;
  background: #fff;
  margin-bottom: 1.25rem;
}
.break { word-break: break-all; }
.muted {
  color: #6b7280;
  font-weight: 500;
  font-size: 0.88rem;
}
.tip {
  margin: 0;
}
.edit {
  display: grid;
  gap: 0.45rem;
}
.edit > b {
  font-size: 0.92rem;
}
.edit input[type='url'],
.edit input[type='text'],
.edit textarea {
  padding: 0.5rem 0.65rem;
  border: 1px solid var(--line);
  border-radius: 8px;
  font: inherit;
  width: 100%;
  background: #f9fafb;
}
.opts {
  display: flex;
  flex-wrap: wrap;
  gap: 0.45rem;
}
.opt {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.42rem 0.7rem;
  border: 1px solid var(--line);
  border-radius: 999px;
  background: #fff;
  font-size: 0.88rem;
  font-weight: 600;
  cursor: pointer;
  user-select: none;
}
.opt.on {
  border-color: #2563eb;
  background: #eff6ff;
  color: #1d4ed8;
}
.opt input {
  margin: 0;
  accent-color: #2563eb;
}
.save {
  width: fit-content;
  padding: 0.45rem 0.9rem;
  border-radius: 8px;
  border: 1px solid var(--line);
  background: #0f766e;
  color: #fff;
  cursor: pointer;
  font: inherit;
  font-weight: 650;
}
.save:disabled {
  opacity: 0.55;
  cursor: not-allowed;
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
