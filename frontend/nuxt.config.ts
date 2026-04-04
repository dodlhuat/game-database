// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },
  ssr: false,

  // Nuxt 4 setzt srcDir standardmäßig auf 'app/' — wir nutzen die Projektroot-Struktur
  srcDir: '.',
  dir: {
    app: 'app',
  },

  modules: [
    '@pinia/nuxt',
  ],

  // SCSS global in alle Komponenten einbinden
  css: ['@dodlhuat/basix/css/style.scss', '~/assets/styles/index.scss'],

  vite: {
    server: {
      watch: {
        usePolling: true,
        interval: 300,
      },
    },
    resolve: {
      alias: {
        // Package exports map doesn't include ./js/* — alias the whole js/ directory
        '@dodlhuat/basix/js': '/app/node_modules/@dodlhuat/basix/js',
      },
    },
  },

  // API Base URL aus .env
  runtimeConfig: {
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://localhost:8000/api',
    },
  },

  // Routen-Middleware
  routeRules: {
    '/admin/**': { ssr: false },
    '/dashboard/**': { ssr: false },
  },
})
