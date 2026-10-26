# Dicionário do Dev

Sim, eu sei: você provavelmente já conhece boa parte destes termos. Mas todo projeto merece uma perfumaria, e todo dev
já precisou perguntar, em algum momento, o que uma sigla significava. Então pega um café: este é um dicionário **sem
prova no final**.

Se você está começando, ótimo, chegou no lugar certo. Se já está no ramo há anos, também vale: às vezes a gente lê
"middleware" pela décima vez no dia e o cérebro simplesmente pede arrego. Aqui ninguém vai te julgar.

Cada termo vem do jeito que eu explicaria para um colega na copa: o que é, onde ele aparece **neste** projeto e, quando
der, uma comparação para fixar. Quando bater a curiosidade de ver como é por dentro, os links te levam para o
[manual](MANUAL-DO-PROJETO.md), os [guias](guias/README.md) ou a [referência de código](referencia/codigo/README.md).

## Antes dos termos: o que acontece quando você clica

Vamos acompanhar um clique só, do começo ao fim. Você está na ficha de um radar, tem um alerta ali e você aperta
**Registrar ciência**.

Primeiro o botão muda para "Registrando…" — é o navegador avisando que o pedido saiu e que é para você não ficar
clicando de novo (ele ignora, mas a gente sabe que dá vontade). Esse pedido viaja até o servidor, onde a aplicação faz
a parte chata e importante: confere se você está mesmo logado e se o pedido saiu da tela do sistema, e não de algum site
espertinho tentando se passar por você. Passou na portaria, a parte do código que entende de alertas olha o caso e
decide: dá para registrar? Dá. Então ela vai até o banco de dados e anota lá, a caneta, que o alerta foi reconhecido
por você, naquele horário. Aí volta a resposta, e na sua tela aparece o aviso verde. Uns poucos milissegundos para
tudo isso.

Se ajudar, pensa num restaurante: você pede na mesa (a tela), o garçom leva o pedido para a cozinha (a aplicação), a
cozinha pega o que precisa na despensa (o banco) e o prato volta para você (a resposta). Quase tudo o que vem abaixo é
alguém desse restaurante.

## 1. O básico

**Aplicação** — o programa que faz o trabalho de verdade: recebe pedidos, aplica as regras, guarda e devolve dados.
Aqui, é o Promotheus Fiscaliza, escrito em PHP, que vive sob o endereço `/promotheusfiscaliza`. É a cozinha do garçom.

**Navegador** — Chrome, Edge, Firefox… O programa onde a pessoa vê e usa as telas. Ele não guarda os dados do sistema:
só mostra o que o servidor manda e envia o que a pessoa faz.

**Servidor** — o computador (ou contêiner) onde a aplicação roda, sempre ligado, esperando pedidos. No ambiente local
deste projeto, é o contêiner `app` do Docker, acessível em `http://localhost:8080`.

**Frontend** — tudo o que roda no navegador: HTML, CSS, JavaScript. Aqui, as telas vêm prontas do servidor (Twig) e o
JavaScript só melhora a experiência — mapa, atualização automática, campos que aparecem na hora certa. Se o JavaScript
falhar, as telas continuam funcionando. Fontes em [`resources/`](../resources/README.md).

**Backend** — tudo o que roda no servidor: regras, permissões, banco. Aqui, o código PHP de [`src/`](../src/README.md).
Regra de ouro: a tela pode até esconder um campo, mas quem **decide** se um dado é válido é sempre o backend.

## 2. Como a web conversa

**Requisição (request)** — o pedido que o navegador manda ao servidor: "me mostra a lista de multas" ou "grava esta
ordem de manutenção". Tem um endereço e um método: `GET` para ler, `POST` para mudar algo.

**Resposta (response)** — o que volta: uma página, um redirecionamento ou um pacote de dados. Vem com um código que diz
como foi: `200` (deu certo), `303` (pronto, agora vá para esta outra página), `401` (precisa entrar), `404` (não
existe), `409` (conflito — alguém mexeu antes de você), `422` (algum campo está errado).

**Rota** — o par "método + endereço" que a aplicação sabe atender. `GET /multas` mostra a lista; `POST /multas` cria uma
multa. É o cardápio: o que não está nele, a cozinha não faz. Todas as rotas estão em [HTTP](http.md).

