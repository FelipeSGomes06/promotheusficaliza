# Promotheus Fiscaliza — arquitetura e passagem para o Claude

Preparado em 18/10/2026, horário de referência America/Sao_Paulo.

## O que esta entrega contém

Plano de produto, arquitetura, critérios de segurança, direção de experiência, roteiro de execução e prompt para o **Opus 5.5**, conforme o nome solicitado pelo proprietário. Não contém implementação, SQL, configuração de servidor, pipeline ou modelos de código. O Claude deve escolher e implementar as soluções, justificar decisões e documentar o resultado real.

Nome público solicitado: **Promotheus Fiscaliza**. O enunciado chama o projeto de **F.O.G.O.**, dentro do Protocolo Prometheus. Manter a grafia do caminho pedida pelo proprietário: **https://devfelipegomes.com.br/promotheusfiscaliza**. Uma eventual mudança de marca não deve alterar esse endereço sem decisão do proprietário.

## Onde os arquivos estão e como usar

Esta pasta fica fora do projeto por causa da regra de propriedade fornecida em AGENTS.md: o Codex pode ler o projeto do Claude, mas não criar ou alterar arquivos nele. A pasta de trabalho inspecionada estava vazia. O Codex não criou arquivos dentro dela.

O próprio Claude pode copiar esta pasta para uma subpasta de planejamento em **C:/Users/FelipedosSantosGomes/Desktop/PromoTheus - fiscaliza** e usar os documentos no desenvolvimento. Essa cópia cabe ao Claude; não é necessário misturar o projeto com o repositório privado do site principal.

1. Leia `01-requisitos-e-escopo.md` para conhecer a origem e prioridade de cada requisito.
2. Leia `02-arquitetura.md`, `03-dominio-e-dados.md` e `04-seguranca-isolamento-e-publicacao.md` antes de decidir a stack.
3. Use `05-experiencia-mapa-e-animacoes.md` para orientar o produto visual.
4. Siga `06-execucao-validacao-e-documentacao.md` como roteiro de entrega.
5. Consulte as fontes em `07-pesquisa-php-firebird-fontes.md` e as observações em `08-contexto-decisoes-e-fontes.md`.
6. Cole `09-PROMPT-PARA-CLAUDE-OPUS-5-5.md` na conversa do Claude. Selecionar o modelo é uma ação do usuário/cliente: o texto do prompt não muda o modelo sozinho.

## Decisões centrais

| Decisão | Motivo |
|---|---|
| Aplicação e repositório próprios | Abrir apenas o desafio, preservando código e segredos do portal principal. |
| Monólito modular em PHP | Facilita compreensão, prazo, instalação e demonstração. |
| Firebird como primeira escolha, com prova de viabilidade inicial | Alinha-se à preferência do proprietário sem presumir suporte da hospedagem ou do ORM. |
| Mapa 2D de Marília com ícones e miniaturas ilustrativas | Entrega reconhecimento visual dos recursos com custo de carregamento controlado. |
| Partes 1 e 2 completas antes de expandir a parte 3 | Um fluxo consistente e demonstrável vale mais que muitas telas incompletas. |
| Uma análise preventiva explicável na parte 3 | Demonstra antecipação com regras e histórico sem inventar qualidade estatística. |
| Ambiente público com dados sintéticos e leitura como padrão | Permite avaliação sem expor pessoas, operação municipal ou administração real. |
| Reaproveitar padrões do site, com execução e segredos separados | Mantém continuidade visual e boas proteções sem acoplar os sistemas. |

## O que já foi confirmado

- A página do desafio foi lida no navegador autenticado do proprietário, sem preencher nem enviar o formulário.
- Existem três partes: inventário; central operacional; mecanismos preditivos à escolha do participante.
- O prazo informado é **27/10/2026 às 23h59**. A página não explicita o fuso desse prazo; usar o horário local como planejamento e conferir antes da submissão.
- O envio aceita ZIP de até **50 MB**; a página prevê arquivo de texto com link público quando necessário e menciona GitHub ou Drive. O proprietário quer **GitLab**: preparar o repositório público e também um ZIP dentro do limite evita depender de uma interpretação sobre o provedor do link.
- A página informa ponto extra por vídeo e pede declaração das ferramentas de IA utilizadas e domínio do que foi construído.
- O site principal respondeu via HTTPS e expôs cabeçalhos de proteção. Isso não equivale a uma auditoria de seu backend.

## Pendências que não impedem desenvolver localmente

Hospedagem e permissões de configuração; extensão PDO Firebird e biblioteca cliente; versões efetivamente instaláveis; possibilidade de processo/usuário separado; políticas de cookies e service workers do portal; limites do GitLab utilizado; licença final do projeto e dos assets. Não presumir respostas, nem obter segredos para colocá-los na documentação pública.

O pacote é de planejamento. Não houve deploy, push, submissão ao desafio, alteração de DNS, acesso administrativo ou teste ofensivo no portal.
