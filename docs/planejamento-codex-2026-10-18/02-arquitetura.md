# Arquitetura proposta, sem implementação

## Direção

Construir um **monólito modular**: uma aplicação PHP implantável, um banco Firebird dedicado e uma interface web. Organizar por responsabilidades claras sem transformar cada recurso em serviço remoto. O domínio e os casos de uso não devem depender da biblioteca do mapa ou de detalhes do servidor web.

O desenho abaixo representa responsabilidades, não impõe pastas, classes, framework ou padrão de projeto.

| Bloco | Responsabilidade | Limite |
|---|---|---|
| Interface web | Dashboard, formulários, mapa, acessibilidade e feedback | Não decide permissões nem é a fonte de cálculo operacional. |
| Entrada HTTP | Rotas, sessão, autorização, validação de formato e tradução de erros | Não concentra regras de manutenção e indicadores. |
| Casos de uso | Coordenar cadastro, alterações de situação, consultas, alertas e análises | Define unidades de transação e usa contratos pequenos de persistência. |
| Domínio | Significado de situação, manutenção, disponibilidade e localização | Regras testáveis sem navegador nem conexão real. |
| Persistência Firebird | Consultas parametrizadas, transações, índices e histórico | SQL e particularidades do driver ficam concentrados aqui. |
| Simulação e tarefas | Gerar eventos fictícios, consolidar histórico e atualizar análises | Execução local/agendada protegida; não vira endpoint público de reset. |
| Operação | Configuração de ambiente, logs, saúde, migrações e backup | Segredos ficam no ambiente e não no repositório. |

Fluxo conceitual: navegador → aplicação dedicada → casos de uso → Firebird. O navegador também obtém imagens cartográficas de um provedor autorizado. O simulador usa os mesmos casos de uso relevantes, com identidade própria, sem desviar das regras.

## Decidir a stack depois de provar o banco

A recomendação é usar uma versão PHP com suporte ativo ou de segurança confirmado, Composer e PDO Firebird. A pesquisa em `07-pesquisa-php-firebird-fontes.md` reúne versões e restrições verificadas; o ambiente real continua sendo a autoridade sobre o que está disponível.

O site principal se apresenta como Laravel/Blade/TypeScript/GSAP. Isso favorece continuidade de conhecimentos e linguagem visual, mas **não prova que seu ORM, suas migrações ou a hospedagem atendam ao Firebird**. Não escolher Laravel, Symfony, Slim, ORM ou ferramenta de migrations por familiaridade sem executar a prova específica.

Primeira entrega técnica do Claude: aplicação mínima local capaz de conectar ao Firebird escolhido, preservar texto em português, gravar/ler, confirmar e desfazer transação, obter identificadores, paginar e aplicar uma migração em banco vazio. Verificar limites e formatos reais do driver. Escolher então framework/componentes mantidos que reduzam trabalho sem esconder incompatibilidade. Se necessário, PDO direto com repositórios pequenos é uma escolha válida.

Não construir framework próprio, autenticação criptográfica própria ou camada universal para vários bancos. Documentar a decisão e suas alternativas em um ADR curto.

## Interface e contratos de consulta

Preferir páginas renderizadas no servidor com atualização progressiva onde fizer sentido. O mapa e os indicadores podem consumir respostas JSON da própria aplicação. SPA só se houver benefício concreto que justifique estado, build e complexidade extras.

Definir contratos para quatro grupos de leitura: inventário/detalhe; estado operacional/mapa; alertas/manutenções; histórico/análise preventiva. Cada contrato deve declarar campos, filtros permitidos, ordenação, paginação, permissões, horário de geração e origem dos dados. O Claude decide caminhos e formato final.

Mapa, KPIs e lista usam o mesmo conjunto de filtros. Um snapshot coerente deve trazer a referência temporal comum; chamadas separadas precisam de versão ou mecanismo equivalente para não apresentar cartões de um instante e pontos de outro. Total cadastrado, total filtrado e total dentro da área visível são grandezas diferentes e precisam de rótulos próprios.

