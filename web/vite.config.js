import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

/**
 * Static pages (vite-ssg):
 * - Only paths returned by `includedRoutes` are prerendered to HTML at build time.
 * - Add more later, e.g. return ['/', '/about', '/pricing']
 * - Dynamic routes need concrete paths, e.g. ['/blog/hello', '/blog/world']
 * - Auth / admin / challenge stay client-only (do not list them here).
 */
export default defineConfig({
  plugins: [vue()],
  ssgOptions: {
    script: 'async',
    formatting: 'minify',
    includedRoutes() {
      return ['/']
    },
  },
  server: {
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:8080',
        changeOrigin: true,
      },
      // Must not use '/s' — it also matches '/src/*' and blanks the Vue app.
      '^/s/': {
        target: 'http://127.0.0.1:8080',
        changeOrigin: true,
      },
      '^/j/': {
        target: 'http://127.0.0.1:8080',
        changeOrigin: true,
      },
    },
  },
})
