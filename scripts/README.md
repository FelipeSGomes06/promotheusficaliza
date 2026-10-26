# Scripts de apoio

Ferramentas fora da aplicação web: nada aqui roda por HTTP. Contratos, parâmetros e efeitos de cada script e de cada
função estão em [referência: scripts](../docs/referencia/codigo/scripts/scripts.md).

| Arquivo ou pasta | Para quê | Muda dados? |
|---|---|---|
| `ci-preparar-banco.php` | CI: espera o Firebird de serviço e cria o usuário da aplicação | cria usuário do Firebird (só CI) |
| `backup-restore-teste.sh` | prova de backup `gbak` e restauração num banco separado | cria backup e banco restaurado no volume |
| `sondagem-firebird.php` | sondagem exploratória do PDO_FIREBIRD (não é teste automatizado) | cria e apaga a tabela `PROBE` no banco de testes |
| `avaliar-sementes.php` | avaliação da heurística preventiva em várias sementes | **substitui** os dados de demonstração de `DB_DSN` |
| `capturar-telas.mjs` | capturas PNG por cenários, via Chrome headless sem dependências | só lê (salvo ações dos cenários) |
| [`verificacao-interface/`](verificacao-interface/README.md) | verificação da interface com Playwright | vários gravam dados de teste |
| [`documentacao/`](documentacao/README.md) | inventário e verificação da documentação | só escreve o catálogo de símbolos |

Regra para scripts que mudam dados: rodar apenas num banco de demonstração, nunca no banco de uma instalação em uso.
`avaliar-sementes.php` exige `DEMO_ALLOW_RESET=true` e o marcador do conjunto sintético, as mesmas proteções de
`demo:seed --reset`.
