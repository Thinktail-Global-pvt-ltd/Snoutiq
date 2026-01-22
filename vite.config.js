import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";

export default defineConfig({
  base: "/", // keep as-is for dev
  plugins: [react()],

  server: {
    proxy: {
      // Laravel API
      "/api": {
        target: "http://127.0.0.1:8000",
        changeOrigin: true,
        secure: false,
      },

      // Echo/Reverb private auth (important)
      "/broadcasting/auth": {
        target: "http://127.0.0.1:8000",
        changeOrigin: true,
        secure: false,
      },

      // OPTIONAL: if your app uses Sanctum cookie csrf route
      "/sanctum/csrf-cookie": {
        target: "http://127.0.0.1:8000",
        changeOrigin: true,
        secure: false,
      },
    },
  },

  build: {
    target: "esnext",
    modulePreload: { polyfill: false },
    rollupOptions: {
      output: {
        entryFileNames: "assets/index.js",
        chunkFileNames: "assets/[name].js",
        assetFileNames: (assetInfo) => {
          if (assetInfo.name && assetInfo.name.endsWith(".css")) {
            return "assets/index.css";
          }
          return "assets/[name].[ext]";
        },
        manualChunks: {
          react: ["react", "react-dom"],
          vendor: ["react-router-dom"],
        },
      },
    },
  },

  optimizeDeps: {
    include: ["react", "react-dom"],
  },
});
