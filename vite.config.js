import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [react()],
  build: {
    target: 'esnext', // Modern browsers ke liye
    modulePreload: { polyfill: false },
    rollupOptions: {
      output: {
        manualChunks(id) {
          if (id.includes('react')) return 'react'             // react & react-dom
          if (id.includes('agora-react-uikit') || id.includes('agora-rtc-sdk-ng')) return 'agora' // heavy video call lib
          if (id.includes('chart.js')) return 'chart'          // dashboard charts
          if (id.includes('node_modules')) return 'vendor'     // baki libraries
        }
      },
      onwarn(warning, warn) {
        // Ignore eval warnings from Agora SDK
        if (warning.code === 'EVAL') return
        warn(warning)
      }
    }
  }
})
