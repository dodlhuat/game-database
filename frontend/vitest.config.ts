import { defineVitestConfig } from '@nuxt/test-utils/config'

export default defineVitestConfig({
  test: {
    environment: 'nuxt',
    // Only measured with `npm run test:coverage`; the report lands in
    // coverage/ (ignored by git) — open coverage/index.html to browse it
    coverage: {
      provider: 'v8',
      include: [
        'pages/**',
        'components/**',
        'composables/**',
        'utils/**',
        'stores/**',
        'middleware/**',
      ],
      reporter: ['text-summary', 'html', 'json-summary'],
      reportsDirectory: 'coverage',
    },
  },
})
