import { defineConfig } from 'vitest/config';
import { fileURLToPath } from 'url';
import path from 'path';
import vue from '@vitejs/plugin-vue';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

export default defineConfig({
  plugins: [vue()],
  test: {
    environment: 'happy-dom',
    globals: true,
    setupFiles: ['./vitest.setup.js'],
    exclude: [
      '**/node_modules/**',
      '**/dist/**',
      '**/vendor/**',
    ],
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './extensions/mailer-module/assets/js'),
      'vue': 'vue/dist/vue.esm-bundler.js'
    },
    extensions: ['.js', '.vue', '.json']
  }
});
