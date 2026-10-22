# Evidência — Multas: lembrete de conferir de novo só depois do resultado (22/10/2026)

Pedido do autor: "após a conferência do documento, perguntar se quer revisar, só depois da conferência". Antes, o
formulário "Anotar conferência dos documentos" mostrava desde o início o campo de data "Revisar em", que respondia
"quando conferir de novo?" sem dizer o que acontecia nessa data. Ambiente: Windows 11 + Docker Desktop, projeto
Compose isolado `pf-t03` (porta 8099), PHP 8.5.11 e Firebird 5.0.4, `demo:seed`. Nada foi publicado.

## O que mudou na tela

- O campo "Revisar em" virou a pergunta **Quer ser lembrado de conferir de novo?**, no fim do formulário, com
  Não precisa · Em 30 dias · Em 90 dias · Escolher data. Só "Escolher data" mostra o campo **Lembrar de conferir de
  novo em**. A dica diz o efeito: na data, a multa mostra "Documentos: precisam ser conferidos de novo"; é um lembrete
  seu, não prazo legal.
- Com JavaScript, a pergunta só aparece depois de escolher o **Resultado**. "Em dia" a traz recolhida, já em "Não
  precisa" (o resumo mostra a resposta); "Com pendência" e "Não conferidos" a trazem aberta, sem resposta marcada.
- Sem JavaScript, opções e data aparecem juntas, com a instrução de usar só uma.
- O selo de lembrete vencido (lista e ficha) passou de "Documentos: rever" para **Documentos: precisam ser conferidos
  de novo**; o motivo em "A conferir" usa a mesma frase. A regra não mudou: conta só depois da data.
- Servidor: `review_choice` vira `review_on` (`FineDocumentStatus::reviewOn`), contando 30/90 dias a partir de hoje;
  "Não precisa" grava nulo; data junto com outra opção é recusada, para nada ser descartado em silêncio. Sem resposta
  vale a data digitada (sem JavaScript, cenário de demonstração). Sem migração; `FINE_DOCUMENT_CHECK` intacta.

Capturas: `capturas-2026-10-22-lembrete-conferencia/`.

## Verificação

| O quê | Resultado |
|---|---|
| `composer check` no contêiner (estilo, PHPStan, PHPUnit) | 0 de 111 arquivos · 0 erros · **211 testes OK** |
| `FineRulesTest::testReviewReminderAnswerBecomesADateCountedFromToday` | 30 e 90 dias, data escolhida, não precisa, erros de combinação |
| `FinesTest::testReviewReminderAnswerIsStoredAsADateAndCannotPrecedeTheCheckedDay` (Firebird) | datas gravadas e projetadas; data antes do dia da conferência recusada |
| `npm run build` | sem erros |
| `scripts/verificacao-interface/multas.cjs` (Chromium, 1440/390/360 px) | 33 verificações OK, sem erro no console; 6 novas sobre o lembrete |

Não verificado: envio pelo navegador de uma conferência com lembrete (coberto pelo teste de integração do serviço);
leitores de tela reais.
