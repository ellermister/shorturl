<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useHead } from '@unhead/vue'
import { useI18n } from 'vue-i18n'
import { api, clearAuth, getStoredPlan, getStoredUser } from '../api'
import Tip from '../components/Tip.vue'
import LangSwitch from '../components/LangSwitch.vue'
import { CN_PROVINCES, COUNTRIES, ISP_OPTIONS, emptyGeoRule, countryLabel } from '../data/geoRegions'

const { t, locale } = useI18n()

useHead({
  title: () => t('home.brand'),
  meta: [
    { name: 'description', content: () => t('home.lead') },
  ],
})

const url = ref('')
const loading = ref(false)
const error = ref('')
const result = ref(null)
const summary = ref({ active: 0, history: 0 })
const copied = ref(false)
const customCode = ref('')
const expireDays = ref(3)
const user = ref(getStoredUser())
const plan = ref(getStoredPlan())
const guestMaxDays = 3

const features = reactive({
  ban_china_browser: true,
  fake_page: true,
  once: false,
  password: false,
  pc_only: false,
  mobile_only: false,
  china_only: false,
  non_china_only: false,
  normal: false,
  encrypt: true,
  dynamic: false,
  whisper: false,
})

const password = ref('')
const whisper = ref('')
const geoAdvanced = ref(false)
const geoFallback = ref('')
const geoRules = ref([emptyGeoRule()])

const featureKeys = {
  firewall: [
    'ban_china_browser',
    'fake_page',
    'once',
    'password',
    'pc_only',
    'mobile_only',
    'china_only',
    'non_china_only',
  ],
  endpoint: ['normal', 'encrypt'],
  encryptExtras: ['dynamic', 'whisper'],
}

const ispOptions = computed(() =>
  ISP_OPTIONS.map((o) => ({
    value: o.value,
    label: t(`home.geo.isp.${o.labelKey}`),
  })),
)

const countryOptions = computed(() =>
  COUNTRIES.map((c) => ({
    code: c.code,
    label: `${countryLabel(c, locale.value)} (${c.code})`,
  })),
)

const showGeoExtras = computed(
  () => geoAdvanced.value || features.china_only || features.non_china_only,
)

function usesCnProvinceSelect(rule) {
  return !rule.country || rule.country === 'CN'
}

function onCountryChange(rule) {
  // CN province list values are not meaningful for other countries.
  if (rule.country && rule.country !== 'CN' && CN_PROVINCES.includes(rule.province)) {
    rule.province = ''
  }
}

const planFeatures = computed(() => {
  if (!user.value) {
    return { custom_code: false, edit_target: false, max_expire_days: guestMaxDays, allow_never_expire: false }
  }
  return plan.value?.features || {
    custom_code: false, edit_target: false, max_expire_days: 3, allow_never_expire: false,
  }
})

const expireOptions = computed(() => {
  const max = planFeatures.value.max_expire_days || guestMaxDays
  const opts = []
  for (const d of [1, 3, 7, 30, 90, 365]) {
    if (d <= max) opts.push(d)
  }
  if (!opts.includes(max) && max > 0) opts.push(max)
  opts.sort((a, b) => a - b)
  return opts
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
    tip: key === 'whisper' && !user.value
      ? t('home.whisperLoginOnly')
      : t(`home.features.${key}.tip`),
    locked: key === 'whisper' && !user.value,
  })),
)

const aboutItems = computed(() =>
  [
    'diff',
    'banChina',
    'fake',
    'burn',
    'password',
    'device',
    'region',
    'noReferrer',
    'whisper',
    'expire',
    'jumpMode',
  ].map((key) => ({
    key,
    q: t(`home.about.${key}.q`),
    a: t(`home.about.${key}.a`),
  })),
)

watch(() => features.pc_only, (v) => { if (v) features.mobile_only = false })
watch(() => features.mobile_only, (v) => { if (v) features.pc_only = false })
watch(() => features.china_only, (v) => { if (v) features.non_china_only = false })
watch(() => features.non_china_only, (v) => { if (v) features.china_only = false })

function addGeoRule() {
  geoRules.value.push(emptyGeoRule())
}

