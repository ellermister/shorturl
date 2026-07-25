export function stableStringify(value) {
  if (value === null || typeof value !== 'object') {
    return JSON.stringify(value)
  }
  if (Array.isArray(value)) {
    return `[${value.map(stableStringify).join(',')}]`
  }
  const keys = Object.keys(value).sort()
  return `{${keys.map((k) => `${JSON.stringify(k)}:${stableStringify(value[k])}`).join(',')}}`
}

export async function hmacHex(key, message) {
  const enc = new TextEncoder()
  const cryptoKey = await crypto.subtle.importKey(
    'raw',
    enc.encode(key),
    { name: 'HMAC', hash: 'SHA-256' },
    false,
    ['sign'],
  )
  const sig = await crypto.subtle.sign('HMAC', cryptoKey, enc.encode(message))
  return [...new Uint8Array(sig)].map((b) => b.toString(16).padStart(2, '0')).join('')
}

export async function collectFingerprint() {
  const canvas = (() => {
    try {
      const c = document.createElement('canvas')
      c.width = 240
      c.height = 60
      const ctx = c.getContext('2d')
      ctx.textBaseline = 'top'
      ctx.font = '14px Arial'
      ctx.fillStyle = '#f60'
      ctx.fillRect(0, 0, 100, 30)
      ctx.fillStyle = '#069'
      ctx.fillText('shorturl-fp', 2, 2)
      return c.toDataURL().slice(0, 128)
    } catch {
      return ''
    }
  })()

  let fonts = []
  try {
    if (document.fonts?.check) {
      const candidates = ['Arial', 'Times New Roman', 'Courier New', 'Georgia', 'Verdana', 'Hiragino Sans GB', 'Microsoft YaHei']
      fonts = candidates.filter((f) => document.fonts.check(`12px "${f}"`))
    }
  } catch {
    /* ignore */
  }

  let audio = ''
  try {
    const Ctx = window.OfflineAudioContext || window.webkitOfflineAudioContext
    if (Ctx) {
      const ctx = new Ctx(1, 8, 44100)
      audio = `${ctx.sampleRate}`
    }
  } catch {
    /* ignore */
  }

  return {
    platform: navigator.platform || '',
    ua: navigator.userAgent || '',
    language: navigator.language || '',
    timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || '',
    screen: {
      width: window.screen?.width || 0,
      height: window.screen?.height || 0,
      depth: window.screen?.colorDepth || 0,
    },
    max_touch_points: navigator.maxTouchPoints || 0,
    mobile_hint: !!navigator.userAgentData?.mobile,
    canvas,
    fonts,
    audio,
  }
}
