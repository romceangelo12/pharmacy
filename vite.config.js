import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

// https://vitejs.dev/config/
export default defineConfig(({ command }) => ({
  plugins: [react()],
  base: command === 'build' ? '/pharmacy/dist/' : '/',
  server: {
    port: 3000,
    proxy: {
      '/api': {
        target: 'http://localhost/pharmacy',
        changeOrigin: true,
      },
    },
  },
}));