function removeGeoRule(idx) {
  if (geoRules.value.length <= 1) {
    geoRules.value = [emptyGeoRule()]
    return
  }
  geoRules.value.splice(idx, 1)
}

function buildGeoPolicy() {
  const require = features.china_only
    ? 'mainland'
    : features.non_china_only
      ? 'overseas'
      : ''
  const rules = geoAdvanced.value
    ? geoRules.value
      .filter((r) => (r.url || '').trim())
      .map((r) => ({
        country: (r.country || '').trim().toUpperCase(),
        province: (r.province || '').trim(),
        city: (r.city || '').trim(),
        isp: r.isp || '',
        url: (r.url || '').trim(),
      }))
    : []
  const fallback_url = (geoFallback.value || '').trim()
  if (!require && !fallback_url && rules.length === 0) {
    return null
  }
  return { require, fallback_url, rules }
}

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

function onEncryptExtraChange(key, checked) {
  if (key === 'whisper' && !user.value) {
    features.whisper = false
    return
  }
  features[key] = checked
}

function logout() {
  clearAuth()
  user.value = null
  plan.value = null
}

onMounted(async () => {
  try {
    summary.value = await api.summary()
  } catch (_) {
    /* ignore */
  }
  if (user.value) {
    try {
      const me = await api.me()
      user.value = me.user
      plan.value = me.plan
      localStorage.setItem('auth_user', JSON.stringify(me.user))
      localStorage.setItem('auth_plan', JSON.stringify(me.plan))
    } catch (_) {
      /* token may be stale */
    }
  }
  const max = planFeatures.value.max_expire_days || guestMaxDays
  expireDays.value = Math.min(expireDays.value, max)
})

async function generate() {
  error.value = ''
  result.value = null
  copied.value = false
  if (!url.value.trim()) {
    error.value = t('home.needUrl')
    return
  }
  if (!user.value) {
    features.whisper = false
    whisper.value = ''
  }
  const selected = Object.keys(features).filter((k) => features[k])
  const extent = {}
  if (features.password) extent.password = password.value
  if (features.whisper && user.value) extent.whisper = whisper.value

  const payload = {
    url: url.value.trim(),
    features: selected,
    extent,
    expire_days: expireDays.value === 0 ? 0 : expireDays.value,
  }
  if (planFeatures.value.custom_code && customCode.value.trim()) {
    payload.custom_code = customCode.value.trim()
  }
  const geo = buildGeoPolicy()
  if (geo) payload.geo_policy = geo

  loading.value = true
  try {
    const created = await api.createLink(payload)
    if (created?.code) {
      created.short_url = `${window.location.origin}/s/${created.code}`
    }
    result.value = created
    summary.value = await api.summary().catch(() => summary.value)
  } catch (e) {
    error.value = e.message || t('home.generateFail')
  } finally {
    loading.value = false
  }
}

async function copyResult() {
  if (!result.value?.short_url) return
  try {
    await navigator.clipboard.writeText(result.value.short_url)
    copied.value = true
  } catch (_) {
    copied.value = false
  }
}
</script>

