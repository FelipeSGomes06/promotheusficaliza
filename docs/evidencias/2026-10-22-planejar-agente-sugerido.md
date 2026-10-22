# Evidência — Planejar: missão com o agente sugerido (22/10/2026)

Relato: com o agente "Sugerido" escolhido no passo 3, a missão às vezes não era confirmada, e o campo de justificativa
dizia "(opcional)" e "Só é obrigatório nesse caso" ao mesmo tempo. Ambiente: Windows 11 + Docker Desktop 29.7.2,
projeto Compose isolado `pf-t01` (volume próprio, porta 8097), PHP 8.5.11 e Firebird 5.0.4. Nada foi publicado.

## Causa

O servidor refazia a sugestão no envio e só aceitava sem justificativa o **primeiro elegível daquele instante**. A
ordem depende do relógio (posição atual × atrasada após 5 min) e do simulador (posições mudam a cada passo); quando
outro agente passava à frente entre abrir a tela e confirmar, a escolha do sugerido virava "manual" e era recusada
por falta de justificativa. O erro ia para uma mensagem no topo e o que fora digitado se perdia.

Reprodução: `PlanningTest::testTheAgentSuggestedOnScreenNeedsNoJustificationEvenIfTheOrderChangedBeforeSending`
(Hermes 10 dentro do setor com posição de 4 min; dois minutos depois ela fica atrasada e Hermes 11 passa à frente).
Antes da correção: `ValidationFailed` em `PlanningService.php:160` (justificativa exigida). Depois: missão `SUGERIDO`,
sem justificativa.

## O que mudou na tela

- O formulário envia o sugerido que a tela mostrou (`suggested_agent_id`). Escolher esse agente (ou o primeiro elegível
  do momento) não pede justificativa. A elegibilidade continua conferida no servidor com o agente travado.
- Campo reescrito: **Por que escolheu este agente e não o sugerido?** `*`, dica "Obrigatório só se você escolher outro
  agente." Sem "(opcional)". Com JavaScript, só aparece (e só é obrigatório) com outro agente marcado; sem JavaScript,
  fica visível com a mesma dica.
- Recusa por validação devolve a tela (422) no passo 3, com resumo de erros, o aviso junto do campo, o agente escolhido
  marcado, ponto de apoio, observação e a confirmação preservados.

## Verificação

| O quê | Resultado |
|---|---|
| `composer check` no contêiner (estilo, PHPStan nível 6, PHPUnit) | 0 de 111 arquivos · 0 erros · **208 testes, 1092 asserções, OK** |
| `npm ci` + `npm run build` (Node do Windows) | sem erros; `reveal()` virou `resources/js/lib/reveal.js`, usado por Multas e Planejar |
| Navegador (cenário de demonstração, 1280 × 900) | sugerido marcado: campo oculto e desabilitado; outro agente: visível e obrigatório; envio sem justificativa: erro no campo e textos mantidos; volta ao sugerido e confirma: missão "agente sugerido" |
| HTML sem JavaScript | campo visível, sem `required`, sem "(opcional)", com a dica |

Não rodado: `scripts/verificacao-interface/` (Playwright não instalado nesta máquina); a conferência acima foi manual.
