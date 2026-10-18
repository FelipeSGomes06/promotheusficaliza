# Requisitos, escopo e rastreabilidade

## Origem e leitura

Fonte: [página do DSIN Coder Challenge](https://appcoderchallenge.dsin.com.br/challenge/), seção da etapa bônus F.O.G.O., consultada no navegador em 18/10/2026. A página permite escolher uma ou mais partes. As tabelas abaixo reorganizam os requisitos em critérios verificáveis, sem reproduzir integralmente o enunciado.

Legenda: **DSIN** = requisito do enunciado; **proprietário** = solicitação de Felipe; **arquitetura** = decisão proposta neste pacote. Um detalhe recomendado não deve ser apresentado como exigência da DSIN.

## Parte 1 — inventário

| ID | Origem | Capacidade | Evidência de aceite |
|---|---|---|---|
| INV-01 | DSIN | Catalogar equipamentos de diversas categorias | Cadastro, edição, consulta e arquivamento coerentes; o sistema não se limita a radares. |
| INV-02 | DSIN | Identificação por tipo, série, marca e modelo | Campos persistem, aparecem no detalhe e respeitam as regras documentadas de obrigatoriedade e unicidade. |
| INV-03 | DSIN | Endereço de instalação ou da base | CEP, bairro, cidade, UF, logradouro, referência livre e GPS representados separadamente. A referência aceita número, quilômetro e descrição textual. |
| INV-04 | DSIN | Situação atual | Contemplar instalado/operacional, falha, desativação, furto e pendência de instalação; incluir manutenção para a parte 2. |
| INV-05 | DSIN | Manutenção imediata ou periódica/agendada | Prioridade e próxima data consultáveis; casos sem data e casos vencidos tratados explicitamente. |
| INV-06 | DSIN | Características específicas por categoria | Radar de velocidade possui limite; câmera possui resolução e capacidades; tipo não é apenas um rótulo visual. |
| INV-07 | DSIN | Cadastro de agentes de mobilidade e segurança | Perfis de trânsito, guarda e polícia com órgão, situação e localização de trabalho simulada. |
| INV-08 | arquitetura | Preservar histórico e autoria das alterações | Uma mudança relevante pode ser explicada depois; arquivamento não apaga eventos históricos. |

O catálogo deve acomodar, sem exigir campos inadequados, radares, câmeras, balanças, cancelas, totens, veículos de fiscalização/transporte, patinetes e veículos das equipes. O Claude decide os subtipos iniciais e a estratégia de extensão. Para radares, distinguir velocidade, semáforo e manobras fiscalizadas; um aparelho pode ter mais de uma função.

## Parte 2 — central operacional

| ID | Origem | Capacidade | Evidência de aceite |
|---|---|---|---|
| OPS-01 | DSIN | Mapa integrado com equipamentos e agentes | Marcadores distintos e detalhes coerentes com os cadastros. |
| OPS-02 | DSIN | Distinguir situação dos recursos | Estado reconhecível por texto/ícone e cor; legenda acessível. |
| OPS-03 | DSIN | Indicadores gerais | Total, operacionais, indisponíveis, disponibilidade e agentes ativos calculados no servidor. |
| OPS-04 | DSIN | Filtros de operação | Tipo, situação, bairro/região e órgão; resultados combinados sem divergência entre mapa e painel. |
| OPS-05 | DSIN | Destacar manutenção urgente e próxima | Manutenção imediata, vencida e a vencer visíveis com prazo e motivo. |
| OPS-06 | DSIN | Alertas de criticidade | Evento crítico gera destaque rastreável, sem duplicação incessante. |
| OPS-07 | DSIN | Detalhes a partir do mapa | Selecionar marcador abre recurso correspondente e seu contexto operacional. |
| OPS-08 | DSIN | Visão consolidada por região | Lista ou resumo revela concentração de problemas e denominadores dos indicadores. |
| OPS-09 | DSIN | Informações continuamente atualizadas | Alteração de evento/posição chega à tela com horário; atraso e desconexão aparecem claramente. |
| OPS-10 | arquitetura | Evidenciar simulação e procedência | A interface informa que eventos e agentes são fictícios, sem sugerir integração com a prefeitura. |

Atualização periódica pode atender ao protótipo desde que a latência seja declarada e demonstrada. Não vender atualização por consulta periódica como entrega instantânea. SSE/WebSocket são decisões condicionadas à necessidade e ao ambiente, não requisitos do enunciado.

## Parte 3 — cidade preditiva

| ID | Origem | Capacidade | Evidência de aceite |
|---|---|---|---|
| PRE-01 | DSIN | Antecipar um problema útil com dados e histórico | Pelo menos um caso futuro claramente definido, com horizonte e recomendação. |
| PRE-02 | DSIN | Explicar a previsão | Exibir dados usados, período analisado, método, limitações e utilidade operacional. |
| PRE-03 | DSIN | Liberdade de método | Regras ou estatística são suficientes; ML/IA não são obrigatórios. |
| PRE-04 | arquitetura | Priorizar manutenção preventiva nos próximos sete dias | Histórico de falhas e manutenção produz uma classificação explicável de atenção. |
| PRE-05 | arquitetura | Mostrar ausência/insuficiência de evidência | Histórico pequeno ou desatualizado não gera certeza artificial. |

O caso PRE-04 é a opção inicial recomendada, não uma imposição do desafio. O Claude pode propor outro caso simples se explicar a vantagem e preservar escopo, honestidade e demonstração. Não criar rankings de pessoas nem inferências reais sobre criminalidade em bairros.

## Pedidos do proprietário

| ID | Pedido | Consequência |
|---|---|---|
| USR-01 | PHP | Regras e servidor implementados em PHP; JavaScript/TypeScript pode atender à interface. |
| USR-02 | Preferência por Firebird | Primeiro banco a validar. Troca precisa ser justificada ao proprietário, nunca escondida por mocks. |
| USR-03 | GitLab e código aberto | Repositório autônomo, instalação reproduzível e licença aberta escolhida com consciência. |
| USR-04 | Preservar site principal | Não incorporar repositório, configurações privadas, dados, usuários ou credenciais do portal. |
| USR-05 | Caminho público definido | Todos os fluxos funcionam sob `/promotheusfiscaliza`, inclusive assets, sessão e navegação direta. |
| USR-06 | Mapa de Marília/SP, Brasil | Abrir no município; equipamentos e agentes de demonstração contextualizados nessa cidade. |
| USR-07 | Experiência divertida e animada | Movimentos funcionais, ilustrações reconhecíveis e apresentação memorável com acessibilidade. |
| USR-08 | Modelos dos pontos | Ícones próprios e miniaturas que representem o equipamento; 3D opcional se útil e viável. |
| USR-09 | Código limpo, documentado e explicação final detalhada | Documentação cresce com as entregas e descreve a implementação efetiva. |
| USR-10 | Opus 5.5 implementará e pensará nas soluções | Este pacote define problemas, limites e aceite; não entrega algoritmos ou estrutura de classes pronta. |

## Escopo recomendado até o prazo

**Compromisso principal:** partes 1 e 2 completas, sem fluxos simulados apenas na interface; parte 3 com um mecanismo preventivo simples, demonstrável e explicado.

**Diferenciais após o núcleo:** visualização histórica, agrupamentos de marcadores, cenários narrados, modelos 3D sob demanda, exportações e indicadores adicionais. Cortar nessa ordem inversa quando necessário.

**Fora da primeira entrega:** integração real com IoT municipal, multas, reconhecimento facial/placas, geolocalização de pessoas reais, autorização de trânsito, aplicativo móvel nativo, broker de mensagens, microsserviços, alta disponibilidade e treinamento de modelos externos.

## Entrega ao avaliador

Preparar código público no GitLab, README de instalação, versões e dependências fixadas, dados sintéticos reproduzíveis, documentação de domínio/segurança, evidência dos testes e vídeo curto. O ZIP deve caber em 50 MB e não incluir dependências reinstaláveis, cache ou segredos. Ao usar link, validar acesso em janela sem login. Registrar quais IAs de fato foram utilizadas; não declarar uma versão do Claude apenas porque está no título deste pacote.

Não preencher o formulário nem assumir que a publicação já constitui submissão. São ações posteriores do proprietário.
