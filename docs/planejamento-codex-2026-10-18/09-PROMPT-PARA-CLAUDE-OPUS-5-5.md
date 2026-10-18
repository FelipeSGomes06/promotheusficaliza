# Prompt para Claude — Opus 5.5

Selecione no cliente o modelo desejado antes de usar este prompt. “Opus 5.5” é o nome indicado pelo proprietário; este arquivo não verifica a disponibilidade do modelo nem troca a seleção automaticamente.

---

Você é o responsável por implementar o **Promotheus Fiscaliza**, sistema para a etapa bônus F.O.G.O. do DSIN Coder Challenge 2026. Use seu raciocínio para escolher as soluções. O planejamento foi preparado pelo Codex sem código, SQL ou configuração executável; ele define objetivos, limites e critérios de aceite, não uma implementação a reproduzir.

## 1. Leia o contexto antes de implementar

Leia todos os documentos desta pasta:

**C:/Users/FelipedosSantosGomes/.codex/entregas-para-claude/promotheus-fiscaliza-2026-10-18**

Comece por `00-LEIA-PRIMEIRO.md` e `01-requisitos-e-escopo.md`; depois leia arquitetura, domínio, segurança, experiência, execução, pesquisa e registro de decisões. Identifique diferenças entre requisitos da DSIN, preferências do proprietário e sugestões do Codex.

O proprietário reservou a você a aplicação de mudanças no projeto. Por isso, o Codex salvou o pacote fora da pasta do projeto. **Você, Claude, deve copiar este planejamento para uma subpasta de documentação dentro de C:/Users/FelipedosSantosGomes/Desktop/PromoTheus - fiscaliza**, preservando arquivos existentes caso o diretório já tenha recebido trabalho depois desta entrega. Continue a implementação nesse projeto, seguindo suas instruções locais. Não modificar outros projetos mantidos por você como efeito colateral.

Documente decisões importantes e contexto em Markdown. Confirme fatos do ambiente com evidência; registre hipóteses e bloqueios sem inventar resultados. Resolva escolhas rotineiras por conta própria e continue o que puder desenvolver localmente enquanto alguma informação externa estiver pendente.

## 2. Objetivo do produto

Entregar um sistema web útil, divertido, visualmente bem cuidado e fácil de avaliar, com mapa de **Marília/SP, Brasil**. Endereço pretendido:

**https://devfelipegomes.com.br/promotheusfiscaliza**

O código do desafio será aberto em **repositório próprio no GitLab**. O portal principal deve continuar com seu código, dados, credenciais e operação privados. Não copiar seu repositório, histórico, usuários, banco, `.env`, tokens, backups ou configuração privada.

A página da DSIN foi lida em 18/10/2026: há três partes, o participante pode escolher uma ou mais, o prazo exibido é **27/10/2026 às 23h59**, o ZIP tem limite de **50 MB**, um vídeo de execução rende ponto extra e o uso de IA deve ser declarado. Confira as condições finais antes da submissão; o FAQ menciona GitHub/Drive ao orientar links. Prepare também ZIP compatível com o limite, além do GitLab, para evitar depender dessa interpretação.

## 3. Funcionalidades e ordem de prioridade

**Primeiro: inventário completo.** Catalogar tipos variados de equipamentos e agentes. Equipamentos têm tipo/capacidades, série, marca, modelo, órgão, endereço estruturado com referência textual, coordenadas, situação e manutenção imediata ou programada. Respeitar características particulares: radar de velocidade pode ter limite em km/h; câmera pode ter resolução, visão noturna e imagem colorida. Não reduzir todos os tipos a um campo de observações.

Agentes de trânsito, guarda e polícia têm cadastros próprios, órgão, situação e base/posição de demonstração. Agente não é sinônimo de conta de login. Separar instalação/base da última posição de um recurso móvel. Preservar histórico das alterações importantes.

