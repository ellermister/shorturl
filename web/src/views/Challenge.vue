<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { api } from '../api'
import { collectFingerprint, hmacHex, stableStringify } from '../utils/challenge'

const { t } = useI18n()
const route = useRoute()

const boot = ref(null)
const password = ref('')
const whisper = ref('')
const error = ref('')
const loading = ref(false)
const phase = ref('loading') // loading | missing | ready | whisper
const track = ref([])
const collecting = ref(false)
const ticket = ref(null)

const needPassword = computed(() => !!boot.value?.need_password)
const collectTrack = computed(() => boot.value?.collect_track !== false)

function onMove(e) {
  if (!collecting.value) return
  const now = Date.now()
  const last = track.value[track.value.length - 1]
  if (last && now - last.t < 30) return
  track.value.push({
    x: Math.round(e.clientX || e.touches?.[0]?.clientX || 0),
    y: Math.round(e.clientY || e.touches?.[0]?.clientY || 0),
    t: now,
  })
  if (track.value.length > 80) track.value.shift()
}

onMounted(async () => {
  const id = String(route.query.c || '')
  if (!id) {
    phase.value = 'missing'
    return
  }
  try {
    boot.value = await api.challengeBoot(id)
    phase.value = 'ready'
    collecting.value = true
    window.addEventListener('mousemove', onMove, { passive: true })
    window.addEventListener('touchmove', onMove, { passive: true })
  } catch (e) {
    error.value = e.message || t('challenge.fail')
    phase.value = 'missing'
  }
})

onUnmounted(() => {
  window.removeEventListener('mousemove', onMove)
  window.removeEventListener('touchmove', onMove)
})

async function goJump(nonce, fpHash) {
  const sig = await hmacHex(fpHash, nonce)
  window.location.replace(`/j/${encodeURIComponent(boot.value.code)}?sig=${sig}&n=${encodeURIComponent(nonce)}`)
}

async function submit() {
  if (!boot.value) return
  error.value = ''
  loading.value = true
  try {
    const fingerprint = await collectFingerprint()
    const clientHash = await hmacHex(boot.value.seed, stableStringify(fingerprint))
    const data = await api.challengeVerify({
      challenge_id: boot.value.challenge_id,
      code: boot.value.code,
      password: password.value,
      fingerprint,
      client_hash: clientHash,
      mouse_track: collectTrack.value ? track.value : [],
    })
    if (data?.action === 'fake' && data.url) {
      window.location.replace(data.url)
      return
    }
    const canon = stableStringify(fingerprint)
    const fpHash = await sha256Hex(canon)
    if (data.whisper) {
      whisper.value = data.whisper
      ticket.value = { nonce: data.nonce, fpHash }
      phase.value = 'whisper'
      return
    }
    await goJump(data.nonce, fpHash)
  } catch (e) {
    error.value = e.message || t('challenge.fail')
  } finally {
    loading.value = false
  }
}

async function continueWhisper() {
  if (!ticket.value) {
    error.value = t('challenge.fail')
    return
  }
  loading.value = true
  try {
    await goJump(ticket.value.nonce, ticket.value.fpHash)
  } finally {
    loading.value = false
  }
}

async function sha256Hex(text) {
  const buf = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(text))
  return [...new Uint8Array(buf)].map((b) => b.toString(16).padStart(2, '0')).join('')
}
</script>

<template>
  <div class="wrap">
    <div v-if="phase === 'loading'" class="panel">
      <p>{{ t('common.loading') }}</p>
    </div>

    <div v-else-if="phase === 'missing'" class="panel">
      <p>{{ error || t('challenge.missing') }}</p>
      <RouterLink to="/">{{ t('common.backHome') }}</RouterLink>
    </div>

    <div v-else-if="phase === 'whisper'" class="panel">
      <h1>{{ t('challenge.whisperTitle') }}</h1>
      <div class="whisper">{{ whisper }}</div>
      <button type="button" :disabled="loading" @click="continueWhisper">
        {{ loading ? t('challenge.working') : t('challenge.continue') }}
      </button>
    </div>

    <div v-else class="panel">
      <h1>{{ t('challenge.title') }}</h1>
      <p class="hint">{{ t('challenge.hint') }}</p>
      <label v-if="needPassword">
        <span>{{ t('challenge.password') }}</span>
        <input v-model="password" type="password" autocomplete="off" />
      </label>
      <p v-if="error" class="err">{{ error }}</p>
      <button type="button" :disabled="loading" @click="submit">
        {{ loading ? t('challenge.working') : t('challenge.continue') }}
      </button>
    </div>
  </div>
</template>

<style scoped>
.wrap {
  min-height: 100vh;
  display: grid;
  place-items: center;
  padding: 1.5rem;
  background: linear-gradient(180deg, #e8eef2, #f7fafc);
}
.panel {
  width: min(420px, 100%);
  display: grid;
  gap: 0.85rem;
  padding: 1.25rem 1.35rem;
  border: 1px solid #d5dee5;
  border-radius: 14px;
  background: #fff;
}
h1 {
  margin: 0;
  font-family: Syne, Manrope, sans-serif;
  font-size: 1.35rem;
}
.hint { margin: 0; color: #5b6b76; font-size: 0.92rem; }
label { display: grid; gap: 0.35rem; }
input {
  padding: 0.55rem 0.7rem;
  border: 1px solid #cfd8df;
  border-radius: 10px;
  font: inherit;
}
button {
  padding: 0.65rem 1rem;
  border: 0;
  border-radius: 10px;
  background: #0f766e;
  color: #fff;
  font-weight: 650;
  cursor: pointer;
}
button:disabled { opacity: 0.65; }
.err { color: #b91c1c; margin: 0; }
.whisper {
  white-space: pre-wrap;
  line-height: 1.55;
  padding: 0.75rem;
  background: #f3f7f9;
  border-radius: 10px;
}
</style>
