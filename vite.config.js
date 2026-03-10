import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";

export default defineConfig({
  base: "/", // keep as-is for dev
  plugins: [
    react(),
    {
      name: "async-entry-css",
      apply: "build",
      transformIndexHtml: {
        order: "post",
        handler(html) {
          return html.replace(
            /<link rel="stylesheet"([^>]*?)href="([^"]+\.css)"([^>]*)>/g,
            (_, preAttrs = "", href, postAttrs = "") =>
              `<link rel="preload" as="style"${preAttrs}href="${href}"${postAttrs} onload="this.onload=null;this.rel='stylesheet'">` +
              `<noscript><link rel="stylesheet"${preAttrs}href="${href}"${postAttrs}></noscript>`,
          );
        },
      },
    },
  ],

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
      },
    },
  },

  optimizeDeps: {
    include: ["react", "react-dom"],
  },
});
