# Manual do projeto — Promotheus Fiscaliza

Este manual percorre o projeto inteiro: o que ele é, como está montado, o que cada área faz e como, de onde vêm os
dados, como instalar e operar, e como saber o que foi verificado. Cada seção resume o essencial e aponta a explicação
canônica (guia, referência ou decisão) para aprofundar. Estado descrito: código do commit `c507882` (25/10/2026) e
documentação de 26/10/2026.

## 1. O que é

Uma central de fiscalização urbana para **Marília/SP**, feita para a etapa bônus F.O.G.O. do DSIN Coder Challenge 2026:

- **inventário** de equipamentos (radar, câmera, balança, cancela, totem, veículo, patinete) e de agentes de campo;
- **Visão geral** com mapa, lista e indicadores que se atualizam sozinhos;
- **alertas** que nascem e somem com a causa, com acompanhamento humano;
- **manutenção** com uma intervenção por vez;
- **revisões recomendadas**: heurística explicável sobre falhas e preventivas;
- **planejar reforço**: onde concentrar agentes na semana, com contagens **reais** de sinistros;
- **multas**: registro **demonstrativo** e manual, com conferências e histórico;
- **contas** de acesso: só entra quem o administrador cadastrou;
- uma **landing pública** que apresenta o sistema.

### Real × fictício (vale para todo o projeto)

| Real | Fictício |
|---|---|
| contagens agregadas de sinistros do Infosiga-SP/Detran-SP (CC BY 4.0), só em Planejar reforço | órgãos, equipamentos, agentes, posições, sinais, falhas, alertas, ordens, missões, multas |
| nomes de bairros, ruas e CEPs de Marília (referência geográfica) | coordenadas dos recursos (ilustrativas) |
| regras, validações, transações, histórico e concorrência | qualquer integração com órgãos, equipamentos, Detran/Senatran ou cobrança — **não existe** |

Três pares de conceitos que não se misturam:

- **Conta de acesso** (quem entra, em Contas) ≠ **agente de campo** (equipe fictícia no mapa).
- **Base de trabalho** (ponto fixo cadastrado do agente) ≠ **última posição** (o que o rádio simulado informou).
- **Ciência de um alerta** (alguém viu) ≠ **resolução da causa** (a condição deixou de existir).

## 2. Como está montado

- **Visitante** → landing em `/` (PHP sem banco nem sessão).
- **Conta** → Apache em `/promotheusfiscaliza` → `public/index.php` → Slim (`Http`).
- **Operador** → `bin/fiscaliza` → `Console`.
- `Http` e `Console` → `Application` (casos de uso, transações curtas) → `Domain` (regras puras) e `Infrastructure`
  (PDO_FIREBIRD, SQL, ViaCEP, Nominatim) → Firebird 5 na rede interna do Compose.

