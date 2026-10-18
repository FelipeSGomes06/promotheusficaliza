# Execução, verificação e documentação final

## Roteiro sugerido até a entrega

Datas são uma proposta de organização, não uma estimativa de esforço validada. O prazo mostrado pela DSIN é 27/10/2026 às 23h59; reservar o último dia para enviar e corrigir problemas de acesso. O Claude pode ajustar a sequência mantendo as dependências e os critérios de conclusão.

| Janela proposta | Resultado verificável | Condição para seguir |
|---|---|---|
| 18–19/10 | Escopo, decisões de infraestrutura e prova PHP/Firebird | Driver e transações reais funcionando; implantação possível identificada. |
| 19–21/10 | Inventário, agentes e manutenção | Fluxos persistem no Firebird; validações, autorização e histórico básicos presentes. |
| 21–23/10 | Central, mapa de Marília, filtros e indicadores | Cadastros e mapa coerentes; fórmulas e situações verificadas. |
| 23–24/10 | Eventos simulados e análise preventiva | Atualização demonstrável; atraso, origem e limitações visíveis. |
| 24–25/10 | Polimento visual e validação | Sem regressões de fluxo; redução de movimento e estados de falha conferidos. |
| 25–26/10 | Clone limpo, documentação, pacote e vídeo | Outra pessoa instala seguindo somente o material público. |
| 27/10 | Conferência e submissão pelo responsável | ZIP/link acessível, requisitos e ferramentas declarados. |

Se o tempo apertar, retirar 3D, heatmap, efeitos extras, exportações e integrações. Não cortar segurança básica, persistência real, instalação reproduzível ou documentação do que ficou incompleto. A parte 3 pode permanecer em um único caso simples, sem expandir para vários modelos.

## Marcos de implementação para o Claude

1. **Ambiente e decisão:** registrar limites da hospedagem, escolher versões e executar a prova Firebird. Registrar ADR de framework/persistência, sem fingir suporte a um ORM ainda não demonstrado.
2. **Uma fatia funcional:** cadastrar um equipamento válido, ler no Firebird, consultar detalhe, alterar situação e registrar histórico. Testar erro e transação antes de replicar o padrão.
3. **Domínio completo:** tipos/capacidades, endereço, agentes, órgãos e manutenção, com regras consistentes e autorização no servidor.
4. **Central unificada:** mapa, indicadores, filtros e lista usam dados do backend. Corrigir divergências antes de adicionar efeitos.
5. **Simulação:** carga inicial, histórico e avanço de cenários protegidos. Distinguir evento atrasado, duplicado e falha de comunicação.
6. **Antecipação:** implementar um caso de sete dias, explicar entradas e avaliar utilidade no cenário reservado. Exibir insuficiência de dados.
7. **Experiência:** acessibilidade, miniaturas e movimento; medir custo dos assets e retirar o que prejudica a operação.
8. **Entrega:** revisão de segredos, CI, clone limpo, documentação contra o código e roteiro do vídeo.

Não encerrar um marco com telas estáticas que aparentam persistência. Registrar o que passou, falhou ou ainda não pôde ser executado.

## Matriz mínima de verificação

| Área | Casos relevantes | Tipo de evidência |
|---|---|---|
| PHP/Firebird | Banco vazio, migrações, acentos, tipos, IDs, paginação, commit/rollback | Integração com o Firebird real da versão adotada. |
| Inventário | Tipos diferentes, série duplicada conforme regra, localização planejada/ausente | Testes de caso de uso e integração onde houver restrição de banco. |
| Manutenção | Urgente, vencida, próxima, conclusão e mudança operacional | Testes das regras, incluindo limite da janela e data local. |
| Agentes | Cadastro, situação, base e última posição | Fluxo persistido e exibido no mapa. |
| Indicadores | Denominador zero, filtros combinados, estados excluídos e desatualizados | Casos com resultado conhecido e comparação tela/backend. |
| Eventos | Repetição, atraso e conflito com alteração manual | Histórico íntegro e estado atual não regredido. |
| Alertas | Criar, reconhecer e resolver; causa ainda ativa | Fluxo sem duplicação nem resolução apenas visual. |
| Análise preventiva | Histórico suficiente/insuficiente e execução reproduzida | Resultado explicado e avaliação temporal documentada. |
| Autorização | Visitante tentando escrita e operador tentando privilégio extra | Rejeição pelo servidor mesmo com chamada direta. |
| Entrada/saída | Texto com marcação em popup, filtros malformados e ordenação não permitida | Renderização como texto/rejeição, sem erro técnico público. |
| Sessão/CSRF | Login/logout, expiração e alteração sem token válido | Requisições e cookies observados no ambiente de teste. |
| Subcaminho | Acesso direto, recarga, login, assets, filtros e redirecionamentos | Navegação sob `/promotheusfiscaliza`, sem escapar para a raiz. |
| Isolamento | Arquivos privados, banco e execução do desafio | Inspeção da configuração no ambiente autorizado, sem expor valores. |
| Cabeçalhos/CSP | Mapa funcional e proteção da raiz preservada | Política efetiva de respostas e ausência de bloqueios inesperados. |
| Falhas | Tiles indisponíveis, rede lenta, erro de banco e observação antiga | Lista/indicadores úteis, aviso honesto e recuperação. |
| Acessibilidade | Teclado, foco, contraste, zoom e movimento reduzido | Navegação manual e ferramenta automatizada apropriada. |
| Entrega | Clone limpo e restauração de backup sintético | Instruções seguidas desde zero, versões e resultado registrados. |