<template>
  <div class="home">
    <header class="nav">
      <RouterLink class="nav-brand" to="/">
        <span class="nav-mark" aria-hidden="true" />
        <span>{{ t('home.brand') }}</span>
      </RouterLink>
      <div class="nav-actions">
        <LangSwitch />
        <template v-if="user">
          <RouterLink class="nav-text" :to="user.role === 'admin' ? '/admin' : '/me'">
            {{ user.role === 'admin' ? t('common.admin') : t('common.account') }}
          </RouterLink>
          <button type="button" class="nav-text btn-link" @click="logout">{{ t('common.logout') }}</button>
        </template>
        <template v-else>
          <RouterLink class="nav-text" to="/register">{{ t('auth.register') }}</RouterLink>
          <RouterLink class="nav-cta" to="/login">{{ t('auth.login') }}</RouterLink>
        </template>
      </div>
    </header>

    <main class="main">
      <section class="hero">
        <h1 class="brand">{{ t('home.brand') }}</h1>
        <p class="lead">{{ t('home.lead') }}</p>
      </section>

      <section class="panel" :aria-label="t('home.panelAria')">
        <div class="url-row">
          <input
            v-model="url"
            type="url"
            :placeholder="t('home.urlPlaceholder')"
            @keyup.enter="generate"
          />
          <button type="button" :disabled="loading" @click="generate">
            {{ loading ? t('home.generating') : t('home.generate') }}
          </button>
        </div>

        <div class="group">
          <div class="group-title">{{ t('home.groupLimit') }}</div>
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
          <label class="geo-toggle">
            <input v-model="geoAdvanced" type="checkbox" />
            <span>{{ t('home.geo.advanced') }}</span>
            <Tip :text="t('home.geo.advancedTip')" />
          </label>
          <div v-if="geoAdvanced" class="geo-panel">
            <p class="muted tip-line">{{ t('home.geo.rulesHint') }}</p>
            <div v-for="(rule, idx) in geoRules" :key="idx" class="geo-rule">
              <select v-model="rule.country" @change="onCountryChange(rule)">
                <option value="">{{ t('home.geo.anyCountry') }}</option>
                <option v-for="c in countryOptions" :key="c.code" :value="c.code">
                  {{ c.label }}
                </option>
              </select>
              <select v-if="usesCnProvinceSelect(rule)" v-model="rule.province">
                <option value="">{{ t('home.geo.anyProvince') }}</option>
                <option v-for="p in CN_PROVINCES" :key="p" :value="p">{{ p }}</option>
              </select>
              <input
                v-else
                v-model="rule.province"
                type="text"
                :placeholder="t('home.geo.provincePh')"
                autocomplete="off"
              />
              <input
                v-model="rule.city"
                type="text"
                :placeholder="t('home.geo.cityPh')"
                autocomplete="off"
              />
              <select v-model="rule.isp">
                <option v-for="o in ispOptions" :key="o.value || 'any'" :value="o.value">
                  {{ o.label }}
                </option>
              </select>
              <input
                v-model="rule.url"
                type="url"
                :placeholder="t('home.geo.ruleUrlPh')"
                autocomplete="off"
              />
              <button type="button" class="ghost geo-rm" @click="removeGeoRule(idx)">
                {{ t('home.geo.remove') }}
              </button>
            </div>
            <button type="button" class="ghost geo-add" @click="addGeoRule">
              {{ t('home.geo.addRule') }}
            </button>
          </div>
          <div v-if="showGeoExtras" class="extra geo-fallback">
            <label for="geo-fallback">
              {{ t('home.geo.fallback') }}
              <span class="muted">{{ t('home.geo.fallbackHint') }}</span>
            </label>
            <input
              id="geo-fallback"
              v-model="geoFallback"
              type="url"
              :placeholder="t('home.geo.fallbackPh')"
              autocomplete="off"
            />
          </div>
        </div>

        <div class="group">
          <div class="group-title">{{ t('home.groupJump') }}</div>
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

        <div v-if="features.encrypt" class="group">
          <div class="group-title">{{ t('home.groupEncryptExtra') }}</div>
          <div class="opts">
            <label
              v-for="f in encryptExtraFeatures"
              :key="f.key"
              class="opt"
              :class="{ on: features[f.key], locked: f.locked }"
            >
              <input
                :checked="features[f.key]"
                type="checkbox"
                :disabled="f.locked"
                @change="onEncryptExtraChange(f.key, $event.target.checked)"
              />
              <span>{{ f.label }}</span>
              <span v-if="f.locked" class="badge">{{ t('home.loginRequired') }}</span>
              <Tip :text="f.tip" />
            </label>
          </div>
          <p v-if="!user" class="muted tip-line">
            <RouterLink to="/login">{{ t('home.whisperLoginOnly') }}</RouterLink>
          </p>
        </div>

        <div class="group">
          <div class="group-title">{{ t('home.groupExpire') }}</div>
          <div class="opts">
            <label
              v-for="d in expireOptions"
              :key="d"
              class="opt"
              :class="{ on: expireDays === d }"
              @click.prevent="expireDays = d"
            >
              <input :checked="expireDays === d" type="radio" name="expire" readonly />
              <span>{{ t('home.expireDays', { n: d }) }}</span>
            </label>
            <label
              v-if="planFeatures.allow_never_expire"
              class="opt"
              :class="{ on: expireDays === 0 }"
              @click.prevent="expireDays = 0"
            >
              <input :checked="expireDays === 0" type="radio" name="expire" readonly />
              <span>{{ t('home.expireNever') }}</span>
            </label>
          </div>
          <p v-if="!user" class="muted tip-line">{{ t('home.guestExpireHint') }}</p>
        </div>

        <div v-if="planFeatures.custom_code" class="extra">
          <label for="code">
            {{ t('home.customCode') }}
            <span class="muted">{{ t('home.customCodeHint') }}</span>
          </label>
          <input
            id="code"
            v-model="customCode"
            type="text"
            :placeholder="t('home.customCodePh')"
            autocomplete="off"
          />
        </div>
        <p v-else-if="!user" class="muted tip-line">
          <RouterLink to="/register">{{ t('home.upgradeHint') }}</RouterLink>
        </p>

        <div v-if="features.password" class="extra">
          <label for="pwd">
            {{ t('home.password') }}
            <span class="muted">{{ t('home.passwordHint') }}</span>
          </label>
          <input
            id="pwd"
            v-model="password"
            type="text"
            :placeholder="t('home.passwordPlaceholder')"
            autocomplete="off"
          />
        </div>
        <div v-if="features.whisper" class="extra">
          <label for="whisper">{{ t('home.whisper') }}</label>
          <textarea
            id="whisper"
            v-model="whisper"
            rows="4"
            :placeholder="t('home.whisperPlaceholder')"
          />
        </div>

        <p v-if="error" class="err" role="alert">{{ error }}</p>

        <div v-if="result" class="result">
          <div class="result-label">{{ t('home.shortLink') }}</div>
          <div class="result-row">
            <input :value="result.short_url" readonly />
            <button type="button" class="ghost" @click="copyResult">
              {{ copied ? t('home.copied') : t('home.copy') }}
            </button>
          </div>
          <p v-if="result.password" class="hint">
            {{ t('home.passwordShow', { pwd: result.password }) }}
          </p>
          <p v-if="result.expires_at" class="hint">
            {{ t('home.expiresAt', { t: new Date(result.expires_at).toLocaleString() }) }}
          </p>
        </div>
      </section>

      <p class="stats">
        {{ t('home.stats', { history: summary.history || 0, active: summary.active || 0 }) }}
      </p>

      <section class="about" :aria-label="t('home.aboutAria')">
        <h2>{{ t('home.aboutTitle') }}</h2>
        <p class="about-lead">{{ t('home.aboutLead') }}</p>
        <ul>
          <li v-for="item in aboutItems" :key="item.key">
            <strong>{{ item.q }}</strong>
            <span class="faq-a">{{ item.a }}</span>
          </li>
        </ul>
      </section>
    </main>
  </div>
