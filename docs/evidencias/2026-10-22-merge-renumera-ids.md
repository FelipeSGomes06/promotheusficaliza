# Filtro `merge` do Twig renumera IDs numéricos (2026-10-22)

## Defeito
Montar um mapa id → nome com `{% set x = x|merge({(a.id ~ ''): a.name}) %}` em laço não guarda os IDs:
`merge` usa `array_merge`, que renumera chaves numéricas (0, 1, 2…). Em `templates/equipment/form.twig`
o mapa `agencyNames`/`regionNames` alimenta o quadro "Salvo agora × Sua versão" do conflito de edição,
que mostrava "—" ou o órgão/bairro vizinho (deslocado em um).

## Correção
`agencies|column('name', 'id')` e `regions|column('name', 'id')` (mesmo recurso usado na aba Multas),
com comentário curto no template.

## Teste
`WebSecurityTest::testEditConflictTableNamesTheRightAgencyAndRegion` (dois órgãos e dois bairros, IDs ≥ 1).
- Antes da correção: falhou — "Salvo agora" = "—" e "Sua versão" = nome do outro órgão.
- Depois: passa. `composer check` no contêiner (projeto Compose `pf-t09`): php-cs-fixer sem pendências,
  PHPStan sem erros, PHPUnit OK (212 testes, 1186 asserções).

## Replay do planejamento: sem alteração
`templates/planning/replay.twig` indexa `names` por `s.sector_id`, que é sempre `NO/NE/SO/SE`
(`SectorForecast::SECTORS`). Chaves de texto não numérico sobrevivem ao `array_merge`, então o defeito
não ocorre ali e não há teste que falhe. Mantido como está; só passaria a errar se os IDs de setor virassem numéricos.
