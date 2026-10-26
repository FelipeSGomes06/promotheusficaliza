# Atualização automática do Infosiga (mensal) e da previsão (semanal)

<!-- fontes: src/Application/UrbanUpdater.php -->

`UrbanUpdater` é a rotina agendável `php bin/fiscaliza urban:update` (serviço `atualizador` do Compose, a cada 6 h, ou
cron do host). Idempotente:

- **Mensal:** se ainda não houve download neste mês, baixa o arquivo aberto do Infosiga-SP, lê só os sinistros de
  Marília com **os mesmos filtros do treino** e guarda as **contagens por quadrado de 2 km e dia dos últimos 12 meses**
  usados na conta (52 semanas até o corte de publicação; os meses mais novos também ficam). O ZIP é apagado; nenhum
  dado de vítima ou veículo é lido.
- **Semanal:** se a segunda-feira atual ainda não tem previsão com o arquivo mais recente, recalcula setores (média de
  52 semanas e Poisson experimental com o **modelo congelado** da execução de referência) e quadrados, gravando uma
  execução comum marcada como "atualização automática".

Método, modelo e avaliação **não mudam** sozinhos: vêm da execução de referência importada do treino. Trocar de método
exige novo treino e novo teste ([planejamento por setores](../../../planejamento-por-setores.md#atualização-automática-mensal-e-semanal)).

| Constante | Valor |
|---|---|
| `SOURCE_URL` | `https://infosiga.detran.sp.gov.br/painel/download/file/dados_infosiga.zip` (sobreposto por `INFOSIGA_URL`) |
| `MAX_DOWNLOAD_BYTES` | 512 MiB |
| `LAST_CHECK` | `infosiga_ultima_checagem` em `APP_META`: último download bem-sucedido, mesmo quando o arquivo veio igual |
| `REQUIRED` (privada) | colunas exigidas: `id_sinistro`, `tipo_registro`, `data_sinistro`, `tipo_via`, `latitude`, `longitude`, `municipio` |
| `MARILIA` (privada) | grafias aceitas de "Marília" depois de `strtoupper` (o arquivo vem em Windows-1252) |

## `UrbanUpdater::update`

`update(string $url, string $workDir, ?string $localZip = null, bool $forceDownload = false, ?string $week = null): array{ok: bool, messages: list<string>}`

1. Com `--arquivo` → [`ingest`](#urbanupdateringest) do arquivo local.
2. Sem arquivo: baixa ([`download`](#urbanupdaterdownload)) se forçado, se nunca checou ou se a última checagem (marcador
   ou snapshot mais novo) não é deste mês (data local); senão "Download mensal já feito neste mês".
3. Falha do download ou arquivo recusado → `ok = false` e mensagem, **mas a previsão semanal roda mesmo assim** com os
   dados anteriores.
4. [`forecast`](#urbanupdaterforecast) da semana (`--semana` ou a segunda-feira atual).

A CLI imprime cada mensagem com data e hora e sai com 1 se `ok` for falso.

## `UrbanUpdater::download`

`download(string $url, string $workDir): string` — só `https://` (inclusive redirecionamentos, até 3), por **curl**
(`allow_url_fopen` fica desligado no `php.ini`), conexão 30 s, total 900 s, tamanho máximo 512 MiB, User-Agent do
projeto. Grava em `var/infosiga/infosiga-<hex>.zip`, chama [`ingestDownloaded`](#urbanupdateringestdownloaded) e
**apaga o arquivo** no fim, com ou sem erro. Falha → `RuntimeException` "Não foi possível baixar … a previsão segue com
os dados anteriores.".

## `UrbanUpdater::ingestDownloaded`

`ingestDownloaded(string $zipPath, string $source): string` — `ingest` e, se não houve exceção, grava o marcador
`LAST_CHECK` (arquivo recusado não marca: a próxima rodada tenta de novo).

## `UrbanUpdater::ingest`

`ingest(string $zipPath, string $source): string`

1. SHA-256 do ZIP já guardado → "Arquivo igual ao já guardado (snapshot #N); nada mudou.".
2. Exige a execução de referência (`RuleViolation` "Importe antes a execução de referência").
3. Janela: de `cutoff(hoje) − 364 dias` em diante.
4. Abre o ZIP e lê **só** os membros `sinistros_*.csv` (separador `;`); faltando colunas → recusado.
5. Para cada linha de Marília: descarta ID repetido, data inválida, antes da janela, tipo diferente de
   `SINISTRO FATAL`/`SINISTRO NAO FATAL`, via diferente de `VIAS URBANAS`, coordenada ausente/inválida ou fora do
   retângulo de estudo ([`SectorForecast::cellOf`](../domain/setores-e-regioes.md#sectorforecast)); o resto conta em
   `dia → quadrado`. Tudo fica num **resumo de auditoria** (contagem por motivo).
6. Recusa sem gravar: nenhum elegível, data final fora do esperado ou arquivo **mais antigo** que o último guardado.
7. Mede a maior **revisão mensal** em meses já guardados (o Infosiga corrige registros antigos); acima de 25% vira
   aviso na mensagem.
8. Uma transação grava `URBAN_SNAPSHOT` (SHA-256, origem, instante, bytes, período, elegíveis, auditoria) e
   `URBAN_DAILY` (dia, quadrado, contagem).

Erros de arquivo → `ValidationFailed` com "Arquivo do Infosiga recusado; nada foi gravado.".

## `UrbanUpdater::forecast`

`forecast(?string $asOf = null): array{message: string, run_id: ?int}`

1. Semana = `$asOf` ou a segunda-feira da semana local atual.
2. Sem snapshot ou sem referência → mensagem e `run_id: null`.
3. Corte = [`SectorForecast::cutoff`](../domain/setores-e-regioes.md#sectorforecast); dados até o dia anterior; 52
   semanas = `through − 363`. Se o snapshot não cobre a janela → não calcula e diz o motivo.
4. Identidade da execução automática: SHA-256 de `auto|<sha do snapshot>|<semana>`; já existe → "já calculada".
5. Por setor: soma dos quadrados (52 e 13 semanas), referência `(n52 + 1) ÷ 53`, Poisson com o modelo da referência e
   as variáveis da semana; estimativa principal = a do método da referência; posição e prioridade.
6. Proveniência: a da referência com a fonte trocada pelo snapshot baixado (URL, SHA-256, data, licença CC BY 4.0,
   período) e a auditoria. Aviso: "Previsão automática semanal: média das 52 semanas recalculada com o Infosiga baixado
   em …; mesmo método da execução de referência #N.".
7. Grava a execução (sem retrospecto) e os 64 quadrados ([`UrbanImporter::cellRows`](importacao-urbana.md#urbanimportercellrows)) numa transação; corrida em `UQ_URBAN_RUN_BUNDLE` → usa a gravada.

| Método | Contrato |
|---|---|
| `UrbanUpdater::__construct` | `(Database, UrbanRepository, Clock, MetaRepository)`. |
| `UrbanUpdater::addDays` | `private static (string $date, int $days): string`. |
| `UrbanUpdater::days` | `private static (string $from, string $to): int`. |

Requer a extensão PHP `zip` (incluída na imagem Docker a partir da migração 0005). Testes: `UrbanUpdaterTest` (arquivo
igual, recusas, janela de 12 meses, revisão mensal, semana idempotente). Verificado em 20/10/2026: o cálculo em PHP
reproduziu a execução de referência (Poisson com diferença ≤ 9e-16) e o download real trouxe um arquivo idêntico
([evidência](../../../evidencias/2026-10-20-planejar-reforco.md)).

## Relacionados

[Importação](importacao-urbana.md) · [Operação: `urban:update`](../../../operacao.md#comandos-php-binfiscaliza-) ·
[Repositório urbano](../infrastructure/repositorios-planejamento.md#urbanrepository)
