import Home from '../views/Home.vue'
import Auth from '../views/Auth.vue'
import Challenge from '../views/Challenge.vue'
import AdminLogin from '../views/admin/Login.vue'
import AdminLayout from '../views/admin/Layout.vue'
import AdminDashboard from '../views/admin/Dashboard.vue'
import AdminLinks from '../views/admin/Links.vue'
import AdminLinkDetail from '../views/admin/LinkDetail.vue'
import AdminUsers from '../views/admin/Users.vue'
import AdminUserDetail from '../views/admin/UserDetail.vue'
import AdminPlans from '../views/admin/Plans.vue'
import AdminGuestLimits from '../views/admin/GuestLimits.vue'
import MeLayout from '../views/me/Layout.vue'
import MeHome from '../views/me/Home.vue'
import MeLinks from '../views/me/Links.vue'
import MeLinkDetail from '../views/me/LinkDetail.vue'
import { getStoredUser } from '../api'

/** Route table shared by ViteSSG (build) and the client router. */
export const routes = [
  { path: '/', name: 'home', component: Home },
  { path: '/challenge', name: 'challenge', component: Challenge },
  { path: '/login', name: 'login', component: Auth, props: { mode: 'login' } },
  { path: '/register', name: 'register', component: Auth, props: { mode: 'register' } },
  { path: '/admin/login', name: 'admin-login', component: AdminLogin },
  {
    path: '/admin',
    component: AdminLayout,
    meta: { auth: true, role: 'admin' },
    children: [
      { path: '', name: 'admin-dashboard', component: AdminDashboard },
      { path: 'links', name: 'admin-links', component: AdminLinks },
      { path: 'links/:id', name: 'admin-link-detail', component: AdminLinkDetail },
      { path: 'users', name: 'admin-users', component: AdminUsers },
      { path: 'users/:id', name: 'admin-user-detail', component: AdminUserDetail },
      { path: 'plans', name: 'admin-plans', component: AdminPlans },
      { path: 'guest-limits', name: 'admin-guest-limits', component: AdminGuestLimits },
    ],
  },
  {
    path: '/me',
    component: MeLayout,
    meta: { auth: true },
    children: [
      { path: '', name: 'me-home', component: MeHome },
      { path: 'links', name: 'me-links', component: MeLinks },
      { path: 'links/:id', name: 'me-link-detail', component: MeLinkDetail },
    ],
  },
]

export function setupRouterGuards(router) {
  router.beforeEach((to) => {
    if (typeof window === 'undefined') return true
    if (!to.matched.some((r) => r.meta.auth)) return true
    const token = localStorage.getItem('auth_token') || localStorage.getItem('admin_token')
    if (!token) {
      const loginName = to.matched.some((r) => r.meta.role === 'admin') ? 'admin-login' : 'login'
      return { name: loginName, query: { redirect: to.fullPath } }
    }
    if (to.matched.some((r) => r.meta.role === 'admin')) {
      const user = getStoredUser()
      if (user && user.role !== 'admin') return { name: 'me-home' }
    }
    return true
  })
}
