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
  {
    files: ['test/**/*.ts'],
    rules: {
      // A hook that returns a function gets it called as teardown — and
      // `() => mock.mockReset()` returns the mock itself, which then runs once
      // more after every test. Always give hooks a block body.
      'no-restricted-syntax': [
        'error',
        {
          selector:
            'CallExpression[callee.name=/^(before|after)(Each|All)$/] > ArrowFunctionExpression[expression=true]',
          message: 'Give test hooks a block body: () => { … }',
        },
      ],
    },
  },
  prettier
)
