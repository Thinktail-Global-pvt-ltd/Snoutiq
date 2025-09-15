import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  build: {
    target: 'esnext',
    outDir: 'dist',
    assetsDir: 'assets',
    sourcemap: false,
    rollupOptions: {
      output: {
        chunkFileNames: 'assets/[name]-[hash].js',
        entryFileNames: 'assets/[name]-[hash].js',
        // manualChunks(id) {
        //   if (id.includes('node_modules')) {
        //     return 'vendor'; // node_modules ek hi chunk me
        //   }
        //   // pages folder ke components ko alag chunks me
        //   if (id.includes('pages/Home')) {
        //     return 'home';
        //   }
        //   if (id.includes('pages/Login')) {
        //     return 'login';
        //   }
        // }
        manualChunks(id) {
  if (id.includes('react')) return 'react-vendor';
  if (id.includes('node_modules')) return 'vendor';
  if (id.includes('pages/Home')) return 'home';
  if (id.includes('pages/Login')) return 'login';
}

      }
    }
  }
});
