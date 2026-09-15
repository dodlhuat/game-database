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

  modules: ['@nuxt/eslint', '@pinia/nuxt', '@nuxtjs/i18n'],

  i18n: {
    locales: [{ code: 'de', file: 'de.json' }],
    defaultLocale: 'de',
    langDir: 'locales/',
    strategy: 'no_prefix',
  },

  app: {
    pageTransition: { name: 'page' },
    head: {
      // No static data-theme default here on purpose: Nuxt's head manager
      // re-asserts a statically configured htmlAttrs value during client
      // hydration, which was silently stomping whatever the inline script
      // below (and the theme toggle) had already set — light mode could
      // never actually stick. The inline script alone (sync, runs before
      // first paint) is enough to avoid a flash of the wrong theme.
      script: [
        {
          // Reads saved theme from localStorage before first paint — prevents flash
          innerHTML: `(function(){try{var t=localStorage.getItem('theme');document.documentElement.setAttribute('data-theme',t==='light'?'light':t==='dark'?'dark':window.matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light');}catch(e){}})();`,
          tagPosition: 'head',
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
      // Client-ID ist per Design öffentlich (steht im JS-SDK-Script-Tag) —
      // kein Secret. Sandbox-/Live-Umschaltung passiert rein über diesen Wert.
      paypalClientId: process.env.NUXT_PUBLIC_PAYPAL_CLIENT_ID || '',
      paypalCurrency: process.env.NUXT_PUBLIC_PAYPAL_CURRENCY || 'EUR',
    },
  },
})
