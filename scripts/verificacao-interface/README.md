# Verificação da interface no navegador

Scripts usados para a [evidência de identidade e movimento](../../docs/evidencias/2026-10-18-identidade-e-motion.md).
Rodam contra uma instalação local com o cenário de demonstração e uma conta de teste descartável; os servidores de
tiles são bloqueados pelos próprios scripts (o `L_tiles` usa tiles sintéticos só para medir o layout).
Como nada abre sem conta ([ADR-0014](../../docs/adr/0014-acesso-somente-com-conta-cadastrada.md)), todos entram com
`PF_USUARIO`/`PF_SENHA` por [`comum.cjs`](comum.cjs); o `verify.cjs` também confere que, sem conta, tudo leva ao login.

```bash
npm install --no-save playwright@1.56.1   # ou PLAYWRIGHT_MODULE=/caminho/para/playwright
export PF_BASE=http://127.0.0.1:8091/promotheusfiscaliza PF_USUARIO=avaliador PF_SENHA='…'
node scripts/verificacao-interface/verify.cjs   /tmp/saida   # relatório JSON (CSP com e sem conta, polling, filtro, ficha, rede, envio, movimento, teclado, celular, tiles, sessão encerrada)
node scripts/verificacao-interface/record.cjs   /tmp/videos  # vídeos WebM das interações
node scripts/verificacao-interface/captures.cjs /tmp/fotos   # capturas JPEG 1440×900 e 390×844
node scripts/verificacao-interface/fps.cjs                   # intervalos entre quadros durante as animações
node scripts/verificacao-interface/multas.cjs  /tmp/multas  # aba Multas: capturas 1440/390/360 px + relatório (filtro, origem, 422, 409, sem JS, movimento reduzido)
```

`verify.cjs` e `record.cjs` registram falhas simuladas e ciência de alertas: use só em banco de demonstração.

`multas.cjs` também grava rascunhos e edições de teste (precisa do cenário de multas: `demo:seed` ou `demo:seed-multas`).
`PF_CHROMIUM` aponta um executável do Chromium já instalado quando a versão do Playwright não traz o seu.
