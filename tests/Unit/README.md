# Testes de unidade

Suíte `unidade`: roda sem banco e sem rede (provedores com respostas simuladas). Cada arquivo, os casos e o
comportamento que provam: [casos de teste — unidade](../../docs/referencia/codigo/testes/casos.md#suíte-unidade-testsunit-sem-banco).

| Arquivo | Tema |
|---|---|
| `AddressLocatorTest.php` | localização pelo endereço (Nominatim simulado) |
| `AttentionModelTest.php` | heurística "Revisões recomendadas" e métricas |
| `CepLookupTest.php` | consulta de CEP (ViaCEP simulado) |
| `DatabaseSeparationTest.php` | recusa de rodar testes destrutivos no banco principal |
| `DomainRulesTest.php` | situações, transições, indicadores, atualidade, urgência, alertas |
| `FineRulesTest.php` | regras das multas (placa, AIT, valores, datas, velocidade, conferências) |
| `HelpTipTest.php` | ajuda contextual "?" nas macros de formulário |
| `InputAndSpecsTest.php` | fronteira de entrada, especificações, filtros, `.env`, CSP, divisor de migrações |
| `UrbanImportTest.php` | pacote do planejamento por setores e grade de 2 km |
| `ViewCacheTest.php` | cache de templates em produção |
| `ViteAssetsTest.php` | tags dos assets com hash a partir do manifesto do Vite |
| [`fixtures/`](fixtures/README.md) | HTML de referência para o `HelpTipTest` |
