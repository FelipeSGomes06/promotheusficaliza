# Linha de comando (`bin/`)

`bin/fiscaliza` é o ponto de entrada da CLI: carrega a configuração, escolhe a identidade do banco (dono do esquema em
`migrate`, `demo:seed` e `demo:seed-multas`; usuário da aplicação nos demais) e chama
[`Console::run`](../docs/referencia/codigo/cli/console.md#consolerun).

```bash
docker compose exec app php bin/fiscaliza
```

Sem argumento, imprime a ajuda com todos os comandos. Referência: [console](../docs/referencia/codigo/cli/console.md) e
[pontos de entrada](../docs/referencia/codigo/raiz/configuracao-e-servicos.md#pontos-de-entrada); operação:
[operação](../docs/operacao.md).