**API** — uma porta pensada para programas, não para pessoas: em vez de uma página bonita, devolve dados crus. Aqui,
`GET /api/central` entrega o retrato da Visão geral, e `/api/cep/{cep}` devolve a rua de um CEP.

**JSON** — o formato desses dados crus. Parece uma lista de etiquetas: `{"status": "OPERACIONAL", "code": "RAD-0007"}`.
Fácil para o JavaScript ler, legível para humanos com paciência.

**Consulta periódica (polling)** — em vez de o servidor avisar quando algo muda, a tela pergunta de tempos em tempos:
"mudou alguma coisa?". A Visão geral faz isso a cada poucos segundos. Truque importante: se uma resposta antiga chegar
atrasada, ela é descartada — só vale a mais recente. Detalhes em [Visão geral](guias/visao-geral.md#atualização-periódica-e-perda-de-conexão).

## 3. As peças do servidor

**Apache** — o servidor web: o porteiro que recebe as requisições e encaminha. Aqui, ele serve a landing pública na raiz
e manda tudo de `/promotheusfiscaliza` para a aplicação PHP. O resto das pastas do projeto fica trancado.

**PHP** — a linguagem em que a aplicação foi escrita (versão 8.5 no contêiner). Cada requisição acorda o PHP, que
responde e volta a dormir.

**Slim** — um *framework* PHP pequeno: organiza rotas, middlewares e respostas sem impor muita coisa. Pense numa
cozinha montada com bancadas boas, mas sem cardápio pronto: o cardápio é do projeto. Ver [aplicação web](referencia/codigo/http/aplicacao-web.md).

**Controlador** — a primeira parte do código que atende uma rota. Ele lê o pedido, chama quem sabe resolver e escolhe
a resposta (página, redirecionamento ou JSON). Não decide regra de negócio. Exemplo: `FineController::store` atende
`POST /multas`. Lista em [controladores](referencia/codigo/http/controladores.md).

**Serviço** — onde mora o "como fazer": validar, aplicar a regra, gravar e registrar histórico. Exemplo:
`MaintenanceService::start` inicia uma intervenção e garante que só exista uma por equipamento. O controlador é o
garçom; o serviço é o cozinheiro. Ver [casos de uso](../src/Application/README.md).

**Middleware** — um filtro por onde **toda** requisição passa antes (e depois) do controlador. Aqui são quatro: um põe
cabeçalhos de segurança, um retoma a sessão, um confere o CSRF e um exige conta e papel. É o segurança da porta que
confere a pulseira de todo mundo. Ver [middlewares](referencia/codigo/http/middlewares.md).

## 4. Quem é você e o que pode fazer

**Autenticação** — provar quem você é. Aqui: usuário e senha cadastrados pelo administrador; não há cadastro aberto.
Depois de 5 tentativas erradas em 15 minutos, a conta espera — mesmo que as tentativas cheguem ao mesmo tempo.

**Autorização** — decidir o que quem já entrou pode fazer. Aqui há dois papéis: **operador** (usa tudo) e
**administrador** (usa tudo e ainda gerencia Contas). Autenticação é mostrar o crachá; autorização é o crachá abrir
ou não a sala.

**Sessão** — a memória de que você está logado entre um clique e outro, guardada no servidor e lembrada por um cookie.
Aqui ela vale até 30 minutos parada ou 8 horas no total, e termina na hora se o administrador redefinir sua senha.

**CSRF** (*Cross-Site Request Forgery*) — um golpe em que outro site tenta fazer seu navegador enviar um pedido ao
sistema "em seu nome". A defesa: cada formulário leva um código secreto da sua sessão, e o servidor recusa qualquer
`POST` sem ele. É como o restaurante só aceitar pedidos escritos no bloquinho da própria casa. Mais em
[segurança](seguranca.md).

Detalhes práticos: [Acesso e contas](guias/acesso-e-contas.md).

## 5. Dados que ficam

**Banco de dados** — onde os dados ficam guardados de forma organizada e segura, mesmo que o servidor reinicie. É a
despensa: o que não está lá, se perdeu.

**Firebird** — o banco de dados usado aqui (versão 5), rodando num contêiner próprio, numa rede interna que o mundo de
fora não enxerga.

**Tabela** — uma planilha com colunas fixas. `EQUIPMENT` guarda equipamentos; `FINE`, multas; `ALERT`, alertas.

**Registro (linha)** — uma linha da tabela: um equipamento, uma multa, um alerta.

**SQL** — a linguagem para conversar com o banco. Os quatro verbos que você mais vai ver:

| Comando | Faz | Exemplo neste projeto |
|---|---|---|
| `SELECT` | lê | listar as ordens de manutenção abertas |
| `INSERT` | cria | registrar um evento no histórico |
| `UPDATE` | altera | mudar a situação de um equipamento |
| `DELETE` | apaga | remover dados de demonstração num `demo:seed --reset` |

O esquema completo, tabela por tabela, está no [dicionário de dados](referencia/dicionario-de-dados.md) — esse é o
dicionário "sério"; este aqui é o de bolso.

**Repositório** — a classe que guarda o SQL de um assunto. `FineRepository` sabe ler e gravar multas; o serviço pede,
o repositório executa. Todo o SQL do projeto mora em [`Repository/`](../src/Infrastructure/Repository/README.md).

**PDO** — a forma padrão do PHP de falar com bancos de dados. Aqui com o driver do Firebird e sempre com parâmetros
separados do SQL (nada de colar texto digitado dentro da consulta).

**ORM** — uma biblioteca que transforma tabelas em objetos e escreve o SQL por você. Este projeto **escolheu não usar**:
o SQL fica explícito, à vista, nos repositórios ([ADR-0002](adr/0002-pdo-direto-sem-orm.md)). Menos mágica, mais
controle.

## 6. Quando duas pessoas clicam ao mesmo tempo

**Transação** — um pacote de operações que acontece **inteiro ou não acontece**. Iniciar uma manutenção muda a ordem,
muda a situação do equipamento e grava o histórico: ou as três coisas acontecem, ou nenhuma. Sem meio-termo.

**Concorrência** — duas coisas acontecendo ao mesmo tempo sobre o mesmo dado. Duas pessoas tentando iniciar intervenções
no mesmo radar, ou confirmando o mesmo agente para missões diferentes. Aqui, o banco garante que só uma vence (com
travas e índices únicos) e a outra recebe uma mensagem clara. E se alguém editou o cadastro enquanto você editava, a tela
mostra "Salvo agora × Sua versão" — ninguém perde o que digitou.

**Histórico** — o registro de tudo o que aconteceu: quem, quando, de onde e o que mudou (antes e depois). Aqui ele é
**só de acréscimo**: a aplicação nem tem permissão no banco para apagar ou reescrever. É o livro-caixa escrito a caneta.

## 7. Contêineres

**Docker** — ferramenta que roda programas em caixas isoladas, iguais em qualquer computador. Acaba com o clássico
"na minha máquina funciona".

**Imagem** — a receita congelada da caixa: sistema, PHP, extensões e configurações. A deste projeto é descrita em
[`docker/php/Dockerfile`](../docker/README.md).

**Contêiner** — uma caixa rodando a partir da imagem. Aqui há o `app` (Apache + PHP), o `firebird` e, quando ligados,
o `simulator` e o `atualizador`. Imagem é a receita do bolo; contêiner é o bolo saindo do forno.

**Volume** — a gaveta onde o contêiner guarda o que precisa sobreviver a ele. Os dados do banco ficam no volume
`fbdata`; por isso `docker compose down -v` apaga o banco local (o `-v` leva a gaveta junto).

**Docker Compose** — o arquivo (`compose.yaml`) que descreve todos os contêineres juntos e um comando para subir tudo:
`docker compose up -d --build`. Passo a passo no [README](../README.md#instalação-local-do-zero) e em
[operação](operacao.md).

## 8. Git

**Git** — o sistema que guarda a história do código: cada mudança, quem fez e por quê.

**Commit** — uma foto salva do código com uma mensagem ("Landing: vídeo de apresentação na seção Para o avaliador").
Dá para voltar a qualquer foto.

**Branch** — uma linha paralela de trabalho. Você experimenta sem mexer na principal (`main`) e depois junta.

**Push** — enviar seus commits para o repositório remoto (GitHub, GitLab). Até o push, tudo está só no seu computador.

## E agora?

Com o vocabulário em dia, o [manual do projeto](MANUAL-DO-PROJETO.md) conta o sistema de ponta a ponta, e os
[guias](guias/README.md) explicam cada tela. Se aparecer uma palavra que não está aqui, ela merece entrar no próximo café.
