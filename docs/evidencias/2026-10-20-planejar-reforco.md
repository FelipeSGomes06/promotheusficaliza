# Evidência — Planejar reforço (20/10/2026)

Extensão descrita no [ADR-0016](../adr/0016-planejamento-preventivo-por-setores.md) e em
[planejamento-por-setores.md](../planejamento-por-setores.md). Base: `main` em `132dc7f` (mesmo commit da inspeção do
Codex). Ambiente: Windows 11 + Docker Desktop 29.7.2, projeto Compose isolado `fiscaliza-marilia` (PHP 8.5.11,
Firebird 5.0.4), porta local 8093. Só está listado o que de fato rodou.

## Demonstração reproduzível

```bash
docker compose exec app php bin/fiscaliza migrate                 # aplica 0003_planejamento_por_setores
docker compose exec app php bin/fiscaliza demo:seed               # cenário fictício (agentes, simulador)
docker compose exec app php bin/fiscaliza urban:import            # execução de referência do Infosiga
docker compose exec app php bin/fiscaliza urban:import            # de novo: não duplica
```

Saída real:

```
Aplicadas: 0001_esquema_inicial, 0002_invariantes_e_cota_de_login, 0003_planejamento_por_setores
Privilégios DML concedidos ao usuário da aplicação em 19 tabelas.
Execução #1 importada: previsão de 2026-10-20 a 2026-10-27 (fim exclusivo), 4 setores, 204 linhas de retrospecto.
SHA-256 do conjunto: 265b2a44c6349f95d93f96851ebb7c3540376f297d67d4a9ef4f2db1a30b120d
Execução já importada antes (#1); nada foi duplicado: previsão de 2026-10-20 a 2026-10-27 (fim exclusivo), 4 setores, 204 linhas de retrospecto.
```

Conjuntos adulterados (cópia da execução em `var/`), recusados sem gravar nada (`URBAN_RUN` continuou com 1 linha e
`URBAN_REPLAY` com 204):

```
$ php bin/fiscaliza urban:import --dir=var/adulterado        # schema_version = 2
Execução recusada; nada foi gravado.
 - previsao.json.schema_version: Valor fora do contrato (esperado 1).
$ php bin/fiscaliza urban:import --dir=var/adulterado        # um observed_count + 1 no retrospecto
Execução recusada; nada foi gravado.
 - retrospecto.json.rows: As linhas não reproduzem as métricas do método poisson na avaliação.
```

No navegador (conta local de teste): Planejar reforço → setor sudoeste (1º) → explicação → troca para Poisson
experimental (setor noroeste) → missão fictícia #1 confirmada com Hermes 01 (sugerido, dentro do setor, posição
atual) → aviso "Nenhuma mensagem foi enviada" → Retrospecto, semana 06/01/2025: a referência priorizou o noroeste
(7,0 esperados), o Poisson priorizaria o sudoeste (6,5); ao revelar, 8 registros elegíveis, 2 no noroeste e 5 no
sudoeste. Resultado do teste inteiro na mesma página (Poisson 246/626 = 39,3%; referência 233/626 = 37,2%).

## Verificações

| Verificação | Resultado |
|---|---|
| `vendor/bin/phpunit` (Firebird real, banco de testes separado) | **140 testes, 672 asserções, OK** (antes desta extensão: 101 / 457) |
| `phpstan analyse` (nível 6) | 0 erros |
| `php-cs-fixer fix --dry-run` | 0 arquivos a corrigir |
| `npm run build` local (Node 24.16) × `node:22-bookworm` (versão do CI) | 11 arquivos com SHA-256 idênticos |
| Cópia `ml/marilia-setores/` × `ENTREGA-MANIFESTO.json` do Codex | 28 de 28 arquivos idênticos (teste automatizado) |
| Treino a partir da cópia do repositório (`treino.py --modo completo --as-of 2026-10-20 --saida var/treino-repro`, Python 3.14.6 do ambiente preparado pelo Codex) | `previsao`, `avaliacao`, `retrospecto`, `modelo` e `vetores_paridade` **idênticos** à execução de referência (fora o carimbo `generated_at_utc`); 4 testes do protocolo OK; `verificar_paridade.php`: 20 vetores, erro máximo 2,7e-15 |
| Celular 375 px | sem rolagem horizontal da página (`scrollWidth` = 375); tabelas largas rolam dentro do próprio quadro |

