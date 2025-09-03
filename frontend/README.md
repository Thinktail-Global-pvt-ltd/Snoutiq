# Vite + React + TypeScript + Tailwind CSS

A modern, fast development stack for building beautiful web applications with:

- **Vite** - Lightning fast build tool and dev server
- **React** - UI library for building user interfaces
- **TypeScript** - Type-safe JavaScript development
- **Tailwind CSS** - Utility-first CSS framework

## Features

- ⚡️ **Fast Development** - Hot module replacement for instant feedback
- 🎨 **Beautiful UI** - Modern design with Tailwind CSS utilities
- 🔒 **Type Safety** - Full TypeScript support with IntelliSense
- 📱 **Responsive** - Mobile-first responsive design
- 🚀 **Production Ready** - Optimized builds for deployment

## Getting Started

### Prerequisites

- Node.js (version 16 or higher)
- npm or yarn

### Installation

1. Clone the repository:
```bash
git clone <your-repo-url>
cd snoutiq-ai
```

2. Install dependencies:
```bash
npm install
```

3. Start the development server:
```bash
npm run dev
```

4. Open your browser and navigate to `http://localhost:5173`

## Available Scripts

- `npm run dev` - Start development server
- `npm run build` - Build for production
- `npm run preview` - Preview production build
- `npm run lint` - Run ESLint

## Project Structure

```
src/
├── App.tsx          # Main application component
├── main.tsx         # Application entry point
├── index.css        # Global styles with Tailwind directives
└── assets/          # Static assets
```

## Customization

### Tailwind CSS

The project is configured with Tailwind CSS. You can customize the design system by modifying `tailwind.config.js`:

```js
// tailwind.config.js
export default {
  content: ["./index.html", "./src/**/*.{js,ts,jsx,tsx}"],
  theme: {
    extend: {
      // Add custom colors, fonts, etc.
    },
  },
  plugins: [],
}
```

### Adding New Components

Create new components in the `src/components/` directory and import them in your App.tsx or other components.

## Deployment

Build the project for production:

```bash
npm run build
```

The built files will be in the `dist/` directory, ready for deployment to any static hosting service.

## Learn More

- [Vite Documentation](https://vitejs.dev/)
- [React Documentation](https://react.dev/)
- [TypeScript Documentation](https://www.typescriptlang.org/)
- [Tailwind CSS Documentation](https://tailwindcss.com/)
