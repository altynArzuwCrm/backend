import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
// import vueDevTools from 'vite-plugin-vue-devtools' // Отключено для удаления Service Worker

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue()], // Убрали vueDevTools()
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        secure: false,
      },
    },
    hmr: {
      overlay: false, // Отключаем overlay ошибок
      port: 24678, // Фиксированный порт для HMR
    },
    watch: {
      usePolling: true, // Для стабильности на некоторых системах
    },
  },
  optimizeDeps: {
    include: [
      'vue', 
      'vue-router', 
      'pinia',
      'axios',
      '@heroicons/vue',
      '@tabler/icons-vue',
      '@vueform/multiselect',
      '@vuepic/vue-datepicker',
      'chart.js',
      'sortablejs'
    ],
    force: true, // Принудительная пересборка зависимостей
  },
  build: {
    target: 'es2015',
    minify: 'terser',
    terserOptions: {
      compress: {
        drop_console: true,
        drop_debugger: true,
      },
    },
    rollupOptions: {
      output: {
        manualChunks: {
          vendor: ['vue', 'vue-router', 'pinia'],
          ui: ['@heroicons/vue', '@tabler/icons-vue'],
          charts: ['chart.js'],
          forms: ['@vueform/multiselect', '@vuepic/vue-datepicker'],
        },
      },
    },
    chunkSizeWarningLimit: 1000,
  },
  // Настройки для стабильности разработки
  define: {
    __VUE_OPTIONS_API__: true,
    __VUE_PROD_DEVTOOLS__: false,
    'process.env.NODE_ENV': '"development"',
  },
  // Кэширование для стабильности
  cacheDir: 'node_modules/.vite',
})
