# Linha de comando (`src/Cli/`)

`Console.php` despacha os comandos de `php bin/fiscaliza` (`migrate`, `demo:seed`, `demo:seed-multas`, `user:create`,
`sim:tick`, `sim:run`, `analysis:run`, `urban:import`, `urban:update`) e trava os ciclos do simulador em
`var/simulador.lock`. Contratos, opções, efeitos e códigos de saída: [console](../../docs/referencia/codigo/cli/console.md);
exemplos operacionais: [operação](../../docs/operacao.md). Nenhum comando é acessível por HTTP.
