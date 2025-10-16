import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  base: '/',  // base URL
  plugins: [react()],
  build: {
    target: 'esnext',
    modulePreload: { polyfill: false },
    rollupOptions: {
      output: {
        entryFileNames: 'assets/index.js',      
        chunkFileNames: 'assets/[name].js',
        assetFileNames: (assetInfo) => {
          // Fixed filename for main CSS
          if (assetInfo.name && assetInfo.name.endsWith('.css')) {
            return 'assets/index.css';
          }
          return 'assets/[name].[ext]';
        },
        manualChunks: {
          react: ['react', 'react-dom'],
          vendor: ['react-router-dom'],
        },
      },
    },
  },
  optimizeDeps: {
    include: ['react', 'react-dom'],
  },
});
