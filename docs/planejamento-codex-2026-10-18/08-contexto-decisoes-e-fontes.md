# Contexto preservado, decisões e fontes

## Registro da sessão

**Data:** 18/10/2026. **Responsável pelo planejamento:** Codex. **Implementador pretendido:** Claude, selecionado pelo proprietário como Opus 5.5.

**Pasta inspecionada, somente leitura:** C:/Users/FelipedosSantosGomes/Desktop/PromoTheus - fiscaliza. A listagem inicial, incluindo itens ocultos, estava vazia. Não havia código, documentação ou repositório local a aproveitar nesse diretório. Não foi feita busca em outros projetos privados.

**Destino da entrega:** C:/Users/FelipedosSantosGomes/.codex/entregas-para-claude/promotheus-fiscaliza-2026-10-18. A regra de propriedade do AGENTS.md foi preservada. O pedido de deixar a pasta disponível dentro do projeto será atendido pela cópia feita pelo próprio Claude, conforme instrução no README/prompt.

**Página do desafio:** https://appcoderchallenge.dsin.com.br/challenge/, aba intitulada DSIN Coder Challenge no Chrome. Foi lida a etapa bônus, suas três partes, FAQ e campos de entrega. Não foram preenchidos campos nem acionado o envio. Não foram lidos e-mails ou outras abas pessoais.

**Site principal:** https://devfelipegomes.com.br/. Leitura pública via navegador e uma consulta de cabeçalhos HTTP. Sem login, busca de arquivos ocultos, exploração de vulnerabilidades ou alteração. A inspeção não revela o ambiente privado.

## Observações e limites de inferência

| Evidência | O que permite concluir | O que não permite concluir |
|---|---|---|
| Rodapé cita Laravel, Blade, TypeScript e GSAP | É a stack declarada publicamente pelo portal. | Versões, estrutura interna, extensões, dependências e compatibilidade Firebird. |
| Resposta HTTPS 200 e HSTS observado | A requisição inspecionada foi atendida com transporte HTTPS e esse cabeçalho. | Segurança de todas as rotas, certificados futuros ou políticas do provedor. |
| CSP, nosniff, bloqueio de enquadramento, referência e permissões | Há proteções de navegador na raiz consultada. | Correção de autenticação, autorização, SQL e tratamento de todos os dados. |
| CSP restringe imagens/conexões à própria origem | O mapa deve testar e configurar seus recursos por rota. | Que é necessário afrouxar a política de todo o domínio. |
| Controle de movimento e som desligado | O produto já tem precedentes de preferência de interação. | Que os scripts/estilos podem ser copiados do projeto privado para um repo aberto. |
| Firebird/PHP têm versões documentadas | Existe uma base técnica viável a investigar. | Que a hospedagem atual já oferece essa base. |

Não arquivar nonce, cookies ou valores temporários de cabeçalhos como configuração. O resumo dos cabeçalhos é suficiente para a decisão arquitetural.

## Registro das decisões importantes

| ID | Decisão | Justificativa e consequência |
|---|---|---|
| DEC-01 | Codex produz documentos fora do projeto do Claude | Respeita a regra explícita de propriedade; Claude copia/aplica. |
| DEC-02 | Planejar três partes em sequência, com um caso preventivo simples | O enunciado permite escolha e valoriza solução útil; atende à ambição sem dispersar o prazo. |
| DEC-03 | Repositório público próprio e histórico novo | Abertura do desafio não revela o portal privado. |
| DEC-04 | PHP + Firebird com prova inicial do ambiente | Preferência do proprietário e compatibilidade precisam coexistir. |
| DEC-05 | Framework e forma final de persistência ficam com o Opus | Sem código ou solução de classes pronta; evitar prescrição incompatível. |
| DEC-06 | Caminho público mantido, limite de mesma origem explícito | Subpasta e processo separado não isolam completamente o navegador. |
| DEC-07 | Dados sintéticos e demo pública de leitura por padrão | Avaliação acessível sem dados de agentes/equipamentos reais nem escrita anônima global. |
| DEC-08 | Atualização periódica como primeira opção | Menor complexidade operacional para o desafio, com latência declarada. |
| DEC-09 | Mapa 2D e miniaturas reconhecíveis, 3D opcional | Responde ao pedido visual com menor custo e fallback acessível. |
| DEC-10 | Índice preventivo explicável, sem chamar score de probabilidade | Evita apresentar heurística e dados fictícios como previsão validada. |
| DEC-11 | ZIP compatível com limite além do GitLab | GitLab é pedido do proprietário; FAQ menciona outros provedores ao orientar links. |
| DEC-12 | Documentação cresce com o código | O documento final descreve o que realmente existe, não apenas a arquitetura imaginada. |

