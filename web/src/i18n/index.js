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
  if (typeof window === 'undefined') {
    return 'zh-CN'
  }

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

/** Accepts either the i18n instance or the object returned by `useI18n()`. */
export function setLocale(i18nOrComposer, code) {
  if (!SUPPORTED_LOCALES.some((l) => l.code === code)) return
  if (i18nOrComposer?.global?.locale) {
    i18nOrComposer.global.locale.value = code
  } else if (i18nOrComposer?.locale) {
    i18nOrComposer.locale.value = code
  }
  if (typeof window !== 'undefined') {
    localStorage.setItem(STORAGE_KEY, code)
    document.documentElement.lang = code
  }
}

export function createAppI18n() {
  const locale = detectLocale()
  const i18n = createI18n({
    legacy: false,
    locale,
    fallbackLocale: 'en',
    messages: {
      'zh-CN': zhCN,
      en,
      ja,
    },
  })
  if (typeof window !== 'undefined') {
    document.documentElement.lang = locale
  }
  return i18n
}