Limitar resultados, tamanho da área consultada, frequência e complexidade dos filtros. Para o volume de demonstração, evitar paginação escondida que faça o mapa omitir recursos sem aviso. Para crescimento, definir agrupamento ou consulta por área com contagens explícitas.

## Atualização operacional e histórico

Proposta inicial: consultas periódicas a cada 5–10 segundos, ajustáveis após medição. Exibir último evento, última sincronização e atraso percebido. Pausar consultas quando a aba estiver oculta; aplicar recuo em falha e controle de atualização acessível. Ao retomar, buscar o estado atual.

Não exigir WebSocket/SSE para o prazo. Se escolhidos, validar suporte do proxy, duração das conexões, autenticação e reconexão. O caminho mais simples que demonstra atualização confiável prevalece.

Separar **evento ocorrido**, **evento recebido** e **estado atual**. Eventos repetidos não duplicam alertas. Um evento antigo pode entrar no histórico sem regredir a posição ou situação mais recente. Alteração manual precisa de autoria e política sobre prioridade em relação à telemetria. Não confundir silêncio do dispositivo com falha mecânica confirmada.

Manter escrita do estado, histórico e evento associado consistente por transação quando forem uma mesma operação. Consultas de tela devem usar transações curtas; não manter transação aberta enquanto o usuário lê o mapa.

## Simulação reproduzível

Nenhuma conexão real com radares, guardas, polícia ou prefeitura está prevista. A primeira versão usa sementes sintéticas identificadas e histórico gerado de maneira reproduzível. As coordenadas são demonstrativas, sem alegação de representar ativos reais.

Separar carga inicial de dados, geração de histórico e avanço operacional. Definir identificação dos eventos, relógio de referência e política de repetição para que uma nova instalação reproduza o cenário. Carga ou reset destrutivo deve se recusar a executar contra um banco fora do ambiente de demonstração explicitamente designado.

O visitante público consulta. O proprietário opera a demonstração autenticado ou por tarefa protegida. O avaliador pode executar CRUD e simulação completos na instalação local, com credenciais criadas nessa instalação e instruções claras.

## Implantação sob o caminho pedido

Repositório e diretório de execução próprios. O servidor encaminha somente `/promotheusfiscaliza` e seus descendentes à aplicação dedicada; o restante do portal continua no atendimento atual. Preferir processo PHP-FPM/container e usuário de sistema separados, com permissão de leitura apenas sobre os arquivos do desafio.

O Claude deve documentar o desenho adequado ao Apache/proxy realmente disponível. Um alias ou uma regra de reescrita, sozinho, não cria isolamento de processo. Quando a hospedagem não permitir isolamento suficiente, entregar ambiente local reproduzível e registrar a limitação; não reutilizar segredos ou permissões amplas como atalho.

Somente a área pública da aplicação fica exposta. Configuração, código interno, banco, backups, sessões e logs ficam fora do alcance HTTP. A URL base é uma configuração da aplicação: assets, formulários, redirecionamentos, fetch, login/logout e navegação direta devem funcionar com o prefixo.

Um link público para o portfólio pode fornecer continuidade de navegação. Evitar inicializar o framework inteiro do portal, incluir seus arquivos ou acessar seus serviços internos. O capítulo de segurança descreve a limitação inevitável de compartilhar a mesma origem do navegador.

## Qualidade de PHP

Aplicar autoload PSR-4, estilo consistente alinhado ao PER Coding Style atual, tipos explícitos onde úteis, validação de fronteira e dependências fornecidas de forma clara. Escolher análise estática e testes compatíveis com a versão PHP, em configuração proporcional ao projeto.

Controladores curtos e consultas dedicadas devem resultar de responsabilidades reais. Evitar abstração genérica para cada tabela, classes vazias e comentários que apenas repitam o código. Preferir nomes que expliquem a intenção; comentários e PHPDoc explicam regras, formatos, exceções e decisões que o código não deixa evidentes. Manter termos do domínio consistentes entre interface, banco e documentação.

Usar horário UTC para instantes quando a estratégia do driver permitir representação inequívoca, exibir em America/Sao_Paulo e tratar datas de manutenção sem horário como datas locais. Documentar a convenção e verificá-la em integração com o Firebird.
