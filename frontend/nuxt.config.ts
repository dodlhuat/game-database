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
    '@nuxtjs/i18n',
  ],

  i18n: {
    locales: [{ code: 'de', file: 'de.json' }],
    defaultLocale: 'de',
    langDir: 'locales/',
    strategy: 'no_prefix',
  },

  app: {
    pageTransition: { name: 'page' },
    head: {
      htmlAttrs: { 'data-theme': 'dark' },
      script: [
        {
          // Reads saved theme from localStorage before first paint — prevents flash
          innerHTML: `(function(){try{var t=localStorage.getItem('theme');document.documentElement.setAttribute('data-theme',t==='light'?'light':t==='dark'?'dark':window.matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light');}catch(e){}})();`,
          tagPosition: 'head',
        },
      ],
      link: [
        {
          rel: 'stylesheet',
          href: 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0',
        },
      ],
    },
  },

  // SCSS global in alle Komponenten einbinden
  css: ['@dodlhuat/basix/css/style.scss', '~/assets/styles/index.scss'],

  vite: {
    css: {
      preprocessorOptions: {
        scss: {
          additionalData: (source: string, filepath: string) =>
            filepath.includes('node_modules')
              ? source
              : `@use 'sass:color';\n@use '@/assets/styles/variables' as *;\n${source}`,
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
