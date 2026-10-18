# Verificação da interface no navegador

Scripts usados para a [evidência de identidade e movimento](../../docs/evidencias/2026-10-18-identidade-e-motion.md).
Rodam contra uma instalação local com o cenário de demonstração e uma conta de teste descartável; os servidores de
tiles são bloqueados pelos próprios scripts (o `L_tiles` usa tiles sintéticos só para medir o layout).

```bash
npm install --no-save playwright@1.56.1   # ou PLAYWRIGHT_MODULE=/caminho/para/playwright
export PF_BASE=http://127.0.0.1:8091/promotheusfiscaliza PF_USUARIO=avaliador PF_SENHA='…'
node scripts/verificacao-interface/verify.cjs   /tmp/saida   # relatório JSON (CSP, polling, corrida de filtro, ficha, rede, envio, movimento, teclado, celular, tiles)
node scripts/verificacao-interface/record.cjs   /tmp/videos  # vídeos WebM das interações
node scripts/verificacao-interface/captures.cjs /tmp/fotos   # capturas JPEG 1440×900 e 390×844
node scripts/verificacao-interface/fps.cjs                   # intervalos entre quadros durante as animações
```

`verify.cjs` e `record.cjs` registram falhas simuladas e ciência de alertas: use só em banco de demonstração.
