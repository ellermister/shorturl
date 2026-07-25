import { ViteSSG } from 'vite-ssg'
import './style.css'
import App from './App.vue'
import { routes, setupRouterGuards } from './router'
import { createAppI18n } from './i18n'

/**
 * ViteSSG entry: `pnpm build` prerenders routes listed in vite.config.js
 * `ssgOptions.includedRoutes`. Everything else stays client-only SPA.
 */
export const createApp = ViteSSG(
  App,
  { routes },
  ({ app, router, isClient }) => {
    app.use(createAppI18n())
    if (isClient) {
      setupRouterGuards(router)
    }
  },
)
