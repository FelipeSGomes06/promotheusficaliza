# Código PHP da aplicação (`src/`)

Namespace `Fiscaliza\` (PSR-4, `composer.json`). PHP ≥ 8.4 (imagem 8.5), `declare(strict_types=1)` em todos os arquivos.
Arquitetura e decisões: [arquitetura](../docs/arquitetura.md); contratos de cada classe e método:
[referência de código](../docs/referencia/codigo/README.md).

## Camadas e dependências

- `Http` → `Application` → `Domain`
- `Application` → `Infrastructure` (banco, provedores externos)
- `Cli` e `Demo` → `Application`, `Domain`, `Infrastructure`

`Domain` não conhece banco nem HTTP (regras puras, testáveis sem Firebird). `Application` orquestra casos de uso em
transações. `Infrastructure` fala com o Firebird (PDO direto, sem ORM — [ADR-0002](../docs/adr/0002-pdo-direto-sem-orm.md))
e com ViaCEP/Nominatim. `Http` traduz requisições; não contém regra de negócio.

| Pasta ou arquivo | Responsabilidade | Documentação |
|---|---|---|
| `Config.php` | carrega `.env` + variáveis do processo (estas vencem), valida prefixo e URLs, descarta credenciais privilegiadas da web | [configuração e serviços](../docs/referencia/codigo/raiz/configuracao-e-servicos.md#config) |
| `Services.php` | raiz de composição: cria banco, repositórios, serviços e provedores sob demanda | [configuração e serviços](../docs/referencia/codigo/raiz/configuracao-e-servicos.md#services) |
| [`Application/`](Application/README.md) | casos de uso (cadastros, manutenção, alertas, multas, planejamento, ingestão, autenticação, análise, endereço) | [application](../docs/referencia/codigo/README.md#application) |
| [`Cli/`](Cli/README.md) | comandos de `bin/fiscaliza` | [console](../docs/referencia/codigo/cli/console.md) |
| [`Demo/`](Demo/README.md) | cenário sintético, carga, multas de demonstração, simulador | [demo](../docs/referencia/codigo/README.md#demo) |
| [`Domain/`](Domain/README.md) | regras puras: situações, atualidade, catálogo, indicadores, alertas, atenção preventiva, multas, setores, tempo | [domain](../docs/referencia/codigo/README.md#domain) |
| [`Http/`](Http/README.md) | aplicação Slim, sessão, views Twig, assets do Vite, controladores e middlewares | [http](../docs/referencia/codigo/README.md#http) |
| [`Infrastructure/`](Infrastructure/README.md) | banco, migrações, provedores externos, repositórios | [infrastructure](../docs/referencia/codigo/README.md#infrastructure) |

Pontos de entrada: `public/index.php` (web) e `bin/fiscaliza` (CLI). Erros e exceções próprias: [erros](../docs/referencia/codigo/erros.md).
Verificação: `composer check` (estilo PER-CS, PHPStan nível 6, PHPUnit).
