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
        handler(html, ctx) {
          const bundle = ctx.bundle || {};
          const homeChunk = Object.values(bundle).find(
            (asset) =>
              asset &&
              asset.type === "chunk" &&
              asset.name === "HomePage" &&
              typeof asset.fileName === "string",
          );
          const homePreloadFiles = [];
          const visitedHomeImports = new Set();

          const collectHomeImports = (fileName) => {
            if (!fileName || visitedHomeImports.has(fileName)) return;

            const chunk = bundle[fileName];
            if (!chunk || chunk.type !== "chunk") return;

            visitedHomeImports.add(fileName);
            homePreloadFiles.push(`/${fileName}`);
            chunk.imports.forEach(collectHomeImports);
          };

          collectHomeImports(homeChunk?.fileName);

          let transformedHtml = html.replace(
            /<link rel="stylesheet"([^>]*?)href="([^"]+\.css)"([^>]*)>/g,
            (_, preAttrs = "", href, postAttrs = "") =>
              `<link rel="preload" as="style"${preAttrs}href="${href}"${postAttrs} onload="this.onload=null;this.rel='stylesheet'">` +
              `<noscript><link rel="stylesheet"${preAttrs}href="${href}"${postAttrs}></noscript>`,
          );

          if (homePreloadFiles.length) {
            transformedHtml = transformedHtml.replace(
              "</head>",
              `<script>if(window.location.pathname==="/"){${JSON.stringify(
                homePreloadFiles,
              )}.forEach(function(href){var link=document.createElement("link");link.rel="modulepreload";link.href=href;link.crossOrigin="";document.head.appendChild(link);});}</script></head>`,
            );
          }

          return transformedHtml;
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
