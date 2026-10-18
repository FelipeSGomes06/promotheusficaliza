# Segurança, isolamento do portal e publicação aberta

## Objetivo e fronteiras

O código do desafio será público; o código, os dados e os segredos do portfólio continuam privados. Segurança não depende de esconder a implementação. A proposta é uma avaliação pública com dados sintéticos, administração protegida e infraestrutura própria do desafio.

Esta é uma arquitetura preventiva, não um laudo de segurança. Foram inspecionados somente a página pública e seus cabeçalhos, sem testes ofensivos, enumeração de arquivos sensíveis ou acesso administrativo.

## O que existe no site e pode inspirar o desafio

Na consulta HTTPS de 18/10/2026 à raiz do domínio, foram observados CSP restritiva com nonce, bloqueio de objetos e enquadramento, `nosniff`, política de referência, restrição de permissões, COOP/CORP e HSTS com duração de um dia. A página oferece redução de movimento e inicia sem som. O rodapé informa Laravel, Blade, TypeScript e GSAP.

Esses fatos permitem reaproveitar **padrões de proteção, identidade e acessibilidade**, não concluir que versões, sessões, autenticação ou validações do backend estejam corretas. O nonce é gerado por resposta; nenhum valor observado deve virar configuração fixa. Inventário das observações em `08-contexto-decisoes-e-fontes.md`.

## Isolamento em quatro níveis

| Nível | Decisão proposta | Evidência necessária |
|---|---|---|
| Código e Git | Repositório novo contendo apenas o desafio | Clone público não contém arquivos nem histórico do portal. |
| Execução e arquivos | Processo/container ou pool e usuário próprios | Processo do desafio não lê configuração, backups, sessões ou código privado do portal. |
| Dados e segredos | Banco, usuário, chaves e sessões próprios | Credenciais do desafio não autorizam acesso ao banco ou serviços do portal. |
| Navegador | Origem compartilhada no caminho solicitado | Limitação registrada, sem prometer separação total por pasta ou cookie. |

