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
          const collectChunkPreloads = (chunkNames = []) => {
            const moduleFiles = [];
            const styleFiles = [];
            const visitedImports = new Set();

            const collectImports = (fileName) => {
              if (!fileName || visitedImports.has(fileName)) return;

              const chunk = bundle[fileName];
              if (!chunk || chunk.type !== "chunk") return;

              visitedImports.add(fileName);
              moduleFiles.push(`/${fileName}`);

              chunk.viteMetadata?.importedCss?.forEach((cssFileName) => {
                const href = `/${cssFileName}`;
                if (!styleFiles.includes(href)) {
                  styleFiles.push(href);
                }
              });

              chunk.imports.forEach(collectImports);
            };

            chunkNames.forEach((chunkName) => {
              const chunk = Object.values(bundle).find(
                (asset) =>
                  asset &&
                  asset.type === "chunk" &&
                  asset.name === chunkName &&
                  typeof asset.fileName === "string",
              );

              collectImports(chunk?.fileName);
            });

            return {
              moduleFiles,
              styleFiles,
            };
          };

          const homePreloads = collectChunkPreloads(["MainLayout", "HomePage"]);
          const vetNearMePreloadsByPath = {
            "/vet-at-home-gurgaon": collectChunkPreloads([
              "VetNearMeBookingLayout",
              "VetNearMeLeadPage",
            ]),
            "/vet-near-me-delhi-ncr": collectChunkPreloads([
              "VetNearMeBookingLayout",
              "VetNearMeLeadPage",
            ]),
            "/vet-at-home-gurgaon/pet-details": collectChunkPreloads([
              "VetNearMeBookingLayout",
              "VetNearMePetDetailsPage",
            ]),
            "/vet-near-me-delhi-ncr/pet-details": collectChunkPreloads([
              "VetNearMeBookingLayout",
              "VetNearMePetDetailsPage",
            ]),
            "/vet-at-home-gurgaon/payment": collectChunkPreloads([
              "VetNearMeBookingLayout",
              "VetNearMePaymentPage",
            ]),
            "/vet-near-me-delhi-ncr/payment": collectChunkPreloads([
              "VetNearMeBookingLayout",
              "VetNearMePaymentPage",
            ]),
            "/vet-at-home-gurgaon/success": collectChunkPreloads([
              "VetNearMeBookingLayout",
              "VetNearMeSuccessPage",
            ]),
            "/vet-near-me-delhi-ncr/success": collectChunkPreloads([
              "VetNearMeBookingLayout",
              "VetNearMeSuccessPage",
            ]),
          };

          let transformedHtml = html.replace(
            /<link rel="stylesheet"([^>]*?)href="([^"]+\.css)"([^>]*)>/g,
            (_, preAttrs = "", href, postAttrs = "") =>
              `<link rel="preload" as="style"${preAttrs}href="${href}"${postAttrs} onload="this.onload=null;this.rel='stylesheet'">` +
              `<noscript><link rel="stylesheet"${preAttrs}href="${href}"${postAttrs}></noscript>`,
          );

          if (homePreloads.moduleFiles.length) {
            transformedHtml = transformedHtml.replace(
              "</head>",
              `<script>if(window.location.pathname==="/"){${JSON.stringify(
                homePreloads.moduleFiles,
              )}.forEach(function(href){var link=document.createElement("link");link.rel="modulepreload";link.href=href;link.crossOrigin="";document.head.appendChild(link);});}</script></head>`,
            );
          }

          if (
            Object.values(vetNearMePreloadsByPath).some(
              ({ styleFiles, moduleFiles }) =>
                styleFiles.length || moduleFiles.length,
            )
          ) {
            transformedHtml = transformedHtml.replace(
              "</head>",
              `<script>(function(){var bookingPreloads=${JSON.stringify(
                vetNearMePreloadsByPath,
              )};var pathname=window.location.pathname.replace(/\\/$/,"")||"/";var current=bookingPreloads[pathname];if(!current)return;current.styleFiles.forEach(function(href){if(document.querySelector('link[rel="stylesheet"][href="'+href+'"]'))return;var link=document.createElement("link");link.rel="stylesheet";link.href=href;document.head.appendChild(link);});current.moduleFiles.forEach(function(href){var link=document.createElement("link");link.rel="modulepreload";link.href=href;link.crossOrigin="";document.head.appendChild(link);});})();</script></head>`,
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
