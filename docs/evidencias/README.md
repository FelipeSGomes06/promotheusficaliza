# Evidências

Registros **históricos** de verificações feitas no navegador, no banco e em scripts, cada um com a data e o ambiente em
que foi feito. Não são reescritos: se o sistema mudou depois, vale o código atual e a evidência mais recente. O resumo
das execuções de testes, datado, está em [verificação](../verificacao.md). Os scripts que produzem a maioria destes
arquivos estão em [`scripts/verificacao-interface/`](../../scripts/verificacao-interface/README.md).

## Registros (um arquivo `.md` por verificação)

| Data | Registro | Pastas de apoio |
|---|---|---|
| 18/10 | [Prova de viabilidade PHP ↔ Firebird](2026-10-18-prova-firebird.md) | — |
| 18/10 | [Backup e restauração](2026-10-18-backup-restauracao.md) | — |
| 18/10 | [Correções da auditoria e redesenho](2026-10-18-correcoes-da-auditoria.md) | `capturas-2026-10-18/` (`antes/`, `depois/`) |
| 18/10 | [Identidade de trânsito, GSAP e reatividade](2026-10-18-identidade-e-motion.md) | `2026-10-18-identidade-e-motion/` (`capturas/`, `videos/`, `verificacao-navegador.json`) |
| 18/10 | [Avaliação da análise preventiva em sementes](2026-10-18-avaliacao-sementes.md) | — |
| 19/10 | [Cartão de autoria na página Sobre](2026-10-19-cartao-de-autoria.md) | `2026-10-19-cartao-de-autoria/` |
| 19/10 | [Identidade nova com acesso somente por conta](2026-10-19-identidade-com-acesso-por-conta.md) | `2026-10-19-identidade-com-acesso-por-conta/` |
| 19–20/10 | [Landing pública com a marca 3D](2026-10-20-landing-publica.md) | `2026-10-20-landing-publica/` (`capturas/`, `videos/`) |
| 20/10 | [Planejar reforço](2026-10-20-planejar-reforco.md) | `2026-10-20-planejar-reforco/capturas/` |
| 21/10 | [Aba Multas](2026-10-21-multas.md) | `2026-10-21-multas/` |
| 22/10 | [Landing atualizada](2026-10-22-landing-atualizada.md) | `2026-10-22-landing-atualizada/` |
| 22/10 | [Multas: lembrete de conferir de novo](2026-10-22-lembrete-da-conferencia.md) | `capturas-2026-10-22-lembrete-conferencia/` |
| 22/10 | [Filtro `merge` do Twig renumera IDs](2026-10-22-merge-renumera-ids.md) | — |
| 22/10 | [Multas: menos campos à vista](2026-10-22-multas-campos.md) | `capturas-2026-10-22-multas-campos/` |
| 22/10 | [Planejar: missão com o agente sugerido](2026-10-22-planejar-agente-sugerido.md) | — |
| 22/10 | [Planejar: mapa de calor dos quadrados](2026-10-22-planejar-mapa-de-calor.md) | `capturas-2026-10-22-mapa-de-calor/` |
| 23/10 | [Cadastrar agente: base pelo mapa ou endereço, rua pelo CEP](2026-10-23-agente-base-pelo-mapa-e-cep.md) | `capturas-2026-10-23-agente-base/` |
| 23/10 | [Landing: diagnóstico dos objetos 3D e planos](2026-10-23-planos-3d.md) | `2026-10-23-planos-3d/` |
| 23/10 | [Landing: enfeites 3D](2026-10-23-enfeites-3d.md) | `2026-10-23-enfeites-3d/` |
| 24/10 | [Acompanhamento dos alertas de agentes](2026-10-24-acompanhamento-de-alertas.md) | `capturas-2026-10-24-acompanhamento-alertas/` |
| 24/10 | [Rótulos harmônicos e ajuda "?"](2026-10-24-labels-e-ajuda.md) | `2026-10-24-labels-e-ajuda/` (`capturas/`, medidas antes/depois, verificação do navegador) |
| 24/10 | [Revisão de lógica e fluxos (R01–R08)](2026-10-24-revisao-logica-e-fluxos.md) | `capturas-2026-10-24-revisao-logica-fluxos/` |

Todas as datas são de 2026. As pastas guardam capturas (JPEG/PNG), vídeos WebM e relatórios JSON gerados pelos scripts
na data do registro; não são regeradas. A auditoria de 25/10/2026 (commit `35de711`) não tem registro próprio aqui; seus
testes de interface estão em `scripts/verificacao-interface/auditoria.cjs`.

Capturas usadas no [README](../../README.md) e no [manual](../MANUAL-DO-PROJETO.md) vêm destas pastas.
