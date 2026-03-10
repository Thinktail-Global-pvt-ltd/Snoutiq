/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./src/App.jsx",
    "./src/layouts/MainLayout.jsx",
    "./src/newflow/HomePage.jsx",
    "./src/newflow/Navbar.jsx",
    "./src/newflow/NewButton.jsx",
    "./src/newflow/NewFooter.jsx",
    "./src/newflow/ServiceCard.jsx",
  ],
  theme: {
    extend: {
      colors: {
        brand: "#2a8cfb",
        "brand-dark": "#1a6fd6",
        "brand-light": "#e8f3ff",
        accent: "#f97316",
        "accent-hover": "#ea6c0a",
      },
      fontFamily: {
        sans: [
          "system-ui",
          "-apple-system",
          "BlinkMacSystemFont",
          '"Segoe UI"',
          "Roboto",
          "sans-serif",
        ],
        display: [
          "system-ui",
          "-apple-system",
          "BlinkMacSystemFont",
          '"Segoe UI"',
          "Roboto",
          "sans-serif",
        ],
        heading: [
          "system-ui",
          "-apple-system",
          "BlinkMacSystemFont",
          '"Segoe UI"',
          "Roboto",
          "sans-serif",
        ],
      },
    },
  },
  plugins: [],
};
