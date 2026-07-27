<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import Tip from './Tip.vue'
import { CN_PROVINCES, COUNTRIES, ISP_OPTIONS, emptyGeoRule, countryLabel } from '../data/geoRegions'

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({ require: '', fallback_url: '', rules: [] }),
  },
})

const emit = defineEmits(['update:modelValue'])
const { t, locale } = useI18n()

const policy = computed({
  get: () => props.modelValue || { require: '', fallback_url: '', rules: [] },
  set: (v) => emit('update:modelValue', v),
})

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

const advanced = computed({
  get: () => !!(policy.value.rules && policy.value.rules.length),
  set: (on) => {
    if (on) {
      if (!policy.value.rules?.length) {
        patch({ rules: [emptyGeoRule()] })
      }
    } else {
      patch({ rules: [] })
    }
  },
})

const showFallback = computed(
  () => advanced.value || policy.value.require === 'mainland' || policy.value.require === 'overseas',
)

function patch(partial) {
  emit('update:modelValue', { ...policy.value, ...partial })
}

function setRequire(kind) {
  const cur = policy.value.require
  if (kind === 'mainland') {
    patch({ require: cur === 'mainland' ? '' : 'mainland' })
  } else if (kind === 'overseas') {
    patch({ require: cur === 'overseas' ? '' : 'overseas' })
  }
}

function updateRule(idx, field, value) {
  const rules = (policy.value.rules || []).map((r, i) =>
    i === idx ? { ...r, [field]: value } : r,
  )
  patch({ rules })
}

function onCountryChange(idx, country) {
  const rule = { ...(policy.value.rules || [])[idx], country }
  if (country && country !== 'CN' && CN_PROVINCES.includes(rule.province)) {
    rule.province = ''
  }
  const rules = (policy.value.rules || []).map((r, i) => (i === idx ? rule : r))
  patch({ rules })
}

function usesCnProvinceSelect(rule) {
  return !rule.country || rule.country === 'CN'
}

function addRule() {
  patch({ rules: [...(policy.value.rules || []), emptyGeoRule()] })
}

function removeRule(idx) {
  const cur = policy.value.rules || []
  if (cur.length <= 1) {
    patch({ rules: [emptyGeoRule()] })
    return
  }
  patch({ rules: cur.filter((_, i) => i !== idx) })
}
</script>

<template>
  <div class="geo-editor">
    <div class="opts">
      <label class="opt" :class="{ on: policy.require === 'mainland' }" @click.prevent="setRequire('mainland')">
        <input :checked="policy.require === 'mainland'" type="checkbox" readonly />
        <span>{{ t('home.features.china_only.label') }}</span>
        <Tip :text="t('home.features.china_only.tip')" />
      </label>
      <label class="opt" :class="{ on: policy.require === 'overseas' }" @click.prevent="setRequire('overseas')">
        <input :checked="policy.require === 'overseas'" type="checkbox" readonly />
        <span>{{ t('home.features.non_china_only.label') }}</span>
        <Tip :text="t('home.features.non_china_only.tip')" />
      </label>
    </div>

    <label class="geo-toggle">
      <input v-model="advanced" type="checkbox" />
      <span>{{ t('home.geo.advanced') }}</span>
      <Tip :text="t('home.geo.advancedTip')" />
    </label>

    <div v-if="advanced" class="geo-panel">
      <p class="muted tip-line">{{ t('home.geo.rulesHint') }}</p>
      <div v-for="(rule, idx) in (policy.rules || [])" :key="idx" class="geo-rule">
        <select :value="rule.country || ''" @change="onCountryChange(idx, $event.target.value)">
          <option value="">{{ t('home.geo.anyCountry') }}</option>
          <option v-for="c in countryOptions" :key="c.code" :value="c.code">{{ c.label }}</option>
        </select>
        <select
          v-if="usesCnProvinceSelect(rule)"
          :value="rule.province || ''"
          @change="updateRule(idx, 'province', $event.target.value)"
        >
          <option value="">{{ t('home.geo.anyProvince') }}</option>
          <option v-for="p in CN_PROVINCES" :key="p" :value="p">{{ p }}</option>
        </select>
        <input
          v-else
          :value="rule.province || ''"
          type="text"
          :placeholder="t('home.geo.provincePh')"
          autocomplete="off"
          @input="updateRule(idx, 'province', $event.target.value)"
        />
        <input
          :value="rule.city || ''"
          type="text"
          :placeholder="t('home.geo.cityPh')"
          autocomplete="off"
          @input="updateRule(idx, 'city', $event.target.value)"
        />
        <select :value="rule.isp || ''" @change="updateRule(idx, 'isp', $event.target.value)">
          <option v-for="o in ispOptions" :key="o.value || 'any'" :value="o.value">{{ o.label }}</option>
        </select>
        <input
          :value="rule.url || ''"
          type="url"
          :placeholder="t('home.geo.ruleUrlPh')"
          autocomplete="off"
          @input="updateRule(idx, 'url', $event.target.value)"
        />
        <button type="button" class="ghost geo-rm" @click="removeRule(idx)">
          {{ t('home.geo.remove') }}
        </button>
      </div>
      <button type="button" class="ghost geo-add" @click="addRule">
        {{ t('home.geo.addRule') }}
      </button>
    </div>

    <div v-if="showFallback" class="extra">
      <label>
        {{ t('home.geo.fallback') }}
        <span class="muted">{{ t('home.geo.fallbackHint') }}</span>
      </label>
      <input
        :value="policy.fallback_url || ''"
        type="url"
        :placeholder="t('home.geo.fallbackPh')"
        autocomplete="off"
        @input="patch({ fallback_url: $event.target.value })"
      />
    </div>
  </div>
</template>

<style scoped>
.geo-editor {
  display: grid;
  gap: 0.65rem;
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
  border: 1px solid var(--line, #e5e7eb);
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
.geo-toggle {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.88rem;
  font-weight: 650;
  cursor: pointer;
  user-select: none;
}
.geo-toggle input {
  margin: 0;
  accent-color: #2563eb;
}
.geo-panel {
  display: grid;
  gap: 0.55rem;
}
.tip-line {
  margin: 0;
  font-size: 0.85rem;
}
.muted {
  color: #6b7280;
  font-weight: 500;
}
.geo-rule {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
  gap: 0.4rem;
}
.geo-rule select,
.geo-rule input,
.extra input {
  width: 100%;
  border: 1px solid var(--line, #e5e7eb);
  border-radius: 8px;
  background: #f9fafb;
  color: #111827;
  padding: 0.55rem 0.65rem;
  font: inherit;
  outline: none;
}
.geo-rule input[type='url'] {
  grid-column: 1 / -1;
}
.geo-rm,
.geo-add {
  border: 1px solid var(--line, #e5e7eb);
  border-radius: 8px;
  background: #fff;
  color: #6b7280;
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
.extra {
  display: grid;
  gap: 0.35rem;
}
.extra label {
  font-size: 0.88rem;
  font-weight: 650;
}
@media (min-width: 720px) {
  .geo-rule {
    grid-template-columns: 8.5rem 7rem 6rem 7rem minmax(0, 1fr) auto;
    align-items: center;
  }
  .geo-rule input[type='url'],
  .geo-rm {
    grid-column: auto;
  }
}
</style>
