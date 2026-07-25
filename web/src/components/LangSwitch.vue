<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { SUPPORTED_LOCALES, setLocale, i18n } from '../i18n'

const { t, locale } = useI18n()

const current = computed(() => locale.value)

function onChange(e) {
  setLocale(i18n, e.target.value)
}
</script>

<template>
  <label class="lang">
    <span class="sr">{{ t('lang.label') }}</span>
    <select :value="current" :aria-label="t('lang.label')" @change="onChange">
      <option v-for="item in SUPPORTED_LOCALES" :key="item.code" :value="item.code">
        {{ t(item.labelKey) }}
      </option>
    </select>
  </label>
</template>

<style scoped>
.lang {
  display: inline-flex;
  align-items: center;
}
.sr {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  border: 0;
}
select {
  appearance: none;
  border: 1px solid var(--line);
  background: var(--paper-2);
  color: var(--ink);
  font: inherit;
  font-size: 0.85rem;
  font-weight: 600;
  padding: 0.35rem 1.6rem 0.35rem 0.55rem;
  background-image: linear-gradient(45deg, transparent 50%, var(--ink-soft) 50%),
    linear-gradient(135deg, var(--ink-soft) 50%, transparent 50%);
  background-position:
    calc(100% - 12px) 55%,
    calc(100% - 7px) 55%;
  background-size:
    5px 5px,
    5px 5px;
  background-repeat: no-repeat;
  cursor: pointer;
}
select:focus {
  outline: 2px solid var(--accent-soft);
  border-color: var(--accent);
}
</style>
