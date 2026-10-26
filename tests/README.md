# Testes automatizados (PHPUnit)

Três suítes, definidas em [`phpunit.xml`](../phpunit.xml):

| Suíte | Pasta | Precisa de banco? | O que cobre |
|---|---|---|---|
| `unidade` | [`Unit/`](Unit/README.md) | não | regras de domínio, multas, entrada, catálogo, provedores de CEP/endereço (respostas simuladas), importação do planejamento, assets e cache de templates |
| `integracao-firebird` | [`Integration/`](Integration/README.md) | **sim** (Firebird real) | casos de uso com o usuário da aplicação, invariantes do banco, concorrência, histórico só de acréscimo |
| `http` | [`Http/`](Http/README.md) | **sim** | a aplicação Slim inteira (middlewares, CSRF, sessão, telas, JSON) sem servidor web |

Infraestrutura comum: [`Support/`](Support/README.md). Catálogo caso a caso, com o comportamento que cada teste prova:
[casos de teste](../docs/referencia/codigo/testes/casos.md).

## Como rodar

Dentro do contêiner da aplicação (Compose):

```bash
docker compose exec app vendor/bin/phpunit
```

Só a suíte de unidade (não toca banco):

```bash
docker compose exec app vendor/bin/phpunit --testsuite unidade
```

Verificação completa usada pela CI (estilo, análise estática e testes): `composer check`, equivalente a
`php-cs-fixer fix --dry-run --diff`, `phpstan analyse` e `phpunit`.

## Cuidados

- As suítes `integracao-firebird` e `http` **apagam e recriam** o banco de `DB_TEST_DSN` na primeira classe de cada
  execução e esvaziam as tabelas antes de cada teste. A base dos testes recusa rodar se `DB_TEST_DSN` coincidir com
  `DB_DSN` (mesmo alvo ou mesmo arquivo físico) — ver [`FirebirdTestCase`](../docs/referencia/codigo/testes/suporte.md#firebirdtestcase).
- Os casos de uso rodam com o usuário da aplicação (sem DDL, histórico só de acréscimo), como em produção; o preparo usa
  o dono do esquema (`DB_MIGRATION_USER`).
- CEP e localização usam falsos: nenhum teste acessa a rede. A atualização do Infosiga é testada com ZIPs montados no
  próprio teste.
- O relógio é fixo (2026-10-18 15:00 UTC) e o fuso do PHP é UTC (`tests/bootstrap.php`).
- `phpunit.xml` falha com aviso, teste arriscado ou depreciação.

Resultados de execuções, datados, ficam em [verificação](../docs/verificacao.md); este README não registra execução.
