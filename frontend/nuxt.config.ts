// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },

  modules: [
    '@pinia/nuxt',
  ],

  // SCSS global in alle Komponenten einbinden
  css: ['~/assets/styles/index.scss'],

  vite: {
    css: {
      preprocessorOptions: {
        scss: {
          additionalData: `
            @use "~/assets/styles/variables" as *;
            @use "~/assets/styles/mixins" as *;
          `,
        },
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
