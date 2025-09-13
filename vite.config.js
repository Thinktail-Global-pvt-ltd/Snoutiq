import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [react()],
  build: {
    target: 'esnext',        // ✅ Modern browsers को direct ES6 दो
    modulePreload: { polyfill: false },
    rollupOptions: {
      output: {
        manualChunks: {
          react: ['react', 'react-dom'],
          vendor: ['react-router-dom']
        }
      }
    }
  }
})