</template>

<style scoped>
.home {
  --ink: #111827;
  --ink-soft: #6b7280;
  --paper: #ffffff;
  --line: #e5e7eb;
  --accent: #2563eb;
  --accent-hover: #1d4ed8;
  --accent-soft: #eff6ff;
  --danger: #dc2626;
  --radius: 12px;
  --radius-sm: 8px;
  min-height: 100vh;
  color: var(--ink);
  background:
    radial-gradient(900px 420px at 50% -12%, rgba(37, 99, 235, 0.08), transparent 60%),
    var(--paper);
  overflow-x: clip;
}
.nav {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  width: min(960px, 100%);
  margin: 0 auto;
  padding: 1.1rem 1.25rem;
}
.nav-brand {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
  text-decoration: none;
  color: var(--accent);
  font-family: Syne, Manrope, sans-serif;
  font-weight: 780;
  font-size: 1.2rem;
  letter-spacing: -0.02em;
}
.nav-mark {
  width: 1.55rem;
  height: 1.55rem;
  border-radius: 999px;
  background:
    radial-gradient(circle at 35% 35%, #fff 0 28%, transparent 29%),
    linear-gradient(145deg, #60a5fa, var(--accent));
  box-shadow: inset 0 0 0 2px rgba(255, 255, 255, 0.35);
}
.nav-actions {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}
.nav-text {
  color: var(--ink-soft);
  text-decoration: none;
  font-weight: 600;
  font-size: 0.92rem;
  border: 0;
  background: transparent;
  cursor: pointer;
  font: inherit;
  font-weight: 600;
  padding: 0;
}
.nav-text:hover {
  color: var(--ink);
}
.nav-cta {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 2.25rem;
  padding: 0 1rem;
  border-radius: var(--radius-sm);
  background: var(--accent);
  color: #fff;
  text-decoration: none;
  font-weight: 700;
  font-size: 0.9rem;
}
.nav-cta:hover {
  background: var(--accent-hover);
}
.btn-link {
  border: 0;
  background: transparent;
  cursor: pointer;
  font: inherit;
  padding: 0;
}
.tip-line {
  margin: 0.4rem 0 0;
  font-size: 0.85rem;
}
.tip-line a {
  color: var(--accent);
  text-decoration: none;
  font-weight: 600;
}
.main {
  width: min(720px, 100%);
  margin: 0 auto;
  padding: 1.25rem 1.25rem 4rem;
}
.hero {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 0.7rem;
  margin-bottom: 1.75rem;
  animation: rise 0.5s ease both;
}
.brand {
  margin: 0;
  font-family: Syne, Manrope, sans-serif;
  font-weight: 800;
  font-size: clamp(2.4rem, 8vw, 3.4rem);
  letter-spacing: -0.03em;
  line-height: 1.1;
  color: var(--ink);
}
.lead {
  margin: 0;
  max-width: 34rem;
  color: var(--ink-soft);
  font-size: 1.05rem;
  line-height: 1.6;
}
.panel {
  background: #fff;
  border: 1px solid var(--line);
  border-radius: 16px;
  padding: 1.25rem;
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
  animation: rise 0.55s ease 0.04s both;
}
.url-row {
  display: flex;
  gap: 0.55rem;
}
.url-row input,
.extra input,
.extra textarea,
.result-row input {
  width: 100%;
  border: 1px solid var(--line);
  border-radius: var(--radius-sm);
  background: #f9fafb;
  color: var(--ink);
  padding: 0.8rem 0.9rem;
  font: inherit;
  outline: none;
}
.url-row input::placeholder,
.extra input::placeholder,
.extra textarea::placeholder {
  color: #9ca3af;
}
.url-row input:focus,
.extra input:focus,
.extra textarea:focus,
.result-row input:focus {
  border-color: var(--accent);
  background: #fff;
  box-shadow: 0 0 0 3px var(--accent-soft);
}
.url-row button,
.result-row button {
  flex: 0 0 auto;
  border: 0;
  border-radius: var(--radius-sm);
  background: var(--accent);
  color: #fff;
  padding: 0 1.2rem;
  font: inherit;
  font-weight: 700;
  cursor: pointer;
}
.url-row button:hover,
.result-row button:hover {
  background: var(--accent-hover);
}
.url-row button:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}
.result-row button.ghost {
  background: #111827;
}
.result-row button.ghost:hover {
  background: #000;
}
.group {
  margin-top: 1.2rem;
}
.group-title {
  margin-bottom: 0.55rem;
  font-size: 0.8rem;
  font-weight: 700;
  color: var(--ink-soft);
}
.opts {
  display: flex;
  flex-wrap: wrap;
  gap: 0.45rem;
}
.opt {
  position: relative;
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.42rem 0.7rem;
  border: 1px solid var(--line);
  border-radius: 999px;
  background: #fff;
  color: var(--ink);
  font-size: 0.88rem;
  font-weight: 600;
  cursor: pointer;
  user-select: none;
}
.opt.on {
  border-color: var(--accent);
  background: var(--accent-soft);
  color: var(--accent-hover);
}
.opt.locked {
  opacity: 0.5;
  cursor: not-allowed;
}
.opt .badge {
  font-size: 0.68rem;
  font-weight: 700;
  padding: 0.1rem 0.4rem;
  border-radius: 999px;
  background: #e5e7eb;
  color: var(--ink-soft);
}
.opt input {
  margin: 0;
  accent-color: var(--accent);
}
.geo-toggle {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  margin-top: 0.75rem;
  font-size: 0.88rem;
  font-weight: 650;
  cursor: pointer;
  user-select: none;
}
.geo-toggle input {
  margin: 0;
  accent-color: var(--accent);
}
.geo-panel {
  margin-top: 0.65rem;
  display: grid;
  gap: 0.55rem;
}
.geo-rule {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
  gap: 0.4rem;
}
.geo-rule select,
.geo-rule input {
  width: 100%;
  border: 1px solid var(--line);
  border-radius: var(--radius-sm);
  background: #f9fafb;
  color: var(--ink);
  padding: 0.55rem 0.65rem;
  font: inherit;
  outline: none;
}
.geo-rule select:focus,
.geo-rule input:focus {
  border-color: #93c5fd;
  background: #fff;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}
.geo-rule input[type='url'] {
  grid-column: 1 / -1;
}
.geo-rm,
.geo-add {
  border: 1px solid var(--line);
  border-radius: var(--radius-sm);
  background: #fff;
  color: var(--ink-soft);
  font: inherit;
  font-size: 0.85rem;
  font-weight: 650;
  padding: 0.45rem 0.7rem;
  cursor: pointer;
}
.geo-rm {
  grid-column: 1 / -1;
  justify-self: start;
}
.geo-add:hover,
.geo-rm:hover {
  color: var(--ink);
  border-color: #cbd5e1;
}
.geo-fallback {
  margin-top: 0.75rem;
}
@media (min-width: 720px) {
  .geo-rule {
    grid-template-columns: 8.5rem 7rem 6rem 7rem minmax(0, 1fr) auto;
    align-items: center;
  }
  .geo-rule input[type='url'] {
    grid-column: auto;
  }
  .geo-rm {
    grid-column: auto;
  }
}
.extra {
  margin-top: 0.95rem;
  display: grid;
  gap: 0.35rem;
}
.extra label {
  font-size: 0.88rem;
  font-weight: 650;
  color: var(--ink);
}
.muted {
  color: var(--ink-soft);
  font-weight: 500;
}
.err {
  margin: 0.9rem 0 0;
  color: var(--danger);
  font-weight: 600;
}
.result {
  margin-top: 1.15rem;
  padding-top: 1rem;
  border-top: 1px solid var(--line);
  animation: rise 0.35s ease both;
}
.result-label {
  margin-bottom: 0.4rem;
  font-size: 0.85rem;
  font-weight: 700;
}
.result-row {
  display: flex;
  gap: 0.5rem;
}
.hint {
  margin: 0.45rem 0 0;
  color: var(--ink-soft);
  font-size: 0.9rem;
}
.stats {
  margin: 1.15rem 0 0;
  text-align: center;
  color: var(--ink-soft);
  font-size: 0.9rem;
}
.about {
  margin-top: 2.5rem;
  padding-top: 1.75rem;
  border-top: 1px solid var(--line);
}
.about h2 {
  margin: 0 0 0.5rem;
  text-align: center;
  font-family: Syne, Manrope, sans-serif;
  font-size: 1.35rem;
  color: var(--ink);
}
.about-lead {
  margin: 0 auto 1.25rem;
  max-width: 36rem;
  text-align: center;
  color: var(--ink-soft);
  line-height: 1.65;
}
.about ul {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: 0.75rem;
}
.about li {
  display: grid;
  gap: 0.2rem;
  padding: 0.95rem 1rem;
  border: 1px solid var(--line);
  border-radius: var(--radius);
  background: #fff;
}
.about strong {
  color: var(--ink);
  font-size: 0.95rem;
}
.about .faq-a {
  color: var(--ink-soft);
  font-size: 0.9rem;
  line-height: 1.55;
  white-space: pre-line;
}
.home :deep(.lang select) {
  border-color: var(--line);
  border-radius: var(--radius-sm);
  background-color: #fff;
  color: var(--ink-soft);
}
@keyframes rise {
  from {
    opacity: 0;
    transform: translateY(8px);
  }
  to {
    opacity: 1;
    transform: none;
  }
}
@media (max-width: 640px) {
  .nav {
    padding-inline: 1rem;
  }
  .nav-text:not(.btn-link) {
    display: none;
  }
  .url-row,
  .result-row {
    flex-direction: column;
  }
  .url-row button,
  .result-row button {
    min-height: 2.75rem;
  }
}
</style>
