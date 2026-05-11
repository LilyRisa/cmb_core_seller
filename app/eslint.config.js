// Flat config (ESLint 9). Lints the React/TS SPA in resources/js.
// See docs/06-frontend/overview.md and docs/09-process/coding-standards.md §3.
import js from '@eslint/js';
import globals from 'globals';
import tseslint from 'typescript-eslint';
import reactHooks from 'eslint-plugin-react-hooks';
import reactRefresh from 'eslint-plugin-react-refresh';

export default tseslint.config(
    { ignores: ['public/build', 'public/hot', 'node_modules', 'vendor', 'bootstrap', 'storage'] },
    {
        files: ['resources/js/**/*.{ts,tsx}'],
        extends: [js.configs.recommended, ...tseslint.configs.recommended],
        languageOptions: {
            ecmaVersion: 2022,
            globals: { ...globals.browser, ...globals.es2022 },
        },
        plugins: {
            'react-hooks': reactHooks,
            'react-refresh': reactRefresh,
        },
        rules: {
            ...reactHooks.configs.recommended.rules,
            'react-refresh/only-export-components': ['warn', { allowConstantExport: true }],
            'no-console': ['warn', { allow: ['warn', 'error'] }],
            '@typescript-eslint/no-unused-vars': ['error', { argsIgnorePattern: '^_', varsIgnorePattern: '^_' }],
            // tsc already enforces typing; keep ESLint focused on lint smells, not type strictness.
            '@typescript-eslint/no-explicit-any': 'off',
        },
    },
);
