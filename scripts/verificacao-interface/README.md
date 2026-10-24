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
node scripts/verificacao-interface/agentes.cjs /tmp/agentes # cadastro de agente: base pelo mapa ou pelo endereço, rua pelo CEP (respostas simuladas + servidor real), sem JS, 390/360 px
PF_AGENTE=<id> node scripts/verificacao-interface/acompanhamento.cjs /tmp/acomp # alerta de agente: ciência → responsável de Contas → providência → conflito → conta desativada → posição simulada (central, 390/360 px, teclado)
php scripts/verificacao-interface/preparar-revisao.php <usuario> > /tmp/preparo.json   # (no contêiner) volumes da revisão de 24/10: ordem antiga + 31 encerradas, não instalado, 22 missões; --limpar desfaz
PF_PREPARO=/tmp/preparo.json PF_MULTA_PAGA=<id> PF_MULTA_CONFERIR=<id> node scripts/verificacao-interface/revisao-fluxos.cjs /tmp/revisao # R01–R08 (ADR-0023), 1440/390/360 px
```

`verify.cjs` e `record.cjs` registram falhas simuladas e ciência de alertas: use só em banco de demonstração.

`multas.cjs` também grava rascunhos e edições de teste (precisa do cenário de multas: `demo:seed` ou `demo:seed-multas`).
`PF_CHROMIUM` aponta um executável do Chromium já instalado quando a versão do Playwright não traz o seu.
`acompanhamento.cjs` cadastra uma conta de teste (e a desativa), registra ciência e providências e simula posição: use só em banco de demonstração, com um agente que tenha alerta `SEM_POSICAO` aberto.
`revisao-fluxos.cjs` abre ordens, anota conferências, simula uma falha (com a resposta interrompida de propósito), cria e desativa uma conta de teste e conclui uma missão: use só em banco de demonstração. `PF_MULTA_PAGA` é uma multa registrada com aviso de infração em 04/09/2026 (a 573 da carga completa) e `PF_MULTA_CONFERIR` uma registrada sem pagamento.