Uma subpasta não muda a origem: esquema, host e porta determinam esse limite. Scripts comprometidos em uma aplicação na mesma origem podem atingir respostas e recursos da outra aplicação. Separação de processo protege o servidor, mas não elimina esse vínculo no navegador. [Fonte: política de mesma origem, MDN](https://developer.mozilla.org/en-US/docs/Web/Security/Defenses/Same-origin_policy).

Manter o endereço pedido é viável com mitigação, mas **isolamento completo de aplicações com níveis de confiança diferentes exige outra origem**. Se a avaliação da infraestrutura exigir esse grau de proteção, apresentar ao proprietário uma proposta concreta de origem dedicada, eventualmente acessível por link/redirecionamento do caminho pedido. Isso altera o comportamento do endereço e deve ser decidido antes do deploy, não presumido neste plano.

Antes de publicar no caminho compartilhado, revisar no ambiente autorizado o alcance dos cookies do portal, autenticação, permissões de arquivos e eventuais service workers. Um service worker com controle da raiz pode alcançar o desafio; não assumir sua existência ou ausência pela página pública. O desafio não instala service worker de escopo raiz.

## Sessões, autenticação e autorização

Usar sessão mantida no servidor com nome próprio, armazenamento separado, HTTPS, cookie Secure/HttpOnly e SameSite apropriado. Sem atributo Domain e com Path limitado ao caminho da aplicação quando essa for a implantação. Isso reduz envio acidental, **não cria fronteira de segurança entre caminhos**. O prefixo `__Host-` exige Path raiz e, portanto, não combina com uma recomendação de Path restrito; não prescrever os dois juntos. Regenerar sessão após login/mudança de privilégio e invalidá-la no logout. [Fonte: gestão de sessões, OWASP](https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html).

Criar três capacidades proporcionais: visitante de leitura dos dados sintéticos; operador autenticado para cadastros e operação; administrador para contas/configuração restrita. O Claude pode simplificar papéis sem eliminar a distinção entre leitura pública e escrita protegida. Verificar autorização em toda requisição e no recurso solicitado, inclusive detalhes, mudanças de situação e endpoints usados pelo mapa. [Fonte: autorização, OWASP](https://cheatsheetseries.owasp.org/cheatsheets/Authorization_Cheat_Sheet.html).

Não oferecer cadastro público de administradores. Credenciais locais de avaliação são criadas no bootstrap com dados fornecidos/gerados no ambiente, não uma senha de produção versionada. A demo pública pode ser somente leitura; o README explica como testar todos os fluxos localmente. Um eventual ambiente de escrita para avaliadores deve ser descartável e isolado antes de ser exposto.

Usar as funções mantidas do PHP para hash/verificação de senha, com parâmetros adequados ao servidor e estratégia de atualização de hash. Não criptografar senha reversivelmente. Login tem limite de tentativas, expiração por inatividade e mensagens sem detalhar a existência de conta. Não guardar tokens/senhas no armazenamento JavaScript. [Fontes: password_hash, PHP](https://www.php.net/manual/en/function.password-hash.php) e [segurança de sessões, PHP](https://www.php.net/manual/en/features.session.security.management.php).

Mudanças autenticadas exigem proteção CSRF. SameSite complementa a defesa e não substitui token nem protege completamente contra aplicações no mesmo site. Leitura GET não altera estado. Validar origem quando pertinente e não abrir CORS indiscriminado com credenciais. [Fonte: prevenção de CSRF, OWASP](https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html).

## Entrada, saída e banco

Validar no servidor formato, faixa, tamanho, campos permitidos, combinações e transições. Rejeitar atributos inesperados em alterações; o cliente não escolhe papel, autoria, origem confiável ou campos internos. Validar coordenadas, datas, tipos, filtros e paginação.

SQL recebe valores por parâmetros; nomes de colunas e ordenação vêm de lista permitida do servidor. Não aceitar SQL, operadores livres ou fragmentos do usuário. Garantir integridade relevante por restrições do Firebird e tratar erro sem expor SQL/DSN. Contas normais não usam SYSDBA nem privilégios de migração. [Fonte: prevenção de SQL injection, OWASP](https://cheatsheetseries.owasp.org/cheatsheets/SQL_Injection_Prevention_Cheat_Sheet.html).

Templates e popups do mapa precisam tratar endereço, série, nome e observações como texto não confiável. Escapar conforme contexto; evitar montar HTML com concatenação de campos externos. Não aceitar HTML/SVG enviado por usuários. Recursos visuais locais são criados ou revisados pelo projeto. [Fonte: prevenção de XSS, OWASP](https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html).

Não criar upload/importação no núcleo sem necessidade. Não aceitar URL arbitrária para buscar foto, ícone ou integração. Se uma função dessas for escolhida, especificar e validar os riscos próprios antes de expô-la. O escopo atual pode atendê-los com assets locais e provedores fixos.

## Cabeçalhos e mapa sem enfraquecer o portal

A CSP observada só permite imagens da própria origem. Um provedor externo de tiles exige permissão específica na resposta do desafio. Assets JavaScript/CSS/fontes preferencialmente locais e com versões fixadas; abrir apenas os hosts e diretivas necessários à biblioteca escolhida. Evitar curingas, execução dinâmica e afrouxamento global para resolver um erro visual.

Identificar quem emite os cabeçalhos: aplicação, Apache e eventual proxy podem aplicar políticas simultâneas. Múltiplas CSP podem continuar bloqueando o mapa mesmo que uma delas seja permissiva. Validar a política efetiva em rotas do desafio e a preservação da política da raiz. [Fonte: Content Security Policy, OWASP](https://cheatsheetseries.owasp.org/cheatsheets/Content_Security_Policy_Cheat_Sheet.html).

Testar estilos/ícones da biblioteca com a política aplicada; não presumir compatibilidade ou bloqueio completo de estilos apenas pela configuração nominal. Nonces, estilos locais e comportamento real precisam ser coerentes. O mapa de Marília não exige localização do visitante: a permissão de geolocalização pode continuar desabilitada.

Preservar HTTPS e as proteções adequadas de conteúdo/enquadramento/referência. Não alterar HSTS do domínio inteiro, incluir subdomínios ou ativar preload como efeito colateral do desafio. Configuração de produção deve ocultar erros técnicos e manter logs privados. [Fonte: configuração PHP, OWASP](https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html).

## Rede, operação e abuso

Firebird atende somente à rede privada/backend autorizado, com conta dedicada e configuração de transporte apropriada. Não publicar sua porta na Internet. Banco físico, backups, logs e arquivos de sessão ficam fora da área pública e fora do Git. Documentar backup/restauração, espaço em disco e retenção do histórico.

Limitar login, consultas de mapa/histórico e tamanho/frequência das requisições. Usar tempo máximo de execução e paginação. Erros públicos recebem mensagem útil e identificador; logs registram contexto mínimo, sem credenciais, cookies, corpos completos ou localização real de pessoas.

Nenhum endpoint público faz migração, reset global, criação de administrador, execução de shell ou geração ilimitada de histórico. O simulador é tarefa controlada. Health check público não devolve versão detalhada, caminhos, segredos ou conteúdo do banco.

## GitLab aberto, instalação privada

Publicar somente código e material sanitizado do desafio, arquivos de dependências/lock, migrações, documentação e dados fictícios. Usar exemplo de configuração com valores obviamente fictícios, descrições das variáveis e instrução de fornecimento pelo ambiente. Não copiar `.env`, usuários, cookies, dumps, tokens de e-mail, certificados ou configurações do portal. [Fonte: gestão de segredos, OWASP](https://cheatsheetseries.owasp.org/cheatsheets/Secrets_Management_Cheat_Sheet.html).

Antes do primeiro push, revisar o conteúdo **e o histórico** com varredura local de segredos. Ignore de Git não remove arquivos já versionados. CI roda qualidade e integração com Firebird de teste, usando credenciais descartáveis. Credenciais de deploy, quando existirem, pertencem só ao desafio e não são entregues a pipelines de contribuições não confiáveis. Detalhes e limites dos recursos GitLab constam na pesquisa técnica.

Código visível precisa de licença para ser de fato reutilizável como código aberto. O Claude deve recomendar uma licença compatível com o objetivo, identificar autoria e manter avisos de terceiros; não aplicar licença própria ao enunciado, marcas ou assets alheios. Registrar procedência e licença de mapas, ícones, fontes e modelos.

Se houver exposição acidental, revogar/rotacionar a credencial e corrigir a origem; apagar o arquivo atual não basta. Este pacote não contém valores de segredo nem depende de recebê-los.
