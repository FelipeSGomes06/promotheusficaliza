# Cenário sintético e carga da demonstração

<!-- fontes: src/Demo/DemoScenario.php src/Demo/DemoSeeder.php -->

Dados de demonstração de Marília/SP e o gerador de histórico reproduzível. **Órgãos, marcas, modelos, séries,
patrimônios e agentes são fictícios.** Bairros, logradouros e CEPs de logradouro são referências geográficas públicas;
as coordenadas vieram de uma consulta pontual ao Nominatim (© colaboradores do OpenStreetMap, ODbL) e são
**ilustrativas**: nenhum ponto representa equipamento, base ou pessoa real. Perfis:
[ADR-0013](../../../adr/0013-perfil-compacto-de-demonstracao.md).

## `DemoScenario`

| Símbolo | Contrato |
|---|---|
| `DemoScenario::COMPACT` | `compacto` — padrão: **17 equipamentos e 7 agentes** que cobrem todas as categorias e situações |
| `DemoScenario::FULL` | `completo` — **48 equipamentos e 21 agentes**, para volume e para a metodologia da análise |
| `COMPACT_SERIALS` (privada) | séries do perfil compacto: radares (multifunção com evento atrasado; lombada com preventiva vencida; falha com corretiva imediata; ponto planejado; **portátil** operado por agente), câmeras (PTZ; instalação recente com sinal atrasado; furtada; sem sinal), balança com preventiva próxima, cancela com preventiva futura, totem desativado, dois veículos em circulação e um em manutenção em execução, patinete em circulação e outro furtado |
| `COMPACT_AGENTS` (privada) | Hermes 01, Hermes 04, Hermes 06, Atena 01, Atena 06, Têmis 01, Têmis 04 — as quatro situações, base × posição e um em turno sem posição recente |
| `DemoScenario::PENDING_PREVENTIVE` | preventivas abertas com data fixa relativa à carga: `PCL-0042` −4 dias (vencida), `PPW-0007` +3 (próxima), `BVF-3301` +18 (futura) — existem em qualquer semente |
| `DemoScenario::profile` | `static (?string $value): string` — normaliza (`''` → compacto); desconhecido → `InvalidArgumentException`. |
| `DemoScenario::agencies` | `static (): list` — `DMT` Departamento de Mobilidade e Trânsito (fictício), `GCD` Guarda Civil de Demonstração, `PDS` Policiamento de Demonstração (fictício), `SMV` Serviço de Manutenção Viária (fictício), `STU` Transporte Urbano de Demonstração. |
| `DemoScenario::regions` | `static (): list` — 22 bairros com zona de agrupamento (CENTRO, NORTE, SUL, LESTE, OESTE — **não oficial**) e centro aproximado. |
| `DemoScenario::equipment` | `static (string $profile = COMPACT): list` — itens do perfil (compacto = filtro por série). |
| `DemoScenario::allEquipment` | `private static (): list` — os 48 itens: tipo, marca, modelo, série (`null` = desconhecida), órgão, rua, referência, CEP, bairro, lat, lng, situação final (`null` = o histórico decide), periodicidade, **propensão latente** (`fragility`), dias desde a instalação e características. |
| `DemoScenario::bases` | `static (): array` — cinco bases fictícias (C, L, S, O, N) com nome, rua, CEP, bairro e ponto. |
| `DemoScenario::agents` | `static (string $profile = COMPACT): list` — indicativo, função, órgão, base, situação e se tem posição recente. |
| `DemoScenario::allAgents` | `private static (): list` — 21 agentes com prefixos de rádio inspirados no mito de Prometeu (Hermes = trânsito, Atena = guarda, Têmis = polícia); nenhum nome ou matrícula real. |
| `DemoScenario::fragility` | `static (?string $serial, string $code): float` — propensão latente a falhas por série (0,6–2,9); fora do cenário, `1,0 + (crc32(código) mod 50)/100`. Usada **só** pelo gerador e pelo simulador; a análise preventiva **não** a lê. |

## `DemoSeeder`

Carga do cenário e geração de histórico reproduzível: mesma semente (`SIM_SEED`) ⇒ mesmo cenário **relativo à data da
carga**. Executa com a identidade de migração (precisa apagar o histórico ao recarregar).

| Símbolo | Contrato |
|---|---|
| `DemoSeeder::HISTORY_DAYS` | 240 |
| `BASE_DAILY_FAILURE` (privada) | 0,004 |
| `DemoSeeder::SILENT_SERIALS` | `OPB-1080-2290`, `TXC-0533` — param de enviar sinal (demonstram "sem comunicação" sem afirmar falha) |
| `DemoSeeder::LAGGING_SERIALS` | `OPD-4K-1199` — concentrador com atraso fixo de `LAG_MINUTES` = 25 min ("sinal atrasado" com o simulador ligado) |
| `DemoSeeder::__construct` | `(Services $services, int $seed, string $profile = COMPACT)`. |

### `DemoSeeder::run`