Essas decisões são recomendações para o implementador. Alterações de detalhe podem ser feitas com justificativa; segurança, privacidade, escopo autorizado e requisitos originais continuam obrigatórios.

## Fontes oficiais consultadas

Cada documento cita as fontes perto das recomendações. A pesquisa detalhada de versões, PDO, PSR, Composer e GitLab está em `07-pesquisa-php-firebird-fontes.md`.

| Tema | Fonte | Uso neste pacote |
|---|---|---|
| Requisitos e entrega | [DSIN Coder Challenge](https://appcoderchallenge.dsin.com.br/challenge/) | Partes, critérios gerais, prazo, limite de arquivo e declaração de IA. |
| Padrões públicos existentes | [Portal do proprietário](https://devfelipegomes.com.br/) | Observação de UX, stack declarada e cabeçalhos; sem prova de backend. |
| Mesma origem | [MDN](https://developer.mozilla.org/en-US/docs/Web/Security/Defenses/Same-origin_policy) | Limite de segurança ao compartilhar host e caminho. |
| Sessões | [OWASP](https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html) | Escopo de cookie e defesa em profundidade. |
| CSRF | [OWASP](https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html) | Proteção de escrita autenticada. |
| Consultas | [OWASP](https://cheatsheetseries.owasp.org/cheatsheets/SQL_Injection_Prevention_Cheat_Sheet.html) | Parâmetros e opções permitidas. |
| Saída e popups | [OWASP](https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html) | Dados exibidos como texto seguro. |
| Autorizações | [OWASP](https://cheatsheetseries.owasp.org/cheatsheets/Authorization_Cheat_Sheet.html) | Verificações no servidor e por recurso. |
| Segredos | [OWASP](https://cheatsheetseries.owasp.org/cheatsheets/Secrets_Management_Cheat_Sheet.html) | Configuração privada e resposta à exposição. |
| CSP | [OWASP](https://cheatsheetseries.owasp.org/cheatsheets/Content_Security_Policy_Cheat_Sheet.html) | Política restrita e integração do mapa. |
| PHP em produção | [OWASP](https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html) | Erros privados e revisão da configuração efetiva. |
| Mapa | [Leaflet](https://leafletjs.com/reference.html) | Marcadores, personalização e comportamento do mapa. |
| Tiles | [OSMF](https://operations.osmfoundation.org/policies/tiles/) | Atribuição, uso permitido e limites de serviço. |
| Geocodificação | [OSMF](https://operations.osmfoundation.org/policies/nominatim/) | Limites do Nominatim público; evitar autocomplete por tecla. |
| Movimento | [W3C C39](https://www.w3.org/WAI/WCAG22/Techniques/css/C39) | Preferência de redução de movimento. |
| Atualização contínua | [W3C 2.2.2](https://www.w3.org/WAI/WCAG22/Understanding/pause-stop-hide) | Controle de conteúdo que atualiza automaticamente. |
| Referência municipal histórica | [Diário Oficial de Marília](https://www.marilia.sp.gov.br/portal/diario-oficial/ver/3573) | Apenas indica existência histórica de fiscalização; não é inventário atual nem fonte dos pontos de demonstração. |

## Questões que o próximo agente deve resolver

1. Qual ambiente dedicado reproduz PHP/PDO/fbclient/Firebird e pode ser instalado no host escolhido?
2. Qual framework reduz trabalho sem depender de suporte Firebird ausente?
3. Como o Apache/proxy encaminhará o prefixo, emitirá CSP compatível e separará execução/arquivos?
4. O portal possui cookies, credenciais ou service workers cujo alcance torna inadequada a origem compartilhada?
5. Qual política final de estados, disponibilidade, eventos atrasados e histórico será implementada?
6. Qual desenho visual e conjunto de miniaturas atendem ao pedido com licença e tamanho adequados?
7. Como demonstrar o caso preventivo com evidência temporal e limites honestos?
8. A instalação em clone limpo e o pacote público permitem que o avaliador use o sistema sem os autores?

Resolver escolhas locais sem interromper o proprietário por detalhes rotineiros. Pedir informação apenas quando uma decisão depender de acesso, restrição ou preferência que não pode ser inferida. Enquanto isso, continuar o trabalho local independente.
