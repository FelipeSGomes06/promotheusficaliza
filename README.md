# Promotheus Fiscaliza

Central de fiscalização urbana de **Marília/SP** — inventário de equipamentos e agentes, central operacional com
mapa e uma análise preventiva explicável. Proposta para a etapa bônus **F.O.G.O.** do DSIN Coder Challenge 2026.

> **Demonstração com dados fictícios.** Órgãos, marcas, modelos, séries, agentes, histórico e posições são sintéticos.
> Bairros, avenidas e CEPs de logradouro são referências geográficas públicas; os pontos são ilustrativos e não
> representam equipamentos, bases ou pessoas reais. Não há integração com prefeitura, polícia ou equipamentos.

Endereço público pretendido: `https://devfelipegomes.com.br/promotheusfiscaliza` (**ainda não publicado** — ver
[Publicação](#publicação)).

## O que o sistema faz

| Parte do desafio | Entregue |
|---|---|
| **1. Inventário** | Equipamentos de 7 tipos (radar, câmera, balança, cancela, totem, veículo, patinete) com **características próprias validadas por tipo** (ex.: radar com funções e limite em km/h; câmera com resolução, visão noturna, imagem colorida, PTZ); série única **por fabricante** garantida pelo banco; endereço estruturado (CEP, logradouro, referência livre, bairro, cidade/UF fixas) e coordenadas; local instalado × planejado × base de recurso móvel; situação operacional; manutenção imediata ou programada/periódica; arquivamento sem perder histórico. Agentes de trânsito, guarda e polícia com órgão, situação, base e **última posição separada da base**. Histórico com antes/depois, autoria e origem. |
| **2. Central operacional** | Mapa Leaflet de Marília com ícones distintos por categoria e forma por tipo de recurso, ficha lateral com miniatura, lista acessível, alertas, manutenções urgentes/próximas e consolidação por bairro. Filtros combinados (tipo, situação, bairro, órgão) aplicados **ao mesmo conjunto** que alimenta mapa, lista e indicadores. Indicadores calculados no servidor com fórmulas visíveis e "não se aplica" para denominador zero. Atualização por consulta a cada 5 s a partir de **eventos persistidos no Firebird** pelo simulador, com horário de dados/último evento, pausa, recuo em falha e tratamento de duplicatas e eventos atrasados. |
| **3. Cidade preditiva** | "Quais equipamentos merecem revisão nos próximos 7 dias?" — índice heurístico explicável (falhas recentes, propensão em 180 dias, atraso da preventiva), com classe *insuficiente* para pouco histórico e *corretiva* para quem já falhou. Limiar escolhido numa janela de **ajuste** e medido numa janela de **avaliação** posterior, contra uma referência simples. Resultado honesto: ~2× o acaso, revocação baixa ([detalhes](docs/analise-preventiva.md)). |

## Stack (versões verificadas)

PHP **8.5.11** · Slim **4.15.3** · Twig **3.30.0** · **Firebird 5.0.4** via PDO_FIREBIRD (fbclient 4.0.5) · Leaflet **1.9.4**
(local) · JavaScript sem framework e sem build · Apache 2.4 (imagem `php:8.5.11-apache-trixie`) · PHPUnit 12.5 ·
PHPStan 2.3 (nível 6) · PHP-CS-Fixer 3.95 (PER Coding Style). Sem ORM: PDO direto com SQL Firebird em repositórios
pequenos ([ADR-0002](docs/adr/0002-pdo-direto-sem-orm.md)).

## Instalação local (do zero)

Pré-requisitos: **Docker** com Compose v2 e **Git**. Nada mais precisa estar instalado no computador.

```bash
git clone <URL-DO-REPOSITÓRIO> promotheus-fiscaliza
cd promotheus-fiscaliza
cp .env.example .env
```

Edite o `.env` e troque os três valores `troque-…` (`DB_PASSWORD`, `DB_MIGRATION_PASSWORD`, `FIREBIRD_ROOT_PASSWORD`)
por senhas longas e aleatórias (letras e números). Depois:

```bash
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php bin/fiscaliza migrate
docker compose exec app php bin/fiscaliza demo:seed
docker compose exec app php bin/fiscaliza user:create avaliador --role=ADMIN --name="Avaliador"
docker compose --profile simulador up -d simulator
```

O comando `user:create` mostra **uma única vez** a senha gerada. Abra
<http://localhost:8080/promotheusfiscaliza/>. A leitura é pública; para cadastrar, editar, abrir ordens e reconhecer
alertas, clique em **Entrar para editar**. A raiz <http://localhost:8080/> é uma página que simula o portal, para
mostrar que a aplicação fica restrita ao prefixo.

Testes, análise estática e estilo (usam o banco de testes `fiscaliza_test.fdb`, recriado a cada execução):

```bash
docker compose exec app vendor/bin/phpunit
docker compose exec app vendor/bin/phpstan analyse --memory-limit=512M
docker compose exec app vendor/bin/php-cs-fixer fix --dry-run --diff
```

Problemas comuns: as senhas do `.env` são lidas pelo Firebird **só na primeira criação do volume**; se mudá-las
depois, recrie com `docker compose down -v` (apaga o banco local). Porta 8080 ocupada: defina `APP_PORT` no `.env`.

## Roteiro rápido de avaliação

1. Central: veja os indicadores, abra a fórmula da disponibilidade, filtre por "Radar" (os agentes saem do escopo e
   o cartão explica), clique num marcador e na lista; pause e retome a atualização.
2. Ficha de um equipamento: características do tipo, histórico (eventos tracejados = atrasados, só no histórico),
   manutenção e atenção preventiva.
3. Entre com a conta criada: cadastre um radar (tente "velocidade" sem limite e uma série já usada pelo mesmo
   fabricante), instale-o (Não instalado → Operacional), abra e conclua uma ordem, use **Simular falha** e veja o
   alerta surgir na central; reconheça o alerta — ele só resolve quando a causa some.
4. Antecipação: método, fatores por equipamento e a avaliação retrospectiva com a referência.

Comandos úteis: `php bin/fiscaliza` lista os comandos (`sim:tick` para cron, `analysis:run`, `demo:seed --reset`).

## Estado da entrega

| Situação | Itens |
|---|---|
| **Implementado e verificado** | Partes 1, 2 e 3 descritas acima; prova PHP/Firebird ([evidência](docs/evidencias/2026-10-18-prova-firebird.md)); 62 testes automatizados (unidade, integração no Firebird real, HTTP) ([verificação](docs/verificacao.md)); backup e restauração com gbak ([evidência](docs/evidencias/2026-10-18-backup-restauracao.md)); cabeçalhos efetivos no Apache; fluxo de cadastro/instalação/login no navegador. |
| **Pendente (depende do proprietário/infra)** | Publicação no endereço pretendido; repositório público (GitLab/GitHub) e varredura de segredos no histórico antes do primeiro push; ZIP de entrega gerado a partir do commit final; vídeo; conferência das condições finais da DSIN. |
| **Fora do escopo** | Integração real com IoT/órgãos, multas, reconhecimento de placas/faces, geolocalização de pessoas reais, app nativo, mensageria, alta disponibilidade, modelos 3D, heatmap e exportações. |

## Publicação

A hospedagem compartilhada do portal não oferece servidor Firebird nem `pdo_firebird` com garantia, e uma subpasta
compartilha a **mesma origem** do portal. A proposta concreta (VPS/contêiner em origem dedicada, com o caminho do
portal redirecionando para ela) está em [docs/seguranca.md](docs/seguranca.md#publicação-e-limite-de-mesma-origem)
e [docs/operacao.md](docs/operacao.md#publicação). Nada foi publicado.

## Documentação

- [Arquitetura e referência do código](docs/arquitetura.md)
- [Domínio, modelo de dados e indicadores](docs/dominio.md)
- [Rotas HTTP e permissões](docs/http.md)
- [Análise preventiva](docs/analise-preventiva.md)
- [Segurança e privacidade](docs/seguranca.md)
- [Operação: configuração, simulador, backup e publicação](docs/operacao.md)
- [Verificação executada](docs/verificacao.md)
- [Decisões (ADRs)](docs/adr/)
- [IA e autoria](docs/ia-e-autoria.md) · [Licenças e fontes](docs/licencas-e-fontes.md) · [Roteiro do vídeo](docs/roteiro-video.md)
- Planejamento original recebido: [docs/planejamento-codex-2026-10-18](docs/planejamento-codex-2026-10-18/00-LEIA-PRIMEIRO.md)

## Uso de IA

Planejamento: Codex (OpenAI). Implementação: Claude (Anthropic), modelo Opus 5.5, no Claude Code. Revisão, decisões e
responsabilidade: autor do projeto. Detalhes em [docs/ia-e-autoria.md](docs/ia-e-autoria.md).

## Licença

Código sob [licença MIT](LICENSE). Leaflet (BSD-2-Clause), fonte Space Grotesk (SIL OFL 1.1) e dados do
OpenStreetMap (ODbL) mantêm suas licenças — ver [docs/licencas-e-fontes.md](docs/licencas-e-fontes.md).
