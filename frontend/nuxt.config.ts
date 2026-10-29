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
      link: [
        { rel: 'icon', href: '/favicon.ico', sizes: '48x48' },
        { rel: 'icon', type: 'image/png', sizes: '16x16', href: '/favicon-16x16.png' },
        { rel: 'icon', type: 'image/png', sizes: '32x32', href: '/favicon-32x32.png' },
        { rel: 'icon', type: 'image/png', sizes: '48x48', href: '/favicon-48x48.png' },
        { rel: 'apple-touch-icon', sizes: '180x180', href: '/apple-touch-icon.png' },
        { rel: 'manifest', href: '/site.webmanifest' },
      ],
      meta: [{ name: 'theme-color', content: '#0b0b0b' }],
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
      // Wartungsmodus: NUXT_PUBLIC_MAINTENANCE_MODE=true leitet alle Routen
      // auf die Startseite um, die dann die Wartungsansicht zeigt. Nuxt
      // überschreibt den Wert zur Laufzeit aus der Umgebung — kein Rebuild nötig.
      maintenanceMode: false,
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://localhost:8000/api',
      // Publishable Key ist per Design öffentlich (wird an Stripe.js
      // übergeben) — kein Secret. Test-/Live-Umschaltung passiert rein über
      // diesen Wert (pk_test_... vs. pk_live_...).
      stripePublishableKey: process.env.NUXT_PUBLIC_STRIPE_PUBLISHABLE_KEY || '',
    },
  },
})
