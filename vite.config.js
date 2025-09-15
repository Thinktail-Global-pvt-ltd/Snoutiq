import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import path from 'path'
import { fileURLToPath } from 'url'

const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)

export default defineConfig({
  plugins: [react()],
  resolve: {
    alias: {
      react: path.resolve(__dirname, 'node_modules/react'),
      'react-dom': path.resolve(__dirname, 'node_modules/react-dom')
    }
  },
  build: {
    target: 'esnext',
    modulePreload: { polyfill: false },
    rollupOptions: {
      output: {
        manualChunks(id) {
          if (id.includes('react') || id.includes('react-dom')) return 'react';
          if (id.includes('agora-react-uikit') || id.includes('agora-rtc-sdk-ng')) return 'agora';
          if (id.includes('chart.js')) return 'chart';
          if (id.includes('node_modules')) return 'vendor';
        }
      }
    }
  },
  optimizeDeps: {
    include: ['react', 'react-dom']
  }
})
