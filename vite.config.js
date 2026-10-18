// Build dos assets do Promotheus Fiscaliza. Saída versionada em public/assets/build (servida pelo Apache/PHP como
// arquivo estático): produção não precisa de Node. base "./" deixa todas as referências internas relativas, então
// o mesmo build funciona sob /promotheusfiscaliza e na origem dedicada (APP_BASE_PATH vazio).
import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
  plugins: [tailwindcss()],
  base: './',
  publicDir: false,
  build: {
    outDir: 'public/assets/build',
    emptyOutDir: true,
    manifest: 'manifest.json',
    // Licenças das dependências empacotadas, publicadas junto com os arquivos gerados.
    license: { fileName: 'licencas-de-terceiros.md' },
    assetsDir: '',
    target: 'es2022',
    sourcemap: false,
    modulePreload: { polyfill: false },
    rollupOptions: {
      input: {
        styles: 'resources/css/app.css',
        app: 'resources/js/app.js',
      },
      // Mantém os avisos de licença (/*! … @license */) das bibliotecas — o GSAP pede que o aviso acompanhe o código.
      output: { comments: { legal: true } },
    },
  },
});
