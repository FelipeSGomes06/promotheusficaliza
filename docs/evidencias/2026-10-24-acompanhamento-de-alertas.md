# Acompanhamento dos alertas de agentes (24/10/2026)

Pedido do autor.
Decisões: [ADR-0022](../adr/0022-acompanhamento-dos-alertas-de-agentes.md). Branch de trabalho
a partir de `b893aae`.

Ambiente: projeto Compose isolado `pf-rev` (porta 8097; PHP 8.5.11, Firebird 5.0.4, perfil completo), com a migração
0009 aplicada sobre o banco já populado. Conta de teste descartável `teste` (administrador) criada pelo
`user:create`; a conta do acompanhamento foi cadastrada pela própria tela Contas durante o roteiro.

## O que mudou na tela

| Antes | Depois |
|---|---|
| Ficha do agente: onde está e histórico primeiro; alertas (20 mais recentes, pendentes misturados) e "Mudar situação" no fim da coluna direita | **Próxima ação** no topo (mesmo padrão dos equipamentos), **Acompanhamento do alerta** logo abaixo e **Mudar situação** com motivo no topo da coluna direita; encerrados numa seção recolhida, com o motivo |
| Ciência só na central ou na lista de alertas, sem autor nem horário na ficha | Ciência na ficha, com nota opcional; autor, horário e nota visíveis |
| Depois da ciência, nada a fazer | Responsável (conta de Contas, pelo ID), providências com relato e próxima conferência, registros só de acréscimo |
| Pausa encerrava o alerta e o retorno abria outro | O mesmo alerta continua em pausa; encerra por posição recente ou saída de turno, com o motivo |
| Ficha resumida da central: ciência e ficha completa | Mesma próxima ação da ficha completa, responsável, próxima conferência, última providência e "Registrar providência" |

## Capturas (`capturas-2026-10-24-acompanhamento-alertas/`)

| Arquivo | O que mostra |
|---|---|
| `01-aberto-1440.jpg` | Alerta aberto: próxima ação, ciência com nota, "Sem responsável", conta recém-cadastrada na seleção |
| `02-acompanhamento-1440.jpg` | Depois da ciência, atribuição e providência: três fatos, formulário e registros (a barra fixa do topo aparece no meio por ser captura de página inteira) |
| `03-conflito-1440.jpg` | Outra aba enviou com a versão antiga: aviso no topo, digitação mantida |
| `04-central-1440.jpg` | Ficha resumida da central coerente com a ficha completa; responsável com conta desativada |
| `05-ficha-390.jpg` · `06-central-390.jpg` | Celular com movimento reduzido, sem rolagem horizontal |
| `07-encerrado-1440.jpg` | Posição simulada: "Nada pendente agora", alerta encerrado com "posição recente recebida" |

`verificacao-navegador.json`: 24 verificações do `scripts/verificacao-interface/acompanhamento.cjs`, todas OK, sem erros
de console nem violações de CSP (Chromium headless 1248). O roteiro rodou três vezes (Hermes 06, Hermes 04 e Atena 05).
As duas primeiras serviram para ajustar o próprio roteiro (os avisos viram toasts) e o visual (campos da providência na
mesma linha, estilo do `datetime-local`, "Onde está" sob a próxima ação quando nada está pendente). A terceira rodou com
tudo pronto e gerou estas capturas.

## Observações

- O simulador do `pf-rev` estava parado: ao reconciliar, 15 agentes em turno ganharam alerta sem posição (correto pela
  regra). Um `sim:tick` encerrou 13 deles com `POSICAO_RECEBIDA`. Os dois em pausa continuaram abertos, porque o
  simulador não envia posição em pausa: é o comportamento novo, e não uma falsa recuperação.
- O painel de navegador do aplicativo não desenhava com a janela oculta. Por isso a verificação visual usou o
  Playwright headless, como os demais roteiros do projeto.
- Site publicado: **não verificado** (a mudança não foi implantada).
