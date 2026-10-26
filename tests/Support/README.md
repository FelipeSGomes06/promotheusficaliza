# Suporte dos testes

Infraestrutura comum das suítes com banco. Contratos completos:
[referência — suporte dos testes](../../docs/referencia/codigo/testes/suporte.md).

| Arquivo | Papel |
|---|---|
| `FirebirdTestCase.php` | base das classes com banco: separação do banco principal, esquema recriado, dados mínimos, relógio fixo |
| `DatabaseSeparation.php` | regra que recusa testes destrutivos no banco principal |
| `ArraySession.php` | sessão em memória para os testes HTTP |
| `FakeCepLookup.php`, `FakeGeocoder.php` | falsos dos provedores (respostas fixas, registro de chamadas, "fora do ar") |
| `Parallel.php`, `parallel-worker.php` | concorrência real em processos PHP separados com largada simultânea |
| `Interleaving.php` | corrida com ordem controlada por Fibers, pausando depois de uma consulta |
