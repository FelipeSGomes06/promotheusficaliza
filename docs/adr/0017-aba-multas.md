# ADR-0017 — Aba Multas: registro demonstrativo de infrações, conferência documental e histórico próprio

Data: 21/10/2026 · Situação: aceita · Amplia: o "fora do escopo" do README (multas) · Relacionadas:
[ADR-0005](0005-estado-atual-versus-historico.md), [ADR-0012](0012-identidade-motion-e-build-de-assets.md),
[ADR-0014](0014-acesso-somente-com-conta-cadastrada.md)

## Contexto

O autor pediu uma aba **Multas** para cadastrar e acompanhar infrações com origem por radar ou agente de trânsito,
dados da ocorrência, veículo/placa/RENAVAM e conferência de documentação. O pedido veio com um pacote de planejamento do
Codex (`promotheus-multas-2026-10-20`, análise de `main@132dc7f`); a implementação partiu de `main@3ef2904`, 25 commits
depois, preservando esse trabalho (planejamento por setores, retrospecto, migrações 0003–0006). Continua sendo uma
demonstração manual: nada é consultado no Detran/Senatran, emitido, cobrado ou protocolado.

## Decisões

- **Tabelas próprias** (`0007_multas.sql`): `FINE` (projeção atual), `FINE_DOCUMENT_CHECK` (constatações) e `FINE_EVENT`
  (histórico). As duas últimas são só leitura e inserção para a aplicação (`Migrator::APPEND_ONLY`); a sincronização de
  privilégios revoga `UPDATE`/`DELETE` que uma versão anterior tenha concedido. `RESOURCE_EVENT`, alertas e a central
  não recebem multas.
- **Três estados de cadastro**: rascunho (aceita ausências, valida o que vier) → registrado após conferência dos campos
  mínimos → cancelado internamente (somente leitura, com motivo; não é anulação do auto). Código interno
  `DEMO-MUL-000023` derivado do ID, sempre separado do AIT externo; AIT único por órgão autuador (índice parcial).
- **Origem**: radar do inventário (`TYPE_CODE = RADAR`) ou agente com função de trânsito, conferidos no servidor com
  bloqueio (multa → equipamento → agente). Radar pode ter agente operador associado (mesma coluna `AGENT_ID`). Ao
  registrar, o servidor grava um snapshot do recurso; arquivar ou mudar o cadastro depois não o reescreve. Órgão
  autuador diferente do órgão do recurso exige justificativa.
- **Velocidade considerada calculada** (pedido do autor em 21/10/2026): medida − 7 km/h até 107 km/h e −7% acima,
  arredondado (Resolução Contran 798/2020, art. 8º e Anexo III); o valor enviado no formulário é ignorado. Verificação
  do radar com mais de 12 meses na data da ocorrência vira motivo de "A conferir" (possível argumento de defesa;
  quem decide é o órgão).
- **Radar sempre com placa**: o radar identifica o veículo pela imagem, então origem Radar exige a placa lida desde o
  rascunho (pedido do autor em 21/10/2026, depois do primeiro merge); os demais dados do veículo podem vir depois.
- **Veículo autuado** como colunas da multa, separado de `EQUIPMENT` (viaturas). Placa normalizada só no formato;
  RENAVAM `VARCHAR(11)` com zeros à esquerda; ausência aceita com justificativa. Sem CPF, CNH ou proprietário.
- **Documentação** temporal e imutável: cada conferência tem escopo (ocorrência ou posterior), data de referência,
  instante, fonte e referência. "Não verificada" é estado legítimo. A situação na ocorrência é a última conferência
  compatível pelo **instante da conferência** (lançamento atrasado não substitui uma mais recente) e pela versão do
  sujeito: mudar placa, UF, RENAVAM, marca, modelo, espécie ou data/hora avança `SUBJECT_VERSION` e a ficha volta a
  "Não verificada"; as antigas ficam visíveis como "anterior à retificação".
- **Andamento** com três dimensões independentes (etapa, pagamento, contestação); valores em centavos com parser
  decimal brasileiro estrito; datas de fatos não futuras nem anteriores à ocorrência; correção de pagamento/resultado
  exige motivo; sequências incomuns geram só aviso. Nada é calculado (tolerância, prazos, descontos, pontos).
