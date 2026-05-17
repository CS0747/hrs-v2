import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'

// https://vite.dev/config/
export default defineConfig({
  // Set base path for production deployment in /hrsystem/ folder
  base: '/hrsystem/',

  plugins: [
    vue(),
    vueDevTools(),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    },
  },
  build: {
    // Output to dist folder
    outDir: 'dist',
    // Target Chrome 70+ (covers Windows 8 + Chrome/Firefox on i3 8th-10th gen)
    target: ['chrome70', 'firefox68', 'edge79'],
    // Generate source maps for debugging (optional, remove for smaller build)
    sourcemap: false,
    // Optimize chunks using function syntax for Vite 8
    rollupOptions: {
      output: {
        manualChunks(id) {
          // Vendor chunk for core dependencies
          if (id.includes('node_modules')) {
            if (id.includes('vue') || id.includes('pinia') || id.includes('vue-router')) {
              return 'vendor'
            }
            // PDF libraries
            if (id.includes('jspdf')) {
              return 'pdf'
            }
            // Excel library
            if (id.includes('xlsx')) {
              return 'excel'
            }
            // Other node_modules
            return 'vendor-other'
          }
        }
      }
    }
  },
  optimizeDeps: {
    include: ['jspdf', 'jspdf-autotable']
  }
})