Não escrever testes para repetir getters ou a implementação. Concentrar em regras, fronteiras, persistência, segurança e fluxos avaliáveis. Não usar SQLite/mocks como prova de compatibilidade Firebird. Verificações de segurança acima são para o ambiente do desafio; não autorizam pentest no portal principal.

## Desempenho proporcional

Definir orçamento depois da primeira medição. Como alvos iniciais, a validar: painel utilizável em poucos segundos numa conexão comum, resposta operacional abaixo de um segundo no volume sintético e mudança aparecendo dentro de um intervalo de consulta mais a latência da rede. Registrar ambiente e amostra antes de afirmar números.

Medir tamanho de assets, tempo de consulta, número de requisições e comportamento durante filtros/zoom. Não medir apenas a página vazia. Limitar histórico e densidade de pontos; evitar consultas repetidas por marcador. Descrever volume máximo validado e limitações, sem prometer escala municipal real.

## Documentação a produzir junto com a implementação

| Documento final | O que deve explicar |
|---|---|
| README público | Objetivo, partes atendidas, demo, stack real, pré-requisitos, instalação desde zero, dados fictícios, uso e limitações. |
| Guia funcional | Jornada por área, campos, regras, permissões, situações e mensagens importantes. |
| Arquitetura | Componentes efetivamente criados, responsabilidades, fluxo de leitura/escrita e fronteiras. |
| Referência do código | Para cada módulo/arquivo relevante: finalidade, entradas/saídas, dependências e testes associados. Explicar a colaboração entre eles. |
| Modelo de dados | Entidades/tabelas efetivas, relacionamentos, restrições, histórico, índices importantes e convenções de data/coordenadas. |
| Contratos HTTP | Rotas reais, métodos, campos, filtros, paginação, erros, sessão/CSRF e permissões. |
| Regras de operação e indicadores | Transições, fórmulas, denominadores, período e tratamento de ausência/atraso. |
| Análise preventiva | Pergunta, horizonte, dados, método, parâmetros, evidências, avaliação e limites. |
| Segurança e privacidade | Controles implementados, dados sintéticos, segredos, fronteira de mesma origem e riscos restantes. |
| Operação | Instalação, configuração por nome/finalidade de variável, migrações, simulador, backup/restauração, logs e recuperação de falhas. |
| Decisões/ADRs | Escolha de banco/framework, mecanismo de atualização, atributos por tipo, implantação e compromissos relevantes. |
| Verificação | Comandos reais escolhidos pelo implementador, ambiente, resultados, evidências e checks não executados. |
| IA e autoria | Ferramentas/modelos usados de fato, contribuição, revisão humana e compreensão do código. |
| Licenças e fontes | Código, dependências, cartografia, ícones, fontes, imagens e modelos; instruções de atribuição. |

O documento detalhado final deve ser um índice narrativo do sistema real, com links úteis para o código e exemplos de jornadas. Evitar uma lista imensa de funções sem explicar o propósito. Não incluir segredos, capturas do painel privado do portal ou nomes reais de operadores.

## Preparação do repositório e do vídeo

Criar histórico próprio do desafio. Incluir arquivos de lock e instruções completas; excluir caches, dependências reinstaláveis, banco físico, backups e arquivos locais. Escolher licença aberta e preservar licenças de terceiros. Verificar acesso público sem login ao GitLab e integridade do ZIP de até 50 MB. Confirmar o fluxo final do formulário, pois o FAQ menciona GitHub/Drive como exemplos de link.

Vídeo sugerido de 3–5 minutos: abrir o mapa de Marília e identificar a simulação; mostrar cadastro real de um radar e de um agente em ambiente autenticado; filtrar um problema; abrir marcador com modelo/miniatura; demonstrar manutenção/alerta e atualização; explicar análise preventiva com histórico e limite; mostrar como instalar e onde está a documentação. A duração é recomendação deste plano, não regra da DSIN.

Não filmar senha, token, `.env`, painel de hospedagem ou dados pessoais. O vídeo deve demonstrar domínio do sistema; os autores precisam conseguir explicar as decisões e o código sem depender de respostas geradas na hora.

## Definição de concluído

O sistema só está pronto quando os requisitos escolhidos têm fluxo real, os checks relevantes passaram, as limitações são declaradas, a instalação limpa foi comprovada, a documentação corresponde ao código e o pacote público está livre de informações privadas. O relatório final distingue implementado, verificado, pendente e fora do escopo.

Publicar infraestrutura, fazer push público e enviar o desafio são etapas concretas posteriores. O pacote atual autoriza planejamento e passagem ao Claude, não registra essas ações como realizadas.