- **Concorrência e repetição**: `VERSION_NO` avança em toda mudança de domínio; cada formulário leva `version` e
  `command_uid` (emitido pelo servidor, diferente do CSRF). O evento guarda a chave e a impressão digital do comando:
  reenvio idêntico devolve o resultado gravado; a mesma chave com outros dados ou outra conta é conflito. Uma transação
  por operação; falha no histórico desfaz tudo.
- **"A conferir"** sai de uma regra pura (`FineRules::attention`) usada pela ficha e, sem a data de hoje, gravada em
  `ATTENTION_COUNT` para que lista, total e os três resumos usem o mesmo `WHERE` numa leitura SNAPSHOT.
- **Interface** no padrão das telas existentes (lista `.rows`, `kpi-strip`, formulário por etapas numeradas, ficha com
  próxima ação), ícone `i-fines` no sprite local, tudo funcional sem JavaScript. `pages/multas.js` só esconde e
  desabilita o bloco de origem que não se aplica (preservando o que foi digitado) com entrada de 0,2 s que respeita
  `pf-motion`; sem novo temporizador, sem 3D, sem biblioteca nova. Desde 22/10/2026 também tira da vista os campos
  que só valem numa situação (`data-show-if`), sem desabilitá-los: continuam sendo enviados e reaparecem com valor ou
  erro.
- **Demonstração**: 12 casos no perfil compacto e 40 no completo, gravados pelos mesmos casos de uso com origem
  `SEMENTE`; `demo:seed-multas` acrescenta o cenário a um banco de demonstração existente sem apagar nada (marcador
  `fines_seed`; outra versão ou carga parcial é conflito).

## Adaptações ao pacote de planejamento

- Migração numerada **0007** (0003–0006 já estavam em uso).
- Um repositório (`FineRepository`) para projeção, constatações e eventos, em vez de três: é o mesmo agregado.
  `FineDocumentStatus` e `FineRules` ficaram como regras puras; `FineQuery` e `FineFilters` como no plano.
- A conferência documental é lançada **na ficha**, depois de salvar, e não junto do cadastro: um único caminho, com
  evento próprio, que já conhece a data da ocorrência e a versão do sujeito. O formulário explica isso na dica da
  etapa "Veículo autuado" (a antiga etapa 4, só de texto, saiu em 22/10/2026: `docs/evidencias/2026-10-22-multas-campos.md`).
- Campos de valor em reais (`nominal_amount`, `paid_amount`) na interface, gravados em `*_CENTS`; motivos com nomes
  próprios por formulário da ficha (`check_reason`, `progress_reason`, `reason`) para não repetir `id` na página.
- Caso 11 do cenário: em vez de arquivar um recurso da demonstração (o que mudaria a central), usa um radar que hoje
  está em mau funcionamento; o arquivamento depois do registro é coberto pelos testes de integração.

## Consequências

- Uma nova tela operacional cobre a pergunta "o que está registrado e o que falta conferir", sem se apresentar como
  sistema de emissão. Antes de uso real seria preciso revisar as normas vigentes e autorizar qualquer integração
  (ver limites no pacote de planejamento, documento 06).
- Rascunhos podem ficar incompletos; o banco garante, por CHECK, os campos mínimos de um registro e a coerência da
  origem e das velocidades.

## Adendo (24/10/2026) — revisão da auditoria de interface do Codex

- **Pendência regularizada depois:** a pendência documental no dia da multa deixa de contar em "A conferir" quando a
  última conferência "depois da multa" da mesma identidade informa "Em dia". A situação no dia da multa continua "Com
  pendência" (selo, ficha, filtro). A leitura traz `LATER_DOC_STATUS` por subconsulta (sem coluna nova); a migração
  0008 só recontou o `ATTENTION_COUNT` dos registros já gravados.
- **Medidor fora de velocidade:** registro no Inmetro, certificado e verificação metrológica aparecem como "não se
  aplica a esta infração" fora de excesso de velocidade, como a regra de "A conferir" já tratava.
- **Radar sem a função da infração:** registrar ou retificar exige a observação sobre a origem quando as funções do
  cadastro do radar não incluem o tipo da infração — o mesmo tratamento do órgão divergente: o cadastro atual pode ter
  mudado desde o fato, então a explicação basta e nada é bloqueado.
