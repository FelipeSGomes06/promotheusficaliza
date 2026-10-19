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
| **1. Inventário** | Equipamentos de 7 tipos (radar, câmera, balança, cancela, totem, veículo, patinete) com **características próprias validadas por tipo** (ex.: radar com funções e limite em km/h; câmera com resolução, visão noturna, imagem colorida, PTZ); série única **por fabricante** garantida pelo banco; endereço estruturado (CEP, logradouro, referência livre, bairro, cidade/UF fixas) e local marcado no mapa; local instalado × planejado × base de recurso móvel; situação operacional; manutenção imediata ou programada/periódica com **uma intervenção em execução por vez**; arquivamento sem perder histórico nem deixar ordens sem saída. Agentes de trânsito, guarda e polícia com órgão, situação, base e **última posição separada da base**. Histórico com antes/depois, autoria e origem. |
| **2. Central operacional** | "Visão geral de Marília": mapa Leaflet com ilustrações por categoria, marcadores próximos agrupados, formas distintas para instalado, planejado, base e última posição; lista com o mesmo resultado; ficha resumida ligada a ambos. Três resumos (funcionando, precisam de atenção, manutenções urgentes) e os demais indicadores com fórmulas sob demanda. Busca, tipo e situação à mão; bairro, órgão e "só o que precisa de atenção" em mais filtros — tudo aplicado **ao mesmo conjunto** no servidor. Atualização por consulta a cada 5 s a partir de **eventos persistidos no Firebird** pelo simulador, sem roubar foco, fechar a seleção ou mover o mapa. |
| **3. Cidade preditiva** | "Revisões recomendadas para os próximos 7 dias": o que revisar primeiro, por quê e o que fazer, com prioridade heurística explicável (falhas recentes, propensão em 180 dias, atraso da preventiva). Cada corte do passado usa só o que o sistema já sabia. Limiar escolhido numa janela de **ajuste** e medido numa janela de **avaliação** posterior, contra uma referência simples. Resultado honesto: ~2× o acaso, revocação baixa ([detalhes](docs/analise-preventiva.md)). |

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
<http://localhost:8080/promotheusfiscaliza/> e entre com essa conta. **Não há acesso sem conta**: nenhuma tela, mapa
ou API responde a visitantes, e não existe cadastro aberto — o administrador cadastra cada pessoa em **Contas** (ou
pelo `user:create` no servidor) e entrega a senha gerada ([ADR-0012](docs/adr/0012-acesso-somente-com-conta-cadastrada.md)).
A raiz <http://localhost:8080/> é uma página que simula o portal, para mostrar que a aplicação fica restrita ao
prefixo. Banco já existente de uma versão anterior: rode `migrate` de novo (aplica a migração 0002; ver [operação](docs/operacao.md#atualizar-um-banco-existente-para-a-migração-0002)).

Testes, análise estática e estilo (usam o banco de testes `fiscaliza_test.fdb`, recriado a cada execução; os testes
recusam rodar se o banco de testes for o mesmo do `DB_DSN`):

```bash
docker compose exec app vendor/bin/phpunit
docker compose exec app vendor/bin/phpstan analyse --memory-limit=512M
docker compose exec app vendor/bin/php-cs-fixer fix --dry-run --diff
```

Problemas comuns: as senhas do `.env` são lidas pelo Firebird **só na primeira criação do volume**; se mudá-las
depois, recrie com `docker compose down -v` (apaga o banco local). Porta 8080 ocupada: defina `APP_PORT` no `.env`.

## Roteiro rápido de avaliação

1. Entre com a conta criada pelo `user:create` (sem conta, qualquer endereço leva ao login). Visão geral: leia os
   três resumos, abra "Mais indicadores", toque em **Ver o que precisa de atenção**, escolha um ponto (ou um grupo,
   que aproxima) e alterne entre Mapa e Lista com a mesma seleção; no celular, use o Menu.
2. Ficha de um equipamento: próxima ação, onde está, cadastro, manutenção e histórico (tracejado = registro que não
   alterou a situação, com o motivo).
3. Em **Contas**, cadastre uma pessoa e confira, numa janela anônima, que ela só entra depois desse cadastro. Depois
   cadastre um radar (deixe "Velocidade" sem limite para ver o resumo de erros com a digitação mantida), instale-o
   (Não instalado → Operacional), inicie e conclua uma ordem (tente iniciar outra do mesmo equipamento), use
   **Simular falha** e registre ciência do alerta — ele só resolve quando a causa some.
4. Revisões recomendadas: o que revisar e por quê; abra o método e o retrospecto com a referência.

Passo a passo de cada tela, mensagens e erros: [guia de uso](docs/guia-de-uso.md).

Comandos úteis: `php bin/fiscaliza` lista os comandos (`sim:tick` para cron, `analysis:run`, `demo:seed --reset`).

## Estado da entrega

| Situação | Itens |
|---|---|
| **Implementado e verificado** | Partes 1, 2 e 3 descritas acima; prova PHP/Firebird ([evidência](docs/evidencias/2026-10-18-prova-firebird.md)); **93 testes automatizados, 372 asserções** (unidade, integração no Firebird real, HTTP, concorrência com processos separados) ([verificação](docs/verificacao.md)); correções dos achados da auditoria de 18/10/2026 e redesenho com capturas antes/depois ([evidência](docs/evidencias/2026-10-18-correcoes-da-auditoria.md)); backup e restauração com gbak ([evidência](docs/evidencias/2026-10-18-backup-restauracao.md)); origem dedicada testada localmente. Rastreabilidade: [matriz de requisitos](docs/matriz-requisitos.md). |
| **Pendente (depende do proprietário/infra)** | Publicação no endereço pretendido (DNS, TLS, redirecionamento no portal); repositório público (GitLab/GitHub) e varredura de segredos no histórico antes do primeiro push; ZIP de entrega gerado a partir do commit final; vídeo; conferência das condições finais da DSIN; teste com leitor de tela e com pessoa iniciante. |
| **Fora do escopo** | Integração real com IoT/órgãos, multas, reconhecimento de placas/faces, geolocalização de pessoas reais, app nativo, mensageria, alta disponibilidade, modelos 3D, heatmap e exportações. |

## Publicação

A hospedagem compartilhada do portal não oferece servidor Firebird nem `pdo_firebird` com garantia, e uma subpasta
compartilha a **mesma origem** do portal. A proposta concreta (VPS/contêiner em origem dedicada com
`compose.origem-dedicada.yaml`, e o caminho do portal redirecionando para ela) e a alternativa com proxy e prefixo
estão em [docs/operacao.md](docs/operacao.md#publicação) e [docs/seguranca.md](docs/seguranca.md#publicação-e-limite-de-mesma-origem).
Esvaziar `APP_BASE_PATH` sozinho não basta: o Apache padrão serve a aplicação só sob o prefixo. Nada foi publicado.

## Documentação

- [Guia de uso por tela](docs/guia-de-uso.md)
- [Arquitetura e referência do código](docs/arquitetura.md)
- [Domínio, modelo de dados e indicadores](docs/dominio.md)
- [Rotas HTTP, permissões e contratos de escrita](docs/http.md)
- [Análise preventiva](docs/analise-preventiva.md)
- [Segurança e privacidade](docs/seguranca.md)
- [Operação: configuração, simulador, backup e publicação](docs/operacao.md)
- [Verificação executada](docs/verificacao.md) · [Matriz de requisitos](docs/matriz-requisitos.md)
- [Decisões (ADRs)](docs/adr/)
- [IA e autoria](docs/ia-e-autoria.md) · [Licenças e fontes](docs/licencas-e-fontes.md) · [Roteiro do vídeo](docs/roteiro-video.md)
- Planejamento original recebido: [docs/planejamento-codex-2026-10-18](docs/planejamento-codex-2026-10-18/00-LEIA-PRIMEIRO.md)

## Uso de IA

Planejamento e auditoria: Codex (OpenAI). Implementação e correções: Claude (Anthropic), modelo Opus 5.5, no Claude
Code. Revisão, decisões e responsabilidade: autor do projeto. Detalhes em [docs/ia-e-autoria.md](docs/ia-e-autoria.md).

## Licença

Código sob [licença MIT](LICENSE). Leaflet (BSD-2-Clause), fonte Space Grotesk (SIL OFL 1.1) e dados do
OpenStreetMap (ODbL) mantêm suas licenças — ver [docs/licencas-e-fontes.md](docs/licencas-e-fontes.md).
