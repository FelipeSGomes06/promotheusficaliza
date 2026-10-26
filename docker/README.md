# Contêineres (`docker/`)

Arquivos usados pelo [`compose.yaml`](../compose.yaml) para o ambiente local reproduzível e para a publicação. Referência
completa (o que cada diretiva faz, versões e CI): [implantação, configuração e CI](../docs/referencia/implantacao-e-ci.md).
Como instalar, atualizar, fazer backup e publicar: [operação](../docs/operacao.md).

| Arquivo | Papel |
|---|---|
| `firebird/10-fiscaliza-init.sh` | roda uma vez, na criação do banco: cria o usuário da aplicação, revoga o papel `ADMIN` do dono e cria o banco de testes |
| `php/Dockerfile` | PHP 8.5 + Apache + `pdo_firebird` + `zip`; diretórios de sessão e log; copia as configurações abaixo |
| `php/php.ini` | erros só no log, fuso UTC, uploads e `allow_url_fopen` desligados, sessão estrita |
| `php/apache-site.conf` | landing pública em `/` e a aplicação em `/promotheusfiscaliza`; o resto do projeto sem rota |
| `php/apache-origem-dedicada.conf` | aplicação na raiz de um host dedicado (com `compose.origem-dedicada.yaml`) |
| `php/portal-stub/index.html` | página antiga da raiz, mantida para voltar atrás sem a landing |

Mudou algo em `php/`? Reconstrua a imagem: `docker compose up -d --build` (os arquivos são copiados para dentro dela).
O script do Firebird só roda num volume novo; num banco existente, nada muda.
