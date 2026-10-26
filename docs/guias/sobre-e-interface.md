# Sobre, ajuda e interface comum

Este guia cobre a página **Sobre** e os comportamentos que valem para **todas** as telas da aplicação: ajuda "?",
envio de formulários e avisos, preferência de movimento, teclado, celular e funcionamento sem JavaScript.

## Sobre (`/sobre`)

**Objetivo:** dizer o que é real, simulado e implementado. **Acesso:** menu **Sobre**, qualquer conta.

| Bloco | Conteúdo |
|---|---|
| Dados | **fictícios**: órgãos, marcas, séries, patrimônios, agentes (nomes inspirados no mito de Prometeu), histórico, falhas, posições, missões e multas; **reais e agregados**: contagens de sinistros do Infosiga-SP (CC BY 4.0) usadas em Planejar reforço; **referências geográficas públicas**: bairros, avenidas e CEPs de logradouro de Marília, com coordenadas ilustrativas (Nominatim/OSM, ODbL). Nenhum ponto é radar, câmera, base ou pessoa real; não há integração com prefeitura, polícia ou equipamentos. Perfil compacto: 17 equipamentos e 7 agentes; completo: 48 e 21 |
| Atualização em tempo quase real | simulador a cada `SIM_INTERVAL_SECONDS`, consulta da Visão geral a cada `POLL_INTERVAL_SECONDS`; repetidos descartados, atrasados só no histórico; tabela de atualidade (sinal 10/60 min; posição 5/30 min) |
| Indicadores | disponibilidade = funcionando ÷ em serviço; confirmada por sinal; o que é "precisa de atenção"; agentes nunca somados a equipamentos |
| Cartão de autoria | **link comum** para o portal do autor; com movimento, foto e dois personagens em CSS 3D com entrada, seguimento do mouse e expansão ao clicar (`Esc` cancela, Voltar restaura) — [`pages/sobre.js`](../referencia/codigo/frontend/paginas.md#pagessobrejs-página-sobre); sem JavaScript ou com movimento reduzido, o clique só navega |
| A marca | o F desenhado como vias com um radar; cores e significado das cores das situações (sempre com forma e texto) |
| Tipos de recurso | ilustrações próprias por categoria (não reproduzem fabricante nem modelo) |

Rota → `PageController::about` → `about.twig` com os tipos do catálogo. Evidência do cartão:
[19/10/2026](../evidencias/2026-10-19-cartao-de-autoria.md). Créditos e licenças: [licenças e fontes](../licencas-e-fontes.md).

## Ajuda contextual "?"

O círculo com "?" ao lado de um rótulo explica o campo, com exemplos. Abre ao passar o mouse, ao chegar nele com Tab ou
ao tocar; fecha ao sair, com `Esc` ou ao tocar fora; clicar deixa aberta. É um botão acessível (`aria-label`,
`role="tooltip"`, `aria-describedby` ligando botão e campo); perto da borda da tela a caixinha se reposiciona. Os textos
ficam em `templates/_ajuda.twig`; situações, transições e características vêm do **domínio** (não há texto duplicado).
Sem JavaScript, a ajuda é um `<details>` que abre no clique. Campo sem ajuda gera exatamente o HTML de antes.
Referência: [macros](../referencia/codigo/templates/macros-e-telas.md#_ajudatwig) e
[`lib/popovers.js`](../referencia/codigo/frontend/entrada-e-bibliotecas.md#libpopoversjs). Teste: `HelpTipTest`;
navegador: [evidência de 24/10](../evidencias/2026-10-24-labels-e-ajuda.md) e `scripts/verificacao-interface/ajuda.cjs`
(16 telas, mouse, teclado, toque, bordas 1440/375/360 px, leitor de tela, sem JS).

## Envio de formulários e avisos

- Ao enviar, o botão troca o rótulo ("Salvando…", "Iniciando…", "Concluindo…", "Registrando…"…) e **ignora cliques
  repetidos**; ações irreversíveis (arquivar, cancelar ordem, redefinir senha…) pedem confirmação.
- O resultado só aparece **depois da resposta do servidor**: aviso no canto (verde some sozinho em 5 s; vermelho fica até
  você fechar ou a causa sumir; mesmo assunto atualiza o aviso em vez de empilhar; no máximo 4). Leitores de tela
  recebem o texto por regiões vivas.
- **Erro de validação (422):** a página volta com **tudo o que você digitou**, um resumo no topo (cada item leva ao
  campo) e a mensagem ao lado do campo.
- **Conflito (409):** "Salvo agora × Sua versão" (equipamentos, agentes, multas) ou aviso explicando o que mudou
  (acompanhamento de alertas); a digitação fica.
- Sem JavaScript: os mesmos formulários funcionam (POST → 303 → GET) e as mensagens aparecem no topo da página.

Referência: [`lib/forms.js`, `lib/toasts.js`](../referencia/codigo/frontend/entrada-e-bibliotecas.md#libformsjs).

## Movimento

Animações curtas, só em resposta a ações (nada pulsa sem parar). Vale, nesta ordem: a escolha salva **neste navegador**
pelo botão **Animações** do menu (`localStorage["pf-motion"]`) e a preferência do aparelho (`prefers-reduced-motion`).
O botão diz o estado e **por quê** ("por sua escolha neste navegador", "pela preferência do sistema"). Com movimento
reduzido, o estado final aparece de imediato (GSAP com duração 0, mapa sem voo). A landing usa a mesma chave
([landing](landing.md)). Referência: [`lib/motion.js`](../referencia/codigo/frontend/entrada-e-bibliotecas.md#libmotionjs);
decisão: [ADR-0012](../adr/0012-identidade-motion-e-build-de-assets.md).

## Teclado, celular e cor

- `Tab` percorre; `Enter`/`Espaço` ativam marcadores e botões; `Esc` fecha ficha resumida, menu, ajuda e avisos,
  devolvendo o foco a quem abriu. No mapa de cadastro, setas movem e Enter marca o centro.
- Celular (até 1099 px): o menu vira gaveta (☰) e o conteúdo atrás fica inerte enquanto aberta; a ficha resumida sobe
  de baixo; chips rolam de lado; formulários em uma coluna.
- Nenhuma informação depende só de cor, de passar o mouse ou de animação: situações têm texto e símbolo.

## Sem JavaScript

| Tela | O que continua funcionando |
|---|---|
| Visão geral | lista renderizada pelo servidor com os mesmos recursos e filtros (sem mapa nem atualização automática) |
| Cadastros | formulários comuns; ponto pela rua/CEP ao salvar; todos os blocos condicionais aparecem (preencha só o da opção escolhida; o servidor ignora ou recusa o que não se aplica) |
| Multas | idem, com o aviso de qual bloco preencher |
| Planejar reforço | lista dos quadrados com links no lugar do mapa |
| Avisos | mensagem no topo da página após o redirecionamento |

Não há código inline: a CSP (`script-src 'self'`, sem `unsafe-inline`) só aceita arquivos locais gerados pelo Vite
(`WebSecurityTest::testPagesUseOnlyLocalBuiltAssetsWithoutInlineCode`, `FinesHttpTest::testUntrustedTextIsEscapedAndPagesHaveNoInlineCode`).

## Verificação e limites

O que foi **verificado** está nas evidências datadas (CSP sem violações, teclado, celular 390/360 px, movimento reduzido
e sem JS nas telas listadas em cada uma) — [índice de evidências](../evidencias/README.md). Não houve auditoria formal de
acessibilidade (WCAG) nem teste com leitor de tela além da descrição acessível conferida pelos scripts.

## Relacionados

[Visão geral](visao-geral.md) · [Segurança](../seguranca.md) · [Frontend](../referencia/codigo/frontend/entrada-e-bibliotecas.md)