**Segundo: central operacional.** Dashboard integrado ao mapa com equipamentos e agentes, símbolos distintos, detalhes selecionáveis, situações, alertas, manutenções urgentes/próximas e visão por região. Filtros combinados por tipo, situação, bairro/região e órgão afetam mapa, lista e indicadores de maneira coerente. Mostrar total, operacionais, indisponíveis, disponibilidade e agentes ativos, com fórmulas e denominadores documentados.

Demonstrar atualização a partir de eventos persistidos. Consulta periódica é uma opção válida se o intervalo/atraso for claro. Exibir horário de observação e sincronização, tratar duplicidade, eventos atrasados e desconexão. Não simular persistência apenas com estado de interface.

**Terceiro: antecipação explicável.** Entregar um caso simples, útil e demonstrável. Recomendação inicial: atenção preventiva de equipamentos para os próximos sete dias, usando histórico de falhas, manutenção e cobertura dos dados. Você escolhe método e parâmetros ou propõe alternativa justificada. Mostrar evidências, horizonte, ação recomendada, limitações e ausência de dados. Um score heurístico não é probabilidade calibrada. Separar temporalmente dados de ajuste e avaliação; não alegar acurácia real com histórico fictício.

Priorize partes 1 e 2 completas e um caso honesto na parte 3. 3D, heatmap, exportações e novas integrações são expansões depois do núcleo.

## 4. Stack e arquitetura: decida com evidência

O backend será **PHP**. **Firebird é a primeira escolha**, por preferência do proprietário. A pesquisa oficial recomenda considerar PHP 8.5 e Firebird 5.0 em patch corrigido, ou PHP 8.4 conforme a infraestrutura; revalide versões e requisitos.

Antes de consolidar framework e persistência, prove a combinação real PHP/PDO_FIREBIRD/fbclient/Firebird: conexão na SAPI prevista, texto UTF-8, gravação/leitura parametrizada, identificadores, tipos, paginação, migração em banco vazio, commit/rollback e conflito concorrente relevante. SQLite e mocks não provam isso. Não trocar silenciosamente o banco nem escolher um ORM que dependa de suporte inexistente.

Use monólito modular proporcional ao desafio. Separe apresentação, entrada HTTP, casos de uso, regras e persistência sem criar uma arquitetura ornamental. Framework, templates, frontend, biblioteca de testes e estratégia de migração ficam sob sua responsabilidade. O portal se apresenta publicamente como Laravel/Blade/TypeScript/GSAP, mas isso não impõe a stack do desafio nem comprova compatibilidade Firebird.

Adote Composer/lock, autoload PSR-4, estilo consistente com PER Coding Style, tipos úteis, análise estática e testes relevantes. Funções coesas, nomes claros e comentários explicando decisões/regras. Não construir framework genérico, autenticação criptográfica própria ou microsserviços sem necessidade.

## 5. Segurança e isolamento são parte da entrega

Mantenha aplicação, execução, arquivos privados, banco, usuários, sessões e segredos separados do portal. Sob o prefixo solicitado, valide assets, formulários, fetch, login/logout, recarga, navegação direta e redirecionamentos. Exponha somente a área pública do desafio.

**Reconheça o limite:** uma subpasta continua na mesma origem do navegador. Cookie Path e pool PHP separado não eliminam acesso entre aplicações por scripts comprometidos. Verifique o alcance dos cookies e eventuais service workers no ambiente autorizado. Se for necessário isolamento completo, apresente uma proposta concreta de origem dedicada antes de alterar o endereço/deploy. Não prometa isolamento total mantendo a mesma origem.

O site principal já expõe proteções como CSP restritiva, nosniff, bloqueio de enquadramento, política de referência/permissões e HSTS. Use esses padrões com configuração própria. O mapa pode exigir hosts específicos de imagens; não enfraqueça a CSP global ou copie um nonce observado. Confira políticas efetivas emitidas por aplicação, Apache e proxy. Geolocalização do visitante não é necessária.

Valide entradas e transições no servidor; autorize toda operação e recurso; use parâmetros SQL e opções permitidas de ordenação; escape saída de templates e popups. Proteja escrita por sessão e CSRF, com senhas armazenadas pelas funções mantidas do PHP e cookies adequados. Sessões, logs e banco ficam fora do alcance HTTP. A aplicação não usa SYSDBA; migração tem identidade própria e controlada.

