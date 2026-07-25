import { createI18n } from 'vue-i18n'
import zhCN from './locales/zh-CN'
import en from './locales/en'
import ja from './locales/ja'

export const SUPPORTED_LOCALES = [
  { code: 'zh-CN', labelKey: 'lang.zhCN' },
  { code: 'en', labelKey: 'lang.en' },
  { code: 'ja', labelKey: 'lang.ja' },
]

const STORAGE_KEY = 'shorturl_locale'

export function detectLocale() {
  const saved = localStorage.getItem(STORAGE_KEY)
  if (saved && SUPPORTED_LOCALES.some((l) => l.code === saved)) {
    return saved
  }

  const candidates = [...(navigator.languages || []), navigator.language].filter(Boolean)
  for (const raw of candidates) {
    const lang = String(raw).toLowerCase()
    if (lang.startsWith('zh')) return 'zh-CN'
    if (lang.startsWith('ja')) return 'ja'
    if (lang.startsWith('en')) return 'en'
  }
  return 'en'
}

export function setLocale(i18n, code) {
  if (!SUPPORTED_LOCALES.some((l) => l.code === code)) return
  i18n.global.locale.value = code
  localStorage.setItem(STORAGE_KEY, code)
  document.documentElement.lang = code
}

export const i18n = createI18n({
  legacy: false,
  locale: detectLocale(),
  fallbackLocale: 'en',
  messages: {
    'zh-CN': zhCN,
    en,
    ja,
  },
})

document.documentElement.lang = i18n.global.locale.value
