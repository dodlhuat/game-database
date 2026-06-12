// @ts-check
import withNuxt from './.nuxt/eslint.config.mjs'
import prettier from 'eslint-config-prettier'

export default withNuxt(
  {
    rules: {
      // Vue 3 allows multiple root elements — Teleport alongside main root is valid
      'vue/no-multiple-template-root': 'off',
      // Optional TS props don't need explicit undefined defaults
      'vue/require-default-prop': 'off',
      // Allow ternary statements like `cond ? a() : b()` and short-circuit `cond && fn()`
      '@typescript-eslint/no-unused-expressions': [
        'error',
        { allowTernary: true, allowShortCircuit: true },
      ],
    },
  },
  prettier
)