`run(bool $wipeFirst): array<string, int>` — com `$wipeFirst`, [`wipe`](#demoseederwipe); gerador `Xoshiro256**`
semeado por `sha256("promotheus-fiscaliza:<semente>")`; numa transação insere órgãos, bairros, equipamentos (com
histórico) e agentes; grava em `APP_META` o marcador `dataset = demo-sintetico` (com a semente), o perfil, `seeded_at`,
zera `duplicates_ignored`, `late_events = 1` (o evento atrasado permanente) e `sim_tick = 0`. Depois reconcilia os
alertas, roda uma [análise](../application/analise-preventiva.md#analysisservicerun) e carrega as
[multas](multas-de-demonstracao.md). Devolve o resumo impresso pela CLI.

### `DemoSeeder::wipe`

`private wipe(): void` — numa transação apaga dados de domínio na ordem das chaves: `FINE_DOCUMENT_CHECK`, `FINE_EVENT`,
`FINE`, `URBAN_MISSION_LOG`, `URBAN_MISSION`, `ANALYSIS_RESULT`, `ANALYSIS_RUN`, `ALERT_FOLLOWUP`, `ALERT`,
`RESOURCE_EVENT`, `MAINTENANCE_ORDER`, `EQUIPMENT`, `AGENT`, `REGION`, `AGENCY`, `APP_META`. **Preserva** as contas
(`APP_USER`, `LOGIN_ATTEMPT`) e as execuções importadas do Infosiga (`URBAN_RUN` e filhas, `URBAN_SNAPSHOT`,
`URBAN_DAILY`: dados reais agregados). As missões fictícias saem junto com os agentes.

### `DemoSeeder::history`

`private history(array $item, int $days, int $stopDaysAgo): array{0: situação, 1: instante, 2: eventos, 3: ordens}` —
linha do tempo de um equipamento instalado, dia a dia:

- **Preventivas**: 72% executadas na data; 28% com atraso de 8 a 30 dias (registradas "com atraso"); cada uma gera
  ordem `CONCLUIDA`, eventos de abertura e de situação (operacional → manutenção → operacional) e reagenda a próxima.
- **Falhas**: risco diário = 0,4% × propensão × (1 + 2 × atraso relativo da preventiva) × (1 + 0,6 × falhas nos últimos
  30 dias). Cada falha abre corretiva **imediata** 5–40 min depois, iniciada 4–30 h depois e concluída 2–40 h depois
  (resultado operacional), quando cabe antes do fim.
- Equipamento cuja situação final é definida pelo cenário para o histórico alguns dias antes (desativado 40, furtado 3,
  manutenção 2, falha 1) sem deixar reparo pela metade.
- Preventiva pendente ao final (vencida ou nos próximos 21 dias) vira ordem aberta.

Mecanismos plausíveis, mas **inventados**: um resultado bom aqui não prova nada sobre um parque real
([análise preventiva: gerador](../../../analise-preventiva.md#gerador-sintético-o-que-ele-assume)).

| Método | Contrato |
|---|---|
| `DemoSeeder::seedEquipment` | `private (array $item, array $agencyIds, array $regionIds): array{0: eventos, 1: ordens}` — valida as características pelo catálogo (`LogicException` se o cenário estiver errado), cria com código sequencial e patrimônio `PAT-<crc32>` (série desconhecida → sem patrimônio), aplica a situação final (desativado/furtado cancelam ordens abertas; falha recente com corretiva imediata não iniciada; manutenção com corretiva em execução desde ontem), sinal atual/atrasado/silencioso, posição recente dos móveis (furtado: última posição de 3 dias atrás), preventiva fixa de `PENDING_PREVENTIVE` e o **evento atrasado permanente** do radar `VS5-24-00201` (recebido há 4 min, ocorrido 2 h antes do estado atual, `HISTORICO`). Eventos com UID `seed-<código>-NNNN` e origem `SEMENTE`. |
| `DemoSeeder::seedAgent` | `private (array $agent, array $agencyIds, array $regionIds): int` — código por função, base do cenário, situação; em turno, posição perto da base (recente ou de 52 min atrás para quem fica "sem posição recente"); eventos de cadastro e de início de turno. |
| `DemoSeeder::statusEvent` | `private (string $from, string $to, DateTimeImmutable $at, string $reason): array` — evento `SITUACAO` com rótulos. |
| `DemoSeeder::near` | `private (float $lat, float $lng, float $spread): array{pos_lat, pos_lng}` — ponto aleatório em volta. |
| `DemoSeeder::failureReason` | `private (string $type): string` — motivo plausível por tipo ("perda de sinal do laço indutivo", "infiltração na cúpula"…). |

Testes: `DemoProfileTest` (17/7 e 48/21, 5 radares no compacto, 12/40 multas), `CentralAndAnalysisTest`,
`scripts/avaliar-sementes.php`.

## Relacionados

[Multas de demonstração](multas-de-demonstracao.md) · [Simulador](simulador.md) · [Domínio: dados de demonstração](../../../dominio.md#dados-de-demonstração)
