import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [react()],
  build: {
    target: 'esnext',
    modulePreload: { polyfill: false },
    rollupOptions: {
      output: {
        manualChunks(id) {
          if (id.includes('react')) return 'react';
          if (id.includes('agora-react-uikit') || id.includes('agora-rtc-sdk-ng')) return 'agora';
          if (id.includes('chart.js')) return 'chart';
          if (id.includes('node_modules')) return 'vendor';
        }
      },
      onwarn(warning, warn) {
        if (warning.code === 'EVAL') return;
        warn(warning);
      }
    }
  }
})
