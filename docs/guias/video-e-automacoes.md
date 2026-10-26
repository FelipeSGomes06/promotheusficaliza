# Vídeo e automações auxiliares

Ferramentas de quem desenvolve, fora da aplicação: o vídeo de apresentação, a verificação da interface no navegador,
capturas, avaliação da análise, backup e o inventário desta documentação. **Vários escrevem dados** — rode só num
ambiente de demonstração descartável.

## Vídeo de apresentação (`video/`)

**Situação em 26/10/2026:** pasta **não versionada**, trabalho em andamento do autor. Guia completo (requisitos,
comandos, decisões e pendências): [`video/README.md`](../../video/README.md); funções:
[referência do vídeo](../referencia/codigo/video/video.md).

| Etapa | Comando | Entrada → saída | Escreve? |
|---|---|---|---|
| Ambiente isolado | projeto Compose `pf-video` na porta 8095, `migrate`, `demo:seed --perfil=compacto`, `urban:import`, conta de teste | — | **sim**, no banco desse projeto |
| Capturas autênticas | `node capturar.mjs` (`--desde=…`) | sistema local → `capturas/*.png` + `estados.json` (caixas medidas) | **confirma uma missão fictícia**; para repetir, `demo:seed --reset` |
| Personagens | `php autoria.php > capturas/autoria.json` | macros de `templates/_autoria.twig` → JSON | não |
| Sons | `node sons.mjs` (com `servidor.mjs` no ar) | eventos da linha do tempo → `midia/efeitos.wav`, `midia/mix.wav` (efeitos sintetizados em código) | só arquivos locais |
| Prévia | `node servidor.mjs` → `http://127.0.0.1:5180/` | — | não |
| Conferência | `node quadros.mjs <pasta> <t…>`, `node caixas.mjs <estado> "<texto>"` | PNG de instantes · caixas | não |
| Render | `FFMPEG=… node render.mjs --processos=4` | → `saida/promotheus-fiscaliza.mp4` (H.264 CRF 14, AAC 320 kb/s) | **só com autorização, depois da prévia aprovada** |

Dependências: Node ≥ 20, Playwright 1.56.1 (`video/package.json`), Chrome instalado, PHP com o `vendor/` do projeto,
FFmpeg com libx264. Saídas `saida/`, `midia/efeitos.wav` e `midia/mix.wav` estão no `.gitignore` da pasta; `capturas/`
(~80 MB) e `midia/narracao.wav` (30 MB) ainda sem decisão de versionamento.

O vídeo final está publicado na landing (`apresentacao.mp4`, seção "Para o avaliador").

## Verificação da interface (`scripts/verificacao-interface/`)

Playwright contra uma instalação local, com conta de teste (`PF_BASE`, `PF_USUARIO`, `PF_SENHA`); ladrilhos do mapa
bloqueados. O que cada script verifica e se grava dados: [referência](../referencia/codigo/scripts/scripts.md#verificação-da-interface-scriptsverificacao-interface);
como rodar: [README da pasta](../../scripts/verificacao-interface/README.md). Gravam dados: `verify.cjs`, `record.cjs`,
`multas.cjs`, `agentes.cjs`, `acompanhamento.cjs`, `preparar-revisao.php`, `revisao-fluxos.cjs`. Só leem: `captures.cjs`,
`fps.cjs`, `auditoria.cjs`, `ajuda.cjs`. As saídas viram evidências em [`docs/evidencias/`](../evidencias/README.md).

## Outros scripts (`scripts/`)

| Script | Para quê | Cuidado |
|---|---|---|
| `capturar-telas.mjs` | capturas PNG por cenários JSON (Chrome headless, sem dependências) | conta de teste descartável |
| `avaliar-sementes.php` | precisão/revocação da análise em várias sementes | **substitui** os dados de demonstração de `DB_DSN`; exige `DEMO_ALLOW_RESET=true` e o marcador |
| `backup-restore-teste.sh` | prova de `gbak` e restauração num banco separado | cria arquivos no volume do Firebird |
| `sondagem-firebird.php` | sondagem do driver (não é teste) | cria e apaga `PROBE` no banco de testes |
| `ci-preparar-banco.php` | só CI: cria o usuário da aplicação | — |
| `documentacao/inventario.mjs` | catálogo de símbolos e verificação da documentação | só escreve o catálogo |

Detalhes: [`scripts/README.md`](../../scripts/README.md).

## Relacionados

[Landing](landing.md) · [Demonstração e simulação](demonstracao-e-simulacao.md) · [Verificação executada](../verificacao.md)
