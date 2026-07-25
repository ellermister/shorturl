import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
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
