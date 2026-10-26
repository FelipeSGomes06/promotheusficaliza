# Tempo e relógio

<!-- fontes: src/Domain/Time.php src/Domain/Clock.php -->

Convenção única de tempo do sistema e o relógio injetável que a torna testável.

**Convenção.** Instantes (`*_AT`, `TIMESTAMP` no Firebird) são gravados em **UTC** e exibidos em
**America/Sao_Paulo**. Datas de calendário (`DUE_DATE`, `INSTALLED_ON`, datas do andamento de multas, `REVIEW_ON`) já
são **datas locais de Marília** (`DATE`) e nunca passam por conversão de fuso. Formulários `datetime-local` são lidos
como hora de Marília e convertidos para UTC ([`Input::localDateTime`](../application/validacao-e-filtros.md#input)).
O JSON da central usa ISO 8601 com deslocamento (`2026-10-18T18:06:13-03:00`).

## `Time`

Classe final só com métodos estáticos.

| Método | Contrato |
|---|---|
| `Time::utc` | `(): DateTimeZone` — `UTC`. |
| `Time::local` | `(): DateTimeZone` — `America/Sao_Paulo` (`LOCAL_TIMEZONE`). |
| `Time::toDb` | `(DateTimeImmutable $instant): string` — `Y-m-d H:i:s` em UTC, formato aceito pelo `TIMESTAMP` do Firebird. |
| `Time::fromDb` | `(mixed $value): ?DateTimeImmutable` — lê os 19 primeiros caracteres como UTC (`!Y-m-d H:i:s`); `null` para valor vazio, não string ou fora do formato. Frações de segundo são descartadas. |
| `Time::localDate` | `(DateTimeImmutable $instant): string` — data `Y-m-d` em Marília. Define "hoje" em todas as regras de calendário (urgência, lembretes, período da previsão). |
| `Time::short` | `(DateTimeImmutable $instant): string` — "19/10 às 15:43", hora de Marília (próxima ação, alertas). |
| `Time::elapsed` | `(DateTimeImmutable $from, DateTimeImmutable $now): string` — "12 min", "2 h 5 min", "5 dias" (negativo vira 0). |
| `Time::iso` | `(?DateTimeImmutable $instant): ?string` — ISO 8601 (`DATE_ATOM`) no fuso local; `null` passa adiante. |

Na interface, os filtros Twig `local_dt` e `br_date` ([`View`](../http/aplicacao-web.md#view)) aplicam a mesma
convenção.

## `Clock`

Relógio injetável. Produção usa o relógio do sistema; testes e a geração de histórico usam um instante fixo, o que
torna o cenário sintético reproduzível ([`DemoSeeder`](../demo/cenario-e-carga.md#demoseeder)).

| Método | Contrato |
|---|---|
| `Clock::__construct` | `(?DateTimeImmutable $fixed = null)` — com instante, o relógio fica parado nele (convertido para UTC). |
| `Clock::now` | `(): DateTimeImmutable` — o instante fixo ou `now` em UTC. |
| `Clock::set` | `(DateTimeImmutable $instant): void` — fixa o relógio (testes). |
| `Clock::advance` | `(string $interval): void` — avança o relógio por uma expressão de `modify` (`'+31 minutes'`); usado em testes de expiração e de atualidade. |

Injetado por [`Services`](../raiz/configuracao-e-servicos.md#services) em todos os serviços; `Services::withClock`
troca o relógio sem refazer o resto.

## Relacionados

[Situações e atualidade](situacoes-e-atualidade.md) · [Domínio: convenções](../../../dominio.md#tabelas)
