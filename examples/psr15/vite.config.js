import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// Set VITE_PORT to run the dev server on another port, and pass the same URL to PHP as VITE_DEV_SERVER_URL.
const port = Number(process.env.VITE_PORT ?? 5173)

export default defineConfig(({ command }) => ({
  // Built files are served from /build; the dev server serves from its root.
  base: command === 'build' ? '/build/' : '/',
  plugins: [react()],
  publicDir: false,
  build: {
    outDir: 'public/build',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: 'resources/js/app.jsx',
    },
  },
  server: {
    port,
    strictPort: true,
    origin: `http://localhost:${port}`,
  },
}))