Testes novos: `tests/Unit/UrbanImportTest.php` (cópia imutável, execução de referência com todos os dígitos, métricas
recalculadas, ordem com desempate, distância, e 18 conjuntos fora do contrato: truncado, versão e protocolo
desconhecidos, setor inválido, NaN, 1e999, negativo, contagem fracionária, ordem incoerente, oito dias, snapshot
diferente, coordenadas invertidas, setor deslocado, "bairro oficial", retrospecto adulterado, execução piloto, arquivo
ausente, arquivo de 10 MiB), `tests/Integration/PlanningTest.php` (precisão no banco, idempotência, nada gravado em
erro, tabelas importadas sem `UPDATE` para a aplicação, comparação de métodos, elegibilidade e ordem da sugestão,
justificativa da escolha manual, conclusão/cancelamento com histórico, índice único, **duas confirmações simultâneas
em processos separados, três rodadas**, 40 ciclos do simulador sem alterar a missão, período encerrado, retrospecto
congelado e oculto até revelar) e `tests/Http/PlanningHttpTest.php` (estado vazio, período/referência/origens/método,
só o JSON de dados inline, CSRF → 403, visitante → 401, agente de guarda forjado recusado, autoria pela sessão,
observação escapada, conclusão, retrospecto oculto/revelado e todas as semanas).

## Capturas (`2026-10-20-planejar-reforco/capturas/`)

Desktop (painel do navegador, ~1100 px): `01` previsão atual com período, referência, método e origens · `02` mapa
dos setores e lista · `03` setor, valores dos dois métodos e explicação · `04` sugestão (único livre: Hermes 06, sem
posição recente, usa a base) · `05` comparação e missão #1 confirmada · `06` Poisson experimental, setor noroeste ·
`07`–`08` retrospecto congelado com os registros ocultos · `09` revelado · `10` resultado do teste inteiro.
Celular (375 × 812): `11` topo · `12` mapa · `13` lista de setores · `14` sugestão · `15` missão · `16` retrospecto ·
`17` revelado.

Ajustes feitos depois de ver as telas: rótulos do mapa encurtados (encavalavam), zoom fracionário (o retângulo ficava
pequeno no zoom 11), caixa de confirmação com o texto num só bloco, período da missão com o último dia, coluna de
entrada escondida no celular para o "Registrado" caber.

## Não verificado

Leitor de tela real; teclado no mapa (os retângulos do Leaflet não recebem foco — a lista ao lado tem os mesmos
links); publicação na VPS (não feita); `TREINAR.cmd` em si (que cria um `.venv` novo e baixa as dependências) não foi
executado — o treino rodou com o Python do ambiente já preparado pelo Codex. A saída de `var/treino-repro` não foi
importada nem versionada.

## Adendo — quadrados de 2 km (20/10/2026)

Experimento com protocolo registrado antes (`ml/marilia-grade-2km/PROTOCOLO.md`) e teste único em 2026:
[RESULTADO.md](../../ml/marilia-grade-2km/RESULTADO.md). Integração: migração 0004, `urban:import` com a grade
(execução #1 existente recebeu os 64 quadrados; segunda importação não duplicou), seção "Onde concentrar o reforço".

```
Aplicadas: 0004_quadrados_de_2km
Execução já importada antes (#1); nada foi duplicado: previsão de 2026-10-20 a 2026-10-27 (fim exclusivo), 4 setores, 204 linhas de retrospecto.
Quadrados de 2 km: 64 de 64.
```

Primeira versão desenhava quadradinhos sobre o mapa dos setores com rótulos "Q1 · 3,8" e coordenadas na lista; o
autor achou confusa e sem simetria. Versão final: mapa próprio com a grade 8 × 8 inteira, só os prioritários
numerados (1–4) e bairros de referência na lista (capturas `18` e `19`). Verificação depois do ajuste: PHPUnit
**146 testes, 698 asserções, OK**; PHPStan 0; PHP-CS-Fixer 0; build idêntico em Node 22 (CI) e Node 24.

## Adendo — atualização automática (20/10/2026)

```
$ php bin/fiscaliza urban:update --arquivo=var/dados_infosiga.zip --semana=2026-10-20     # mesmo arquivo do Codex
Snapshot #1: 912 registros elegíveis de 2025-08-02 a 2026-09-30 guardados (12 meses usados na conta).
Previsão da semana de 2026-10-20 calculada (execução #2, dados até 2026-07-31).
$ php bin/fiscaliza urban:update --forcar-download                                          # download real, 12 s
Arquivo igual ao já guardado (snapshot #1); nada mudou.
Previsão da semana de 2026-10-19 já calculada (execução #3).
```

Execução #2 (PHP) × #1 (Python): contagens de 13/52 semanas, média, posição e os 64 quadrados idênticos; Poisson
igual em SO e NO e com diferença de 9e-16 e 1e-16 em SE e NE. Primeira tentativa de download falhou porque o projeto
mantém `allow_url_fopen` desligado; trocado por curl só https. Serviço `atualizador` rodou e registrou "Download
mensal já feito neste mês" e "Previsão da semana … já calculada". Captura `20`: execução automática na tela.
Verificação: PHPUnit **151 testes, 753 asserções, OK**; PHPStan 0; PHP-CS-Fixer 0.

## Adendo — tela em três passos (20/10/2026)

Resumo da semana + passos 1 (onde), 2 (quem) e 3 (confirmar); missão guarda o quadrado (migração 0006). Capturas `21`
a `24` (painel do navegador, ~800 px). PHPUnit **152 testes, 763 asserções, OK**; PHPStan 0; PHP-CS-Fixer 0.
