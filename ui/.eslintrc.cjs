module.exports = {
  root: true,
  env: {
    browser: true,
    node: true,
  },
  parser: 'vue-eslint-parser',
  parserOptions: {
    parser: '@typescript-eslint/parser',
  },
  extends: ['@nuxtjs/eslint-config-typescript', 'plugin:prettier/recommended'],
  plugins: [],
  rules: {
    // Block raw console.log; allow warn/error.
    // Wrap dev-only debug logs in if (import.meta.dev) or
    // add // eslint-disable-next-line no-console on the line.
    'no-console': ['warn', { allow: ['warn', 'error'] }],
  },
  ignorePatterns: ['public/js/**'],
}
