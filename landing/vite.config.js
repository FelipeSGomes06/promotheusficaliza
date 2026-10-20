// Build da landing pública (separado do build do sistema em vite.config.js). Saída versionada em
// landing/public/landing-assets/promotheus/build: a produção só precisa de PHP e arquivos estáticos, sem Node.
// JS e CSS levam hash no nome e o PHP os encontra pelo manifest.json (uma query ?v= no módulo de entrada faria o
// pedaço 3D, que importa a entrada pelo nome, carregar uma segunda cópia dela). Fontes e a textura ficam sem hash.
import { defineConfig } from 'vite';

export default defineConfig({
  base: './',
  publicDir: false,
  build: {
    outDir: 'landing/public/landing-assets/promotheus/build',
    emptyOutDir: true,
    manifest: 'manifest.json',
    // O pedaço 3D (three.js) passa de 500 kB, mas só é baixado depois da primeira pintura, quando há WebGL.
    chunkSizeWarningLimit: 700,
    assetsDir: '',
    // Tudo vira arquivo: a CSP da landing não aceita data: em imagens nem fontes.
    assetsInlineLimit: 0,
    license: { fileName: 'licencas-de-terceiros.md' },
    target: 'es2022',
    sourcemap: false,
    modulePreload: { polyfill: false },
    rollupOptions: {
      input: {
        landing: 'landing/src/landing.css',
        pagina: 'landing/src/pagina.js',
      },
      output: {
        entryFileNames: '[name]-[hash].js',
        chunkFileNames: '[name]-[hash].js',
        assetFileNames: (arquivo) => (/\.(woff2|svg)$/.test(arquivo.names?.[0] ?? '') ? '[name][extname]' : '[name]-[hash][extname]'),
        // Mantém os avisos de licença (/*! … @license */) do GSAP e do three.js.
        comments: { legal: true },
      },
    },
  },
});