- **Stack:** PHP 8.5, Slim 4, Twig 3, PDO_FIREBIRD sem ORM, Firebird 5; Vite + Tailwind 4 + Alpine (build CSP) + GSAP
  + Leaflet local; three.js só na landing. Versões: [implantação e CI](referencia/implantacao-e-ci.md#versões-declaradas).
- **Camadas:** `src/Domain` (regras puras), `src/Application` (casos de uso), `src/Infrastructure` (banco e provedores),
  `src/Http` (web), `src/Cli`, `src/Demo` — [`src/README.md`](../src/README.md) e [arquitetura](arquitetura.md).
- **Uma requisição:** `SecurityHeaders` → `SessionContext` (relê a conta no banco) → erros → corpo → rota →
  `CsrfGuard` → `RequireRole` → controlador → serviço → transação → resposta (página, 303 com aviso ou JSON) —
  [middlewares](referencia/codigo/http/middlewares.md).
- **Telas:** Twig renderiza tudo no servidor; o JavaScript só melhora (mapa, atualização, campos condicionais). Sem
  JavaScript, tudo funciona por formulários comuns — [interface comum](guias/sobre-e-interface.md).
- **Banco:** 11 migrações; tabelas de histórico **só de acréscimo** para o usuário da aplicação (sem `UPDATE`/`DELETE`);
  índices únicos garantem as invariantes sob concorrência — [dicionário de dados](referencia/dicionario-de-dados.md).

### Garantias transversais

| Garantia | Como | Onde ler |
|---|---|---|
| Nada se perde numa edição concorrente | `VERSION_NO` em equipamentos, agentes e multas; `FOLLOWUP_NO` nos alertas; tela "Salvo agora × Sua versão" | [equipamentos](guias/equipamentos.md#editar-e-resolver-conflito-de-edição) |
| Uma intervenção por equipamento, uma missão por agente, um alerta aberto por causa, uma vaga de login por tentativa | índices únicos parciais (`UX_MAINT_ONE_RUNNING`, `UX_URBAN_MISSION_ACTIVE`, `OPEN_KEY`, `UX_LOGIN_ATTEMPT_SLOT`) + bloqueio `WITH LOCK` | [dicionário](referencia/dicionario-de-dados.md) |
| Envio repetido não duplica | botão bloqueia clique repetido; nas multas, `command_uid` + impressão digital | [multas](guias/multas.md#como-funciona-no-código) |
| Histórico confiável | eventos com autoria, origem, antes/depois; GRANTs só `SELECT, INSERT` | [banco e migrações](referencia/codigo/infrastructure/banco-e-migracoes.md#migrator) |
| Tempo coerente | instantes em UTC no banco, datas locais de Marília para prazos e urgência | [tempo](referencia/codigo/domain/tempo.md) |
| Segurança web | acesso só com conta, CSRF, verificação de `Origin`, CSP sem inline, escape, cota de login atômica, credenciais privilegiadas fora da web | [segurança](seguranca.md) |

## 3. As áreas, uma a uma

| Área | O que faz | Guia |
|---|---|---|
| Visão geral | mapa, lista e indicadores do **mesmo** filtro; ficha resumida; atualização a cada poucos segundos com um único dono do polling, descartando respostas antigas e preservando foco, seleção e mapa | [visão geral](guias/visao-geral.md) |
| Equipamentos | cadastro com características por tipo, local por mapa ou endereço, situação com transições permitidas, histórico, arquivamento | [equipamentos](guias/equipamentos.md) |
| Agentes | cadastro com base pelo mapa ou CEP/rua, situação, última posição, missão vigente | [agentes](guias/agentes.md) |
| Alertas | regras de condição, ciência, responsável (conta), providências, encerramento explicado | [alertas e acompanhamento](guias/alertas-e-acompanhamento.md) |
| Manutenção | fila por urgência; abrir, iniciar (uma por vez), concluir com o resultado real, cancelar | [manutenção](guias/manutencao.md) |
| Revisões recomendadas | heurística `atencao-v1` com ajuste e avaliação temporais; o índice não é probabilidade | [revisões](guias/revisoes-recomendadas.md) |
| Planejar reforço | quadrados de 2 km prioritários, agente sugerido, missão fictícia com reserva atômica; retrospecto | [planejar reforço](guias/planejar-reforco.md) |
| Multas | rascunho, registro com retrato da origem, conferências temporais, andamento informado, cancelamento interno | [multas](guias/multas.md) |
| Acesso e contas | login com cota, sessão de 30 min/8 h, contas, redefinição de senha que encerra sessões | [acesso e contas](guias/acesso-e-contas.md) |
| Sobre e interface | real × simulado, ajuda "?", avisos, movimento, teclado, celular, sem JavaScript | [sobre e interface](guias/sobre-e-interface.md) |
| Landing | página pública com capturas reais e 3D decorativo sob demanda | [landing](guias/landing.md) |

### Duas "previsões" diferentes

| | Revisões recomendadas | Planejar reforço |
|---|---|---|
| Pergunta | quais **equipamentos** revisar nos próximos 7 dias? | onde concentrar **agentes** na semana? |
| Dados | histórico **sintético** de falhas e manutenções | contagens **reais** agregadas de sinistros (Infosiga-SP) |
| Método na tela | heurística `atencao-v1` (pontos por fatores; limiar ajustado) | média das 52 semanas (referência histórica) por quadrado de 2 km |
| Candidatos só experimentados | — | Poisson nos setores (perdeu no ajuste; fica como comparação nos dados) e na grade (descartado) |
| Resultado medido | concentra falhas ~2× acima do acaso, com menos alertas que a referência | grade com 4 quadrados capturou 51,5% dos registros em 2026 contra 39,8% de um setor inteiro |
| Canônico | [análise preventiva](analise-preventiva.md) | [planejamento por setores](planejamento-por-setores.md) |

## 4. Fluxos de ponta a ponta

Cada fluxo abaixo está detalhado, com rota → controlador → serviço → regra → tabela → resposta → teste, no guia indicado.

1. **Atualização da Visão geral.** `GET /api/central` a cada intervalo; pedido novo cancela o anterior; só a resposta de
   sequência mais recente é aplicada → [visão geral](guias/visao-geral.md#atualização-periódica-e-perda-de-conexão).
2. **Cadastrar/editar equipamento, mudar situação, reconciliar alertas.** `EquipmentService` em transação, evento em
   `RESOURCE_EVENT`, `AlertService::reconcile` na mesma transação → [equipamentos](guias/equipamentos.md#como-funciona-no-código).
3. **Manutenção com tentativa concorrente.** trava equipamento → ordem; índice único como última barreira →
   [manutenção](guias/manutencao.md#como-funciona-no-código).
4. **Multa: criar, retificar, registrar, conferir, repetir envio.** → [multas](guias/multas.md#como-funciona-no-código).
5. **Alerta de agente até a resolução.** → [alertas](guias/alertas-e-acompanhamento.md#como-funciona-no-código).
6. **Missão com disputa pelo mesmo agente.** → [planejar reforço](guias/planejar-reforco.md#como-funciona-no-código).
7. **Atualizar dados urbanos e publicar a previsão.** → [dados reais e ML](guias/dados-e-ml.md#atualização-automática-urbanupdate).
8. **Entrar, redefinir senha, sessão seguinte.** → [acesso e contas](guias/acesso-e-contas.md#como-funciona-no-código).

## 5. Dados

- **Esquema:** 11 migrações em [`database/migrations/`](../database/migrations/README.md); cada tabela e regra em
  [dicionário de dados](referencia/dicionario-de-dados.md).
- **Demonstração:** `demo:seed` gera um cenário reproduzível pela semente (`SIM_SEED`), com 240 dias de histórico, perfil
  compacto (17 equipamentos, 7 agentes) ou completo (48, 21); `--reset` apaga o domínio mas **preserva contas e dados
  do Infosiga** → [demonstração e simulação](guias/demonstracao-e-simulacao.md).
- **Dados reais:** pacote de treino em `ml/marilia-setores/` (conferido byte a byte pelo manifesto),
  experimento da grade em `ml/marilia-grade-2km/`, importação por `urban:import` e atualização por `urban:update` →
  [dados reais e ML](guias/dados-e-ml.md) e [`ml/README.md`](../ml/README.md).
- **Identificadores com zeros à esquerda** são texto em todo lugar: CEP `'17500-000'`, RENAVAM `'00123456789'`,
  códigos `RAD-0007`, `AGT-006`, `DEMO-MUL-000023`.

## 6. Instalar, configurar e operar

| Tarefa | Onde |
|---|---|
| Instalar do zero com Docker | [README](../README.md#instalação-local-do-zero) |
| Variáveis de configuração (`.env.example`, nenhuma senha real na documentação) | [operação](operacao.md#configuração-env-ou-variáveis-de-ambiente) |
| Credenciais por processo (web sem credenciais privilegiadas) | [operação](operacao.md#credenciais-por-processo) |
| Comandos `php bin/fiscaliza` | [console](referencia/codigo/cli/console.md) |
| Atualizar um banco existente (migrações) | [operação](operacao.md) · [migrações](../database/migrations/README.md) |
| Backup e restauração | [operação](operacao.md) · [evidência](evidencias/2026-10-18-backup-restauracao.md) |
| Simulador e atualizador (serviços do Compose ou cron) | [demonstração](guias/demonstracao-e-simulacao.md#simulador) · [dados](guias/dados-e-ml.md) |
| Contêineres, Apache, PHP, CI | [implantação e CI](referencia/implantacao-e-ci.md) · [`docker/`](../docker/README.md) |
| Publicação (prefixo ou origem dedicada) | [operação](operacao.md#publicação) · [landing](../landing/README.md) |

**Comandos destrutivos:** `demo:seed --reset` (apaga o domínio; exige `DEMO_ALLOW_RESET=true` e o marcador sintético),
`scripts/avaliar-sementes.php` (substitui a demonstração), a suíte PHPUnit (apaga e recria o banco de testes) e
`docker compose down -v` (apaga o volume do banco). Nenhum deles foi executado para produzir esta documentação.

## 7. Código: onde está cada coisa

| Quero entender… | Vá para |
|---|---|
| uma camada ou pasta | o `README.md` da pasta ([`src/`](../src/README.md), [`resources/`](../resources/README.md), [`templates/`](../templates/README.md), [`tests/`](../tests/README.md), [`scripts/`](../scripts/README.md), [`ml/`](../ml/README.md), [`landing/`](../landing/README.md), [`docker/`](../docker/README.md), [`bin/`](../bin/README.md)) |
| um método ou função | [referência de código](referencia/codigo/README.md); a lista de todos os símbolos está no [catálogo](referencia/catalogo-simbolos.md) |
| uma rota | [HTTP](http.md) e [controladores](referencia/codigo/http/controladores.md) |
| uma regra de negócio | [domínio](dominio.md) e as páginas de `referencia/codigo/domain/` |
| o comportamento de uma tela no navegador | [frontend](referencia/codigo/frontend/entrada-e-bibliotecas.md) |

## 8. O que foi verificado

- **Testes automatizados:** três suítes PHPUnit (unidade; integração no Firebird real; HTTP), com concorrência real
  em processos separados e corridas com ordem controlada — [`tests/README.md`](../tests/README.md) e
  [casos de teste](referencia/codigo/testes/casos.md). **Última execução registrada:** 283 testes, 1914 asserções, OK,
  em 24/10/2026 ([verificação](verificacao.md)); os casos acrescentados pela auditoria de 25/10 entram na próxima
  execução registrada.
- **Navegador:** evidências datadas com capturas, vídeos e relatórios — [evidências](evidencias/README.md).
- **CI:** GitHub Actions e GitLab CI rodam estilo, análise estática, testes e conferem que os builds versionados batem
  com as fontes — [implantação e CI](referencia/implantacao-e-ci.md#integração-contínua).
- **Esta documentação** foi conferida com `node scripts/documentacao/inventario.mjs --verificar` (símbolos, pastas,
  links e âncoras); ver a [matriz de cobertura](cobertura-documentacao.md), que também lista divergências e pendências.

## 9. Decisões e histórico

As 23 ADRs ([índice](adr/README.md)) registram por que o projeto é assim: contêineres e Firebird (0001), PDO sem ORM
(0002), consulta periódica (0004), estado × histórico (0005), heurística preventiva (0007), uma intervenção por vez
(0009), versão do cadastro (0010), cota de login (0011), identidade e movimento (0012), acesso só com conta (0014),
planejamento por setores (0016), multas (0017), base do agente (0021), acompanhamento de alertas (0022), revisão de
fluxos (0023) e as da landing (0015, 0018–0020).

## 10. Escopo

O Promotheus Fiscaliza é uma demonstração completa sobre uma operação fictícia: não se integra a órgãos, equipamentos ou
serviços de cobrança, e multas não têm valor oficial. As análises declaram seus próprios limites onde importam — a
heurística de revisões usa histórico sintético ([análise preventiva](analise-preventiva.md)) e o planejamento usa
dados reais com atraso de publicação assumido ([planejamento por setores](planejamento-por-setores.md)). Próximos passos
da entrega: [README](../README.md#estado-da-entrega); da documentação: [cobertura](cobertura-documentacao.md#divergências-e-pendências-encontradas).
