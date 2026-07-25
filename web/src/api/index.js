const BASE = ''

function canUseStorage() {
  return typeof window !== 'undefined' && typeof window.localStorage !== 'undefined'
}

function getToken() {
  if (!canUseStorage()) return ''
  return localStorage.getItem('auth_token') || localStorage.getItem('admin_token') || ''
}

export function setAuth(data) {
  if (!data?.token || !canUseStorage()) return
  localStorage.setItem('auth_token', data.token)
  // keep admin_token for existing admin pages during transition
  localStorage.setItem('admin_token', data.token)
  if (data.user) localStorage.setItem('auth_user', JSON.stringify(data.user))
  if (data.plan) localStorage.setItem('auth_plan', JSON.stringify(data.plan))
}

export function clearAuth() {
  if (!canUseStorage()) return
  localStorage.removeItem('auth_token')
  localStorage.removeItem('admin_token')
  localStorage.removeItem('auth_user')
  localStorage.removeItem('auth_plan')
}

export function getStoredUser() {
  if (!canUseStorage()) return null
  try {
    return JSON.parse(localStorage.getItem('auth_user') || 'null')
  } catch {
    return null
  }
}

export function getStoredPlan() {
  if (!canUseStorage()) return null
  try {
    return JSON.parse(localStorage.getItem('auth_plan') || 'null')
  } catch {
    return null
  }
}

async function request(path, options = {}) {
  const headers = {
    Accept: 'application/json',
    ...(options.body ? { 'Content-Type': 'application/json' } : {}),
    ...options.headers,
  }
  const token = getToken()
  if (token) headers.Authorization = `Bearer ${token}`

  const res = await fetch(BASE + path, { ...options, headers })
  const data = await res.json().catch(() => ({}))
  if (data.code && data.code !== 200) {
    const err = new Error(data.msg || 'request failed')
    err.code = data.code
    err.data = data
    throw err
  }
  return data.data
}

export const api = {
  createLink(payload) {
    return request('/api/v1/links', { method: 'POST', body: JSON.stringify(payload) })
  },
  summary() {
    return request('/api/v1/links/summary')
  },
  plans() {
    return request('/api/v1/plans')
  },
  challengeVerify(payload) {
    return request('/api/v1/challenge/verify', {
      method: 'POST',
      body: JSON.stringify(payload),
      headers: { 'X-Challenge': '1' },
    })
  },
  challengeBoot(id) {
    return request(`/api/v1/challenge/${encodeURIComponent(id)}`)
  },

  register(username, password) {
    return request('/api/v1/auth/register', {
      method: 'POST',
      body: JSON.stringify({ username, password }),
    })
  },
  login(username, password) {
    return request('/api/v1/auth/login', {
      method: 'POST',
      body: JSON.stringify({ username, password }),
    })
  },
  me() {
    return request('/api/v1/auth/me')
  },

  meLinks({ page = 1, pageSize = 20, keyword = '' } = {}) {
    const q = new URLSearchParams({ page, page_size: pageSize, keyword })
    return request(`/api/v1/me/links?${q}`)
  },
  meLink(id) {
    return request(`/api/v1/me/links/${id}`)
  },
  meUpdateLink(id, payload) {
    return request(`/api/v1/me/links/${id}`, { method: 'PUT', body: JSON.stringify(payload) })
  },
  meDeleteLink(id) {
    return request(`/api/v1/me/links/${id}`, { method: 'DELETE' })
  },
  meVisits(id, page = 1, pageSize = 20) {
    return request(`/api/v1/me/links/${id}/visits?page=${page}&page_size=${pageSize}`)
  },

  adminLogin(username, password) {
    return request('/api/v1/admin/login', {
      method: 'POST',
      body: JSON.stringify({ username, password }),
    })
  },
  adminStats(days = 14) {
    return request(`/api/v1/admin/stats?days=${days}`)
  },
  adminLinks({ page = 1, pageSize = 20, keyword = '', userId } = {}) {
    const q = new URLSearchParams({ page, page_size: pageSize, keyword })
    if (userId != null && userId !== '') q.set('user_id', userId)
    return request(`/api/v1/admin/links?${q}`)
  },
  adminLink(id) {
    return request(`/api/v1/admin/links/${id}`)
  },
  adminDeleteLink(id) {
    return request(`/api/v1/admin/links/${id}`, { method: 'DELETE' })
  },
  adminVisits(id, page = 1, pageSize = 20) {
    return request(`/api/v1/admin/links/${id}/visits?page=${page}&page_size=${pageSize}`)
  },
  adminUsers({ page = 1, pageSize = 20, keyword = '' } = {}) {
    const q = new URLSearchParams({ page, page_size: pageSize, keyword })
    return request(`/api/v1/admin/users?${q}`)
  },
  adminUser(id) {
    return request(`/api/v1/admin/users/${id}`)
  },
  adminSetUserPlan(id, planId) {
    return request(`/api/v1/admin/users/${id}/plan`, {
      method: 'PUT',
      body: JSON.stringify({ plan_id: planId }),
    })
  },
  adminSetUserStatus(id, status) {
    return request(`/api/v1/admin/users/${id}/status`, {
      method: 'PUT',
      body: JSON.stringify({ status }),
    })
  },
  adminGetPlans() {
    return request('/api/v1/admin/plans')
  },
  adminSavePlans(cfg) {
    return request('/api/v1/admin/plans', { method: 'PUT', body: JSON.stringify(cfg) })
  },
  adminGetGuestLimits() {
    return request('/api/v1/admin/guest-limits')
  },
  adminSaveGuestLimits(cfg) {
    return request('/api/v1/admin/guest-limits', { method: 'PUT', body: JSON.stringify(cfg) })
  },
}