Use apenas dados sintéticos identificados: nenhum agente, trajetória operacional ou inventário municipal real. A demo pública deve permitir leitura sem conta; escrita fica autenticada. O avaliador deve conseguir testar CRUD completo localmente, com credenciais criadas naquela instalação. Sem conta administrativa de produção versionada, reset global público ou simulador anônimo que altere o banco inteiro.

Firebird em rede privada e credenciais exclusivas. Segredos só no ambiente, exemplos fictícios no Git e logs sanitizados. Faça varredura de conteúdo/histórico **antes do primeiro push público**, além da CI. Credenciais de deploy não são entregues a pipelines de contribuições externas. Confira licença do projeto e de dependências/assets.

## 6. Experiência que quero ver

Mapa centrado em Marília/SP, com pontos ilustrativos claramente identificados como demonstração. Cada categoria deve ser reconhecível por silhueta/ícone e miniatura: radar parece radar, câmera parece câmera. A ficha mostra marca/modelo e capacidades. Não dizer que uma ilustração genérica é o aparelho comercial exato.

Você decide o estilo visual. Quero personalidade e prazer de uso, com animações de seleção, mudanças de valor e feedback. Respeite movimento reduzido, teclado, foco, contraste, ausência de som automático e controle de atualizações. Não usar cor como único indicador nem mover o mapa sozinho a cada atualização.

Leaflet é uma primeira opção; escolha versão estável e provedor de tiles permitido. Preserve atribuição e políticas de cache/uso; não fazer download em massa. Nominatim público não é autocomplete por tecla. Mapa 2D e miniaturas precisam funcionar integralmente. Modelos 3D só como melhoria sob demanda, com licença, orçamento e fallback.

Desenhe estados vazios, filtros sem resultado, carregamento, erro parcial, tiles indisponíveis, sessão expirada, conflito de edição, observação antiga e análise sem histórico. Lista e indicadores continuam úteis quando os tiles falham.

## 7. Execução, verificação e explicação final

Trabalhe por fatias verticais verificáveis: prove infraestrutura; implemente um cadastro persistido com histórico; complete o domínio; conecte a central; acrescente simulação; entregue a análise; refine experiência; prepare a entrega. Mantenha documentação em cada marco e corte extras antes de comprometer o núcleo.

Execute testes proporcionais às regras e riscos: integração real Firebird, restrições, atomicidade, concorrência, estados/manutenção, indicadores com denominador zero, eventos atrasados/duplicados, autorização, CSRF, saída em popup, subcaminho e falhas. Confira acessibilidade manualmente, política efetiva do mapa e preservação das rotas do portal quando houver ambiente autorizado.

Valide uma instalação a partir de clone limpo usando somente o material público e teste restauração de backup sintético. Registre ambiente, checks executados, resultados e limitações; não declare teste que não rodou.

Ao terminar, produza documentação detalhada do **sistema realmente implementado**: propósito de cada área e módulo/arquivo relevante, entradas/saídas, relações, banco e restrições, rotas e permissões, regras/indicadores, atualização/simulador, análise preventiva, segurança, operação/recuperação e testes. Inclua ADRs, fontes/licenças, ferramentas de IA usadas de fato e um roteiro de vídeo de demonstração.

O README deve permitir ao avaliador instalar e compreender o sistema sem depender de arquivos do portal ou dos autores. Distinguir o que está implementado, verificado, pendente e fora do escopo. Publicação no servidor, push público e submissão devem respeitar as autorizações do proprietário; prepare resultados concretos antes de qualquer decisão final necessária.

Comece lendo os documentos e inspecionando o estado atual do projeto. Apresente uma síntese curta das escolhas iniciais e avance na implementação local. Você tem liberdade para resolver os problemas e melhorar as recomendações com justificativa e evidência, preservando os requisitos, a segurança e o prazo.
