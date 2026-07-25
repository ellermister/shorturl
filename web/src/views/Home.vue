<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useHead } from '@unhead/vue'
import { useI18n } from 'vue-i18n'
import { api, clearAuth, getStoredPlan, getStoredUser } from '../api'
import Tip from '../components/Tip.vue'
import LangSwitch from '../components/LangSwitch.vue'

const { t } = useI18n()

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
  ['privacy', 'burn', 'encrypt', 'anticrawl', 'note', 'visits'].map((key) => ({
    key,
    title: t(`home.about.${key}.title`),
    text: t(`home.about.${key}.text`),
  })),
)

watch(() => features.pc_only, (v) => { if (v) features.mobile_only = false })
watch(() => features.mobile_only, (v) => { if (v) features.pc_only = false })
watch(() => features.china_only, (v) => { if (v) features.non_china_only = false })
watch(() => features.non_china_only, (v) => { if (v) features.china_only = false })

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
    <div class="home-bg" aria-hidden="true" />

    <header class="top">
      <LangSwitch />
      <template v-if="user">
        <RouterLink class="admin-link" :to="user.role === 'admin' ? '/admin' : '/me'">
          {{ user.role === 'admin' ? t('common.admin') : t('common.account') }}
        </RouterLink>
        <button type="button" class="admin-link btn-link" @click="logout">{{ t('common.logout') }}</button>
      </template>
      <template v-else>
        <RouterLink class="admin-link" to="/login">{{ t('auth.login') }}</RouterLink>
        <RouterLink class="admin-link" to="/register">{{ t('auth.register') }}</RouterLink>
      </template>
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
            <strong>{{ item.title }}</strong>
            <span>{{ item.text }}</span>
          </li>
        </ul>
      </section>
    </main>
  </div>
</template>

<style scoped>
.home {
  position: relative;
  min-height: 100vh;
  color: var(--ink);
  overflow-x: clip;
}
.home-bg {
  position: fixed;
  inset: 0;
  z-index: 0;
  pointer-events: none;
  background:
    radial-gradient(1000px 520px at 8% -8%, rgba(15, 118, 110, 0.16), transparent 58%),
    radial-gradient(800px 480px at 100% 0%, rgba(12, 27, 36, 0.07), transparent 52%),
    linear-gradient(180deg, #e8eef2 0%, var(--paper) 42%, #e7eef3 100%);
}
.top,
.main {
  position: relative;
  z-index: 1;
}
.top {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem 1.25rem;
}
.admin-link {
  color: var(--ink-soft);
  text-decoration: none;
  font-weight: 600;
  font-size: 0.92rem;
}
.admin-link:hover {
  color: var(--accent);
}
.btn-link {
  border: 0;
  background: transparent;
  cursor: pointer;
  font: inherit;
  padding: 0;
}
.tip-line {
  margin: 0.35rem 0 0;
  font-size: 0.85rem;
}
.main {
  width: min(680px, 100%);
  margin: 0 auto;
  padding: 0.25rem 1.25rem 3.5rem;
}
.hero {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
  margin-bottom: 1.5rem;
  animation: rise 0.55s ease both;
}
.brand {
  display: block;
  margin: 0;
  padding: 0;
  font-family: Syne, Manrope, sans-serif;
  font-weight: 800;
  font-size: clamp(2.6rem, 9vw, 3.75rem);
  letter-spacing: -0.02em;
  line-height: 1.15;
  color: var(--ink);
}
.lead {
  display: block;
  margin: 0;
  max-width: 32rem;
  color: var(--ink-soft);
  font-size: 1.05rem;
  line-height: 1.55;
}
.panel {
  background: var(--paper-2);
  border: 1px solid var(--line);
  padding: 1.1rem;
  animation: rise 0.65s ease 0.05s both;
}
.url-row {
  display: flex;
  gap: 0.5rem;
}
.url-row input,
.extra input,
.extra textarea,
.result-row input {
  width: 100%;
  border: 1px solid var(--line);
  background: #f7fafb;
  color: var(--ink);
  padding: 0.75rem 0.85rem;
  font: inherit;
  outline: none;
}
.url-row input::placeholder,
.extra input::placeholder,
.extra textarea::placeholder {
  color: #7a8b97;
}
.url-row input:focus,
.extra input:focus,
.extra textarea:focus,
.result-row input:focus {
  border-color: var(--accent);
  background: #fff;
}
.url-row button,
.result-row button {
  flex: 0 0 auto;
  border: 0;
  background: var(--accent);
  color: #fff;
  padding: 0 1.15rem;
  font: inherit;
  font-weight: 700;
  cursor: pointer;
}
.url-row button:hover,
.result-row button:hover {
  filter: brightness(1.05);
}
.url-row button:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}
.result-row button.ghost {
  background: var(--ink);
}
.group {
  margin-top: 1.15rem;
}
.group-title {
  margin-bottom: 0.5rem;
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
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
  gap: 0.3rem;
  padding: 0.4rem 0.55rem 0.4rem 0.5rem;
  border: 1px solid var(--line);
  background: #f7fafb;
  color: var(--ink);
  font-size: 0.88rem;
  font-weight: 600;
  cursor: pointer;
  user-select: none;
}
.opt.on {
  border-color: var(--accent);
  background: var(--accent-soft);
  color: var(--ink);
}
.opt.locked {
  opacity: 0.55;
  cursor: not-allowed;
}
.opt .badge {
  font-size: 0.7rem;
  font-weight: 700;
  padding: 0.1rem 0.35rem;
  border-radius: 4px;
  background: rgba(12, 27, 36, 0.08);
  color: var(--ink-soft);
}
.opt input {
  margin: 0;
  accent-color: var(--accent);
}
.extra {
  margin-top: 0.9rem;
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
  margin-top: 1.1rem;
  padding-top: 1rem;
  border-top: 1px solid var(--line);
  animation: rise 0.35s ease both;
}
.result-label {
  margin-bottom: 0.35rem;
  font-size: 0.85rem;
  font-weight: 700;
  color: var(--ink);
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
  margin: 1.1rem 0 0;
  color: var(--ink-soft);
  font-size: 0.9rem;
  animation: rise 0.7s ease 0.08s both;
}
.about {
  margin-top: 2.25rem;
  padding-top: 1.5rem;
  border-top: 1px solid var(--line);
  animation: rise 0.75s ease 0.1s both;
}
.about h2 {
  margin: 0 0 0.55rem;
  font-family: Syne, Manrope, sans-serif;
  font-size: 1.25rem;
  line-height: 1.3;
  color: var(--ink);
}
.about-lead {
  margin: 0 0 1rem;
  color: var(--ink-soft);
  line-height: 1.6;
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
  gap: 0.15rem;
}
.about strong {
  color: var(--ink);
  font-size: 0.95rem;
}
.about span {
  color: var(--ink-soft);
  font-size: 0.9rem;
  line-height: 1.5;
}
@keyframes rise {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: none;
  }
}
@media (max-width: 640px) {
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
