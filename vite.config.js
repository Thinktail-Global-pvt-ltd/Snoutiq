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
        entryFileNames: "assets/[name]-[hash].js",
        chunkFileNames: "assets/[name]-[hash].js",
        assetFileNames: "assets/[name]-[hash][extname]",
        manualChunks: (id) => {
          if (id.includes("node_modules/agora-rtc-sdk-ng")) {
            return "agora";
          }
          if (id.includes("node_modules/lucide-react")) {
            return "icons";
          }
          if (id.includes("node_modules/react-router-dom") || id.includes("node_modules/react-router")) {
            return "router-vendor";
          }
          if (id.includes("node_modules/react-dom") || id.includes("node_modules/react")) {
            return "react-vendor";
          }
          return undefined;
        },
      },
    },
  },

  optimizeDeps: {
    include: ["react", "react-dom"],
  },
});
