import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [react({
    jsxRuntime: 'classic' // Add this to handle React compatibility
  })],
  build: {
    target: 'esnext',
    modulePreload: { polyfill: false },
    rollupOptions: {
      output: {
        manualChunks(id) {
          if (id.includes('react') && !id.includes('react-dom')) return 'react';
          if (id.includes('react-dom')) return 'react-dom';
          if (id.includes('agora-react-uikit') || id.includes('agora-rtc-sdk-ng')) return 'agora';
          if (id.includes('chart.js')) return 'chart';
          if (id.includes('node_modules')) return 'vendor';
        }
      },
      onwarn(warning, warn) {
        if (warning.code === 'EVAL') return;
        if (warning.code === 'THIS_IS_UNDEFINED') return; // Add this
        warn(warning);
      }
    }
  },
  optimizeDeps: {
    include: ['react', 'react-dom'] // Ensure these are optimized
  }
})