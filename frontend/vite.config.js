import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    react(),
    tailwindcss(),
  ],
  base: '/frontend/files/',   // IMPORTANT - यह line add की है
  define: {
    global: {},
  },
  resolve: {
    alias: {
      '@mui/styled-engine': '@mui/styled-engine-sc',
    },
  },
  server: {
    hmr: {
      overlay: false
    }
  },
  build: {
    outDir: 'dist'  // यह line भी add की है
  }
})