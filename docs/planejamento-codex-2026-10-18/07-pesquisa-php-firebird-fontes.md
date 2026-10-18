# Pesquisa técnica: PHP, Firebird, qualidade e publicação no GitLab

Pesquisa realizada em **18/10/2026**, usando documentação oficial. Este arquivo orienta decisões do Claude Opus 5.5; não contém implementação, SQL, scripts ou configurações prontas para execução. As recomendações de arquitetura abaixo são decisões propostas para este desafio, identificadas separadamente dos fatos documentados.

## 1. Base tecnológica proposta e condição de viabilidade

**Recomendação:** PHP 8.5 com o patch estável corrigido disponível no ambiente e Firebird da série 5.0. PHP 8.4 é uma alternativa se a hospedagem ainda não oferecer 8.5 e a matriz real de dependências e driver estiver validada. Não começar um projeto novo em PHP 8.2 apenas porque o provedor o oferece.

Na data da pesquisa, a tabela oficial informa:

| Série PHP | Suporte ativo até | Correções de segurança até | Implicação para a decisão |
| --- | --- | --- | --- |
| 8.5 | 31/12/2027 | 31/12/2029 | Preferência para uma aplicação nova, condicionada ao ambiente. |
| 8.4 | 31/12/2026 | 31/12/2028 | Alternativa ainda mantida; documentar atualização futura. |
| 8.3 | Encerrado em 31/12/2025 | 31/12/2027 | Já recebe somente correções de segurança. |
| 8.2 | Encerrado em 31/12/2024 | 31/12/2026 | Vida útil restante curta para iniciar este projeto. |

Revalidar essa tabela antes da implantação. Não se prescreve aqui um patch específico do PHP. [Fonte: ciclo oficial de suporte do PHP](https://www.php.net/supported-versions.php).

A página oficial de downloads do Firebird apresenta **5.0.4, de 17/04/2026**, e também lista pacotes das linhas 4.0 e 3.0. A listagem não deve ser interpretada, sozinha, como garantia de manutenção de todas essas linhas. Isso confirma uma opção estável da série 5.0; não prova que essa versão está instalada no servidor do usuário. Conferir o patch corrigido disponível no momento da instalação e evitar versões de desenvolvimento. [Fonte: downloads oficiais do Firebird](https://www.firebirdsql.org/en/downloads/).

**Primeira decisão obrigatória do implementador:** demonstrar que a infraestrutura pode executar a combinação escolhida. A URL pública do site principal não informa a versão do PHP, disponibilidade de extensões, permissões de instalação ou acesso a um serviço Firebird. Não prometer publicação antes dessa confirmação.

## 2. PDO Firebird: o que existe e o que não deve ser presumido

O PHP oferece o driver **PDO_FIREBIRD**. Desde PHP 8.4, sua implementação usa as APIs C++ do Firebird e precisa ser compilada contra **fbclient 3.0 ou superior**; a compilação da extensão requer compilador C++. Uma hospedagem oferecer PHP não significa que ofereça esse driver e sua biblioteca cliente. Preferir pacotes mantidos adequados à plataforma e registrar as versões efetivamente usadas. [Fonte: manual oficial do PDO_FIREBIRD](https://www.php.net/manual/en/ref.pdo-firebird.php).

PDO padroniza a interface de acesso aos dados, **não reescreve SQL nem emula funcionalidades ausentes do banco**. Portanto, a existência do driver não demonstra que um ORM, gerador de migrações ou framework tenha suporte adequado ao Firebird. A recomendação inicial é acesso a dados com PDO por repositórios pequenos e SQL específico do Firebird, encapsulado na infraestrutura. Um framework leve pode ser escolhido pelo Opus, desde que não imponha outro banco ou complexidade dispensável. [Fonte: introdução oficial ao PDO](https://www.php.net/pdo).

O DSN do driver admite banco, charset, papel SQL e dialeto. **Recomendação:** definir explicitamente UTF-8 e dialeto 3 no contrato de infraestrutura, usar alias do banco quando viável e guardar os valores reais fora do repositório. Não copiar caminhos físicos ou credenciais do site principal para exemplos públicos. [Fonte: conexão PDO_FIREBIRD](https://www.php.net/manual/en/ref.pdo-firebird.connection.php).

**Cuidados de implementação a validar, sem pressupor incompatibilidades:**

- Retorno de identificadores gerados, paginação e nomes de colunas devem seguir o dialeto escolhido, sem copiar convenções de outro SGBD.
- A hidratação de booleanos, valores nulos, decimais, datas e textos extensos deve ter conversão explícita e testes de integração.
- Datas devem ter convenção documentada de armazenamento e apresentação; a interface usa o contexto de Marília e o fuso America/Sao_Paulo.
- Caracteres de nomes de ruas, bairros e observações precisam sobreviver ao percurso completo entre formulário, PHP, Firebird, JSON e interface.

Esses itens são verificações propostas, não afirmações de que determinado tipo esteja quebrado no driver.

## 3. Modelo de banco e migrações

O Firebird possui colunas de identidade, mas a identidade por si só não estabelece unicidade: a tabela ainda precisa da restrição correspondente. Colunas de texto aceitam charset e collation, e há regras próprias para mudanças de estrutura. **Recomendação:** usar chaves primárias, estrangeiras, unicidade e restrições coerentes com as regras reais do desafio; definir UTF-8 desde a criação e versionar as alterações de esquema. Não deixar integridade exclusivamente na interface. [Fonte: definição e alteração de tabelas no Firebird 5](https://www.firebirdsql.org/file/documentation/chunk/en/refdocs/fblangref50/fblangref50-ddl-table.html).

O Opus deve escolher uma estratégia simples de migração que demonstre suporte ao Firebird. A documentação de cada migração deve informar seu propósito, dependências, risco de perda de dados e recuperação prevista. Testar a criação a partir de banco vazio e uma evolução com dados existentes; não afirmar reversibilidade de toda alteração. Não executar migração automaticamente a cada requisição web.

O contrato de persistência deve distinguir o usuário normal da aplicação do usuário de manutenção. A aplicação pública recebe apenas os privilégios necessários aos seus casos de uso; alterações estruturais ficam no processo controlado de implantação. Restringir os privilégios ao banco deste desafio.

## 4. Consultas e transações

Consultas com dados externos devem usar parâmetros vinculados. Parâmetros representam valores completos; não substituem nomes de tabelas, colunas, palavras-chave ou uma lista inteira de valores. Por isso, ordenação e campos selecionáveis precisam de opções permitidas definidas pelo servidor. A documentação também exige cuidado ao reutilizar o mesmo marcador nomeado na mesma consulta. [Fonte: PDO::prepare](https://www.php.net/manual/en/pdo.prepare.php).

**Recomendação:** a transação deve corresponder a uma operação de negócio consistente. Abrir, executar as mudanças necessárias e encerrar com confirmação ou desfazimento, incluindo o caminho de falha. Evitar transações abertas enquanto o usuário interage com a tela. A API PDO possui comportamento de autocommit e transações explícitas; seu uso deve ser validado no driver real. [Fonte: transações no PDO](https://www.php.net/manual/en/pdo.transactions.php).

O driver específico expõe níveis de isolamento; a referência atual informa READ COMMITTED como padrão e descreve equivalências com os modos snapshot do Firebird. **Recomendação:** escolher isolamento e tratamento de conflitos a partir dos casos de uso, registrar a escolha e testar duas conexões concorrentes. Definir resposta compreensível para conflitos, tempo de espera limitado e repetição apenas onde for segura. Não assumir que os nomes dos níveis produzem comportamento idêntico em bancos diferentes. [Fonte: classe específica do driver Firebird](https://www.php.net/manual/en/class.pdo-firebird.php).

## 5. Arquitetura limpa proporcional ao desafio

**Decisão proposta:** um monólito modular, com um único processo de implantação e responsabilidades visíveis. Uma estrutura suficiente separa:

| Responsabilidade | O que deve conter |
| --- | --- |
| Entrada HTTP | Rotas, leitura de requisições, validação de formato, respostas e códigos HTTP. |
| Casos de uso | Fluxos de negócio, permissões aplicáveis, coordenação de transações e resultados. |
| Modelo do domínio | Conceitos, estados e regras que não dependem do servidor HTTP ou do Firebird. |
| Persistência e integrações | SQL Firebird, mapeamento de dados, conexão e acesso a serviços externos. |
| Apresentação | Templates, interação com mapa, estados visuais, acessibilidade e animações. |

O Opus pode combinar pastas ou reduzir camadas quando isso tornar o código mais legível. Evitar microserviços, barramento de eventos, repositórios genéricos e uma interface para cada classe sem necessidade concreta. A UI não deve conter SQL; a regra de negócio não deve depender de DOM ou do formato de um marcador no mapa.

Essas escolhas são julgamento de arquitetura para um sistema pequeno, não uma arquitetura obrigatória prescrita pelo PHP-FIG.

## 6. Convenções e documentação do PHP

- **PSR-4** para organização de namespaces e autoload. [Especificação oficial](https://www.php-fig.org/psr/psr-4/).
- **PER Coding Style** em uma versão compatível com as ferramentas adotadas. A própria especificação informa que expande e substitui PSR-12; não tratar PSR-12 como a única orientação atual. [Especificação oficial](https://www.php-fig.org/per/coding-style/).
- Tipos de parâmetros, retornos e propriedades quando informativos; política consistente de tipagem estrita nos arquivos da aplicação. A tipagem estrita tem escopo e limitações documentados e não substitui validação de entrada HTTP, limites e regras de negócio. [Manual de tipos do PHP](https://www.php.net/manual/en/language.types.declarations.php).
- Logs com interface compatível com **PSR-3**, quando uma biblioteca de logs for necessária. O padrão define a interface de logging, mas a política de remoção de segredos e retenção precisa ser definida pela aplicação. [Especificação oficial](https://www.php-fig.org/psr/psr-3/).

**Recomendação de documentação:** explicar principalmente decisões, contratos, unidades, estados, exceções e motivos. Nomes claros e funções coesas reduzem comentários que apenas repetem a instrução seguinte. Documentar módulos e casos de uso junto da implementação; ao final, revisar o documento funcional completo contra o código efetivamente entregue.

## 7. Dependências e reprodutibilidade

O Composer documenta que o arquivo de lock registra as versões exatas e deve acompanhar o repositório da aplicação. Instalações de CI e produção devem partir desse lock; atualização deliberada é uma operação distinta. O diretório de dependências não precisa integrar o histórico do projeto. [Fonte: uso básico do Composer](https://getcomposer.org/doc/01-basic-usage.md).

**Recomendação:** manter poucas dependências, verificar manutenção, licença e compatibilidade antes da adoção. Na CI, validar o manifesto e o lock, auditar os pacotes e verificar os requisitos reais de plataforma. A verificação de plataforma do Composer examina PHP e extensões reais; não substituir esse controle por uma plataforma simulada nem ignorar requisitos para fazer a instalação parecer bem-sucedida. O audit identifica problemas conhecidos nas bases consultadas e não prova ausência de vulnerabilidades. [Fonte: comandos oficiais do Composer](https://getcomposer.org/doc/03-cli.md).

Plugins e scripts do Composer podem executar código com as permissões de quem os invoca. **Recomendação:** revisar o que será executado, evitar privilégios administrativos no build e isolar a execução de dependências não confiáveis. [Fonte: segurança da instalação de pacotes no Composer](https://getcomposer.org/doc/faqs/how-to-install-untrusted-packages-safely.md).

## 8. Operação do PHP e Firebird

Em produção, o PHP deve registrar erros internamente e não mostrar detalhes técnicos ao visitante. A configuração deve valer também para falhas de inicialização; tratá-la somente durante a execução da aplicação pode ser insuficiente. **Recomendação:** respostas de erro genéricas com identificador de correlação e logs privados sem senhas, tokens, sessão ou dados pessoais desnecessários. [Fonte: configuração oficial de erros do PHP](https://www.php.net/manual/en/errorfunc.configuration.php).

O guia do Firebird recomenda proteger os arquivos do banco no sistema de arquivos, usar aliases e restringir os bancos que o servidor pode abrir. Também descreve os riscos do acesso embutido e da autenticação legada. **Recomendação:** executar o Firebird como serviço separado, limitar os arquivos ao processo do banco, usar autenticação moderna e criptografia de transporte compatível, e restringir a rede ao backend autorizado. Banco, backup e banco de segurança ficam fora do diretório servido pela web. Nunca usar SYSDBA como identidade normal da aplicação. [Fonte: guia rápido oficial do Firebird 5, segurança](https://www.firebirdsql.org/file/documentation/html/en/firebirddocs/qsg5/firebird-5-quickstartguide.html).

Prever backup próprio do banco do desafio e uma restauração testada em ambiente separado. Utilizar ferramentas apropriadas do Firebird em vez de copiar arbitrariamente o arquivo de um banco em uso. A restauração deve conferir estrutura, dados de exemplo e funcionamento da aplicação; possuir um arquivo de backup não basta para demonstrar recuperação. [Fonte: ferramenta oficial gbak](https://www.firebirdsql.org/file/documentation/html/en/firebirddocs/gbak/firebird-gbak.html).

## 9. Repositório público e CI do GitLab

**Decisão proposta:** repositório independente contendo somente o sistema do desafio. Não importar o histórico do site principal nem compartilhar suas credenciais, dumps, certificados, backups, logs, arquivos de sessão ou configuração real. Publicar exemplos fictícios e dados de demonstração claramente identificados. O inventário público pode documentar nomes e finalidade das variáveis sem expor seus valores reais.

A documentação do GitLab alerta que scripts maliciosos de CI podem capturar variáveis inclusive mascaradas e protegidas. **Recomendação:** executar a CI de contribuições externas com credenciais descartáveis do ambiente de teste; disponibilizar credenciais de implantação somente no fluxo autorizado e limitado a esse projeto. Revisar mudanças na pipeline antes de executá-las com segredos. Quando necessário, usar um gerenciador de segredos; como alternativa proporcional, variáveis protegidas, mascaradas, ocultas e com escopo adequado. [Fonte: segurança das variáveis CI/CD](https://docs.gitlab.com/ci/variables/).

A detecção de segredos em pipeline ocorre depois do commit e envio ao GitLab; a documentação prevê uma varredura histórica inicial para detectar conteúdo antigo. **Recomendação:** inspecionar arquivos e histórico antes do primeiro envio público, adicionar a verificação contínua e revogar/rotacionar um segredo exposto. Apagar apenas o arquivo atual não resolve a exposição anterior. Não prometer recursos de revisão de vulnerabilidades restritos a um plano comercial sem confirmar o plano da conta. [Fonte: secret detection em pipeline](https://docs.gitlab.com/user/application_security/secret_detection/pipeline/).

## 10. Provas obrigatórias antes de consolidar a implementação

Os itens abaixo são critérios propostos de aceite técnico, não resultados já obtidos:

1. **Ambiente real:** comprovar conexão PHP → PDO_FIREBIRD → fbclient → Firebird no mesmo sistema operacional e modo de execução planejados para a implantação. Conferir também diferenças entre PHP de terminal e PHP do servidor web.
2. **Banco vazio:** instalar dependências pelo lock, criar o esquema pelas migrações escolhidas e carregar apenas dados fictícios documentados. Repetir em um ambiente limpo.
3. **Integração do driver:** demonstrar gravação e leitura parametrizadas, acentos, valores nulos, tipos usados pelo domínio, retorno de identificadores, ordenação e paginação.
4. **Atomicidade:** demonstrar confirmação e desfazimento de uma operação com múltiplas gravações, incluindo falha intermediária.
5. **Concorrência:** usar duas conexões para verificar o conflito relevante ao domínio e sua resposta; manter transações curtas e encerrar todos os caminhos.
6. **Migração e recuperação:** testar alteração com dados existentes, falha de migração e restauração de backup em banco separado.
7. **Qualidade:** executar análise estática, verificação de estilo, auditoria de dependências e testes dos fluxos de negócio relevantes. Testes de persistência precisam executar no Firebird; SQLite ou mocks não demonstram compatibilidade com ele.
8. **Publicação isolada:** inspecionar o conteúdo e o histórico destinados ao GitLab e demonstrar que o ambiente de teste não depende de arquivos ou segredos do site principal.

Se uma dessas provas falhar, registrar o bloqueio e ajustar a decisão de infraestrutura antes de construir funcionalidades sobre a suposição. Não trocar silenciosamente o banco por outro nem declarar a entrega pronta com testes feitos apenas contra uma simulação.

## 11. Informações que continuam em aberto

Esta pesquisa não inspecionou o servidor privado, o código do site principal, o plano de hospedagem, o GitLab do usuário ou os requisitos completos da página do desafio. Portanto, não comprova suporte a Firebird no host, compatibilidade de um framework específico, segurança atual do site principal ou quantidade necessária de conexões. Essas informações devem vir do levantamento do projeto e de verificações autorizadas, sem copiar dados privados para o material público.
