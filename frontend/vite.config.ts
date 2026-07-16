import { resolve } from 'node:path'
import tailwindcss from '@tailwindcss/vite'
import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vite'

export default defineConfig({
  plugins: [vue(), tailwindcss()],
  define: {
    'process.env.NODE_ENV': JSON.stringify('production'),
    'process.env': '{}',
  },
  css: {
    postcss: './postcss.config.js',
  },
  build: {
    lib: {
      entry: resolve(__dirname, 'src/main.ts'),
      name: 'Chatify',
      formats: ['iife'],
      fileName: () => 'chatify.js',
    },
    outDir: resolve(__dirname, '../dist'),
    emptyOutDir: false,
    cssCodeSplit: false,
    rollupOptions: {
      output: {
        assetFileNames: 'chatify.css',
        inlineDynamicImports: true,
      },
    },
  },
  test: {
    environment: 'jsdom',
    globals: true,
  },
})
