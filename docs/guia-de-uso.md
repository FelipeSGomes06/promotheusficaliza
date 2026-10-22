# Guia de uso

Para quem vai usar a demonstração pela primeira vez. Tudo é fictício (ver "Demonstração" no topo de qualquer tela).
**Só entra quem tem conta cadastrada pelo administrador**: sem conta, qualquer endereço leva à tela de login e não
há cadastro aberto. Peça sua conta a quem administra o sistema.

Situações usadas em todas as telas, sempre com texto e símbolo além da cor:

| Selo | Significa |
|---|---|
| ✓ Operacional | Funcionando. |
| ! Mau funcionamento | Em falha (por telemetria ou registro de pessoa). |
| ⚙ Em manutenção | Intervenção em execução (ou definida por pessoa). |
| ✕ Furtado | Registrado como furtado. |
| – Desativado · ◌ Não instalado | Fora de serviço: não contam na disponibilidade. |
| Agentes: ● Em atividade · ❚❚ Em pausa · ! Indisponível · – Fora de serviço | "Em turno" = em atividade ou em pausa. |

"Sem informação recente" quer dizer que o aparelho ou o rádio não comunicou no prazo (sinal: 60 min; posição: 30 min).
**Não** quer dizer que está quebrado.

## Visão geral (`/`)

**Objetivo.** Ver no mapa de Marília os recursos da demonstração e descobrir o que precisa de atenção.

- **Menu (trilho grafite à esquerda; no celular, botão ☰):** telas, conta e o botão **Animações**, que mostra se as
  animações estão ativas ou reduzidas e **por quê** ("por sua escolha neste navegador" ou "pela preferência do
  sistema"). Tocar alterna e a escolha fica salva neste navegador.
- **Barra superior:** título, "Atualizado às…" (ponto verde; âmbar quando atrasado ou sem conexão), **Atualizar agora**
  (mostra um indicador enquanto busca) e **Pausar/Retomar** a atualização automática; aviso **Demonstração**.
- **Resumos** (acima da lista; no celular, abaixo do título): equipamentos em serviço funcionando, recursos que precisam
  de atenção (toque para filtrar) e manutenções urgentes (leva à Manutenção). O botão ⓘ abre as fórmulas e a tabela
  por bairro.
- **Filtros (chips):** busca (endereço, tipo, código ou nome, sem precisar de acentos), **Tipo** e **Situação** (marque
  um ou vários), **Precisa de atenção** e **Mais filtros** (bairro e órgão). Cada filtro ativo vira um chip com ×
  logo abaixo; **Limpar tudo** volta ao início. Mapa, lista e números mudam juntos, e a lista se reorganiza com uma
  animação curta. No celular, os chips rolam de lado.
- **Lista:** primeiro o que precisa de atenção (com o motivo em vermelho), depois os demais, radares primeiro. A lista
  começa curta; **Ver os outros N recursos** mostra o conjunto inteiro. O título é "tipo · endereço"; código, série e
  coordenadas ficam na ficha.
- **Mapa ou Lista:** mostram exatamente os mesmos resultados. A escolha fica no endereço da página (`?visao=lista`).
- **Escolher um recurso** (toque no ponto ou na linha): o ponto ganha destaque, o mapa centraliza e a **ficha** abre ao
  lado (no celular, sobe de baixo): o que é, situação, atualidade, onde está (ponto ilustrativo), motivos de atenção,
  alertas, manutenção, código e características, com **Ver ficha completa**. Trocar de recurso com a ficha aberta só
  troca o conteúdo. `Esc` ou × fecha e devolve o foco a quem abriu.
- **Com conta:** na ficha, **Registrar ciência** de um alerta e **Simular falha** (recurso da demonstração). O botão
  mostra "Registrando…"/"Simulando…" e só depois da resposta do servidor aparece o aviso verde; se não der certo, o
  aviso fica em vermelho até você fechar.
- **Números redondos no mapa** juntam recursos próximos: toque para aproximar; se estiverem no mesmo ponto, aparece
  uma lista para escolher. O "!" indica que algum deles precisa de atenção.
- **Formas no mapa** (ver **Legenda**): quadrado = equipamento no local de instalação; borda tracejada = local
  planejado (não instalado); círculo = agente ou veículo na última posição recebida; borda pontilhada com ⌛ = posição
  antiga; "B" = base de trabalho, quando não há posição recente.
- Recursos sem coordenadas aparecem só na lista (o mapa avisa quantos são). Se o mapa de fundo não carregar, os pontos,
  a lista e os resumos continuam atualizados (aviso no próprio mapa).
- **Atualização:** a cada poucos segundos, sem mexer no mapa, no filtro, na escolha nem no foco. Só o que mudou pisca
  (a linha fica turquesa por um instante e o número do resumo troca). Alertas novos viram **um** aviso por vez.
  Sem conexão, um aviso vermelho diz quais dados estão na tela e oferece **Tentar agora**; quando volta, aparece
  "Conexão restabelecida".

**Exemplo.** Para achar um radar: os radares estão no topo de "Demais recursos" (ou Tipo → "Radar de fiscalização").
Toque em "Radar · Avenida Sampaio Vidal…": o ponto se destaca e a ficha mostra a situação ("Operacional") e
"Sinal atual · há 2 min".

## Equipamentos (`/equipamentos`)

**Lista.** Título "tipo · endereço", plaqueta com o código (ex.: `RAD-0007`), bairro, marca e modelo, situação e,
quando for o caso, "Sinal atrasado", "Sem sinal recente (não indica falha)" ou "Sem localização cadastrada". Filtros:
busca, tipo e situação; em **Mais filtros**, bairro, órgão, ordenação e **Mostrar só arquivados**.

**Ficha.** De cima para baixo: o que é e onde está (título, código, órgão), situação e atualidade; **Próxima ação**
(uma só: concluir a intervenção em andamento, iniciar uma ordem urgente, abrir corretiva, registrar instalação ou
"nada pendente"); manutenção; histórico; à direita, onde está (com **Ver no mapa**), cadastro, revisão recomendada,
alertas e **Mais ações** (mudar situação, simular falha, arquivar).

**Histórico.** Cada item diz quando aconteceu e quem registrou (pessoa, simulador, sistema, carga da demonstração).
Linha tracejada = registro que **não alterou** a situação atual; o motivo aparece no item: evento atrasado, situação
definida por pessoa (ex.: em manutenção), equipamento arquivado ou nenhuma mudança.

### Cadastrar e editar

Três etapas: **1. O que é** (tipo, situação inicial, marca, modelo, órgão, série, patrimônio), **2. Onde fica** (toque
no mapa para marcar o ponto; "este ponto é" local de instalação, planejado ou base; endereço; bairro; coordenadas em
"Opções avançadas") e **3. Características** do tipo escolhido, periodicidade da preventiva, data de instalação e
observações. Ao trocar o tipo, só aparecem as características dele. Cidade é sempre Marília/SP.

| Erro comum | Mensagem | Como resolver |
|---|---|---|
| Faltou endereço | "Informe logradouro, referência ou coordenadas." | Preencha um deles ou marque no mapa. |
| Radar que fiscaliza velocidade sem limite | "Obrigatório para este tipo." no limite de velocidade | Informe o limite (20 a 120 km/h); só é exigido quando "Velocidade" está marcada. |
| Série repetida na mesma marca | "Já existe equipamento deste fabricante com esta série." | Corrija a série ou deixe em branco se não souber. |
| Veículo como "local de instalação" | "Recurso móvel usa o endereço como base…" | Escolha "Base". |
| Não instalado marcado como instalado | "Equipamento ainda não instalado: use 'planejado'." | Escolha "Local planejado". |
| Coordenada fora da cidade | "Fora da área de Marília/SP atendida pela demonstração." | Marque o ponto no mapa. |

Com erro, a página volta com **tudo o que você digitou**, um resumo no topo (cada item leva ao campo) e a mensagem ao
lado do campo.

**Envio de formulários (todas as telas).** Ao enviar, o botão troca o rótulo por "Salvando…", "Iniciando…",
"Concluindo…" etc. e ignora cliques repetidos. O resultado aparece depois que o servidor responde, como aviso no canto
da tela (verde some sozinho; vermelho fica até você fechar). Sem JavaScript, a mesma mensagem aparece no topo da
página.

**Conflito de edição.** Se alguém (ou você, em outra aba) salvou o cadastro depois que você abriu o formulário,
aparece "O cadastro mudou enquanto você editava", com uma tabela **Salvo agora × Sua versão** e a última mudança
registrada. Seus dados continuam no formulário. **Salvar alterações** grava a sua versão (agora sobre a versão atual);
**descarte suas alterações** recarrega o que está salvo. Posição recebida, sinal ou mudança de situação **não** causam
conflito; concluir uma instalação (planejado → instalado) causa, porque muda o local cadastrado.

### Mudar situação

**Mais ações → Mudar situação**: só aparecem as mudanças possíveis a partir da situação atual, e o motivo é
obrigatório (fica no histórico). Instalar = "Não instalado" → "Operacional" (o local planejado vira local de
instalação e a data é registrada). Com intervenção em execução, a situação só muda ao concluí-la.

### Arquivamento

Retira o equipamento da operação: ele some da visão geral, não recebe ordens e não gera alertas; ficha, ordens,
alertas e histórico continuam consultáveis em **Equipamentos → Mais filtros → Mostrar só arquivados**. Não há
desarquivamento pela interface.

- Com intervenção em execução: **não é possível arquivar**; conclua a ordem informando o resultado real.
- Com ordens só agendadas: é preciso marcar "Cancelar também as ordens agendadas (#…)"; cada uma é cancelada com o
  motivo do arquivamento e o seu nome.

Bancos anteriores a 18/10/2026 podem ter ordens abertas em equipamentos já arquivados (a regra não existia). Elas não
aparecem no painel; quem administra pode localizá-las com
`SELECT m.ID FROM MAINTENANCE_ORDER m JOIN EQUIPMENT e ON e.ID = m.EQUIPMENT_ID WHERE e.ARCHIVED_AT IS NOT NULL AND m.STATE IN ('ABERTA', 'EM_EXECUCAO')`
e decidir caso a caso.

## Agentes (`/agentes`)

Agentes são equipes fictícias mostradas no mapa — **não** são contas de acesso.

- **Base de trabalho:** endereço fixo cadastrado (obrigatório, com coordenadas). Não indica onde o agente está agora.
- **Última posição recebida:** vem do rádio simulado, com data e hora; só é mostrada no mapa enquanto o agente está
  em turno. Sem posição recente, o mapa mostra a base com "B".
- Situação muda na ficha (**Mudar situação**). Cadastro em duas etapas: quem é e base de trabalho.

**Exemplo.** "Agente de trânsito · Hermes 06" em atividade, mas "Sem informação recente": o rádio não transmitiu há
mais de 30 min. Aparece em "precisam de atenção" como "Em atividade sem posição recente" — não é falha do agente.

## Multas (`/multas`)

Registro **demonstrativo** de infrações informadas à mão: nada é consultado no Detran/Senatran, emitido, cobrado ou
protocolado ([ADR-0017](adr/0017-aba-multas.md)). Use só dados fictícios.

- **Lista:** três resumos do mesmo filtro da lista — registros, **a conferir** (rascunhos, documentação não verificada
  ou com pendência, revisão marcada que passou, dados do medidor ou evidência a conferir) e **pagamento informado**
  (lançado à mão; não é arrecadação). Busca por código interno (`DEMO-MUL-000023`), AIT, placa ou RENAVAM; origem e
  etapa à mão; em **Mais filtros**, período da ocorrência, órgão autuador, radar, agente (da constatação ou operador do
  radar), documentação, pagamento, rascunhos/cancelados e ordem. A URL guarda os filtros, e a ficha volta para eles.
- **Cadastrar multa:** um formulário em cinco etapas (ocorrência, como foi constatada, veículo, documentação, revisão).
  Salvar cria um **rascunho**: só a descrição é obrigatória, mas o que for preenchido precisa estar bem formado. Escolha
  **Radar** (do inventário; pode indicar o agente operador do medidor) ou **Agente de trânsito**; com JavaScript, o bloco
  da outra origem some e não é enviado, e o que foi digitado nele volta se você trocar de ideia. Sem JavaScript, os dois
  blocos aparecem: preencha só o da origem escolhida. Velocidades (regulamentada, medida, considerada) só em excesso de
  velocidade: o limite e a medida vêm do documento, e a **considerada é calculada** (medida − 7 km/h até 107 km/h;
  −7% acima — Resolução Contran 798/2020), com prévia enquanto você digita. Com origem **Radar**, a placa lida na imagem é obrigatória desde o rascunho:
  sem placa legível o radar não gera registro; marca, modelo, espécie e RENAVAM podem vir depois.
- **Ficha:** a próxima ação diz o que falta. **Registrar após conferência** aparece quando os campos com * estão
  completos; o servidor confere de novo e grava o retrato do radar ou do agente naquele momento. O código interno nunca
  substitui o número do AIT.
- **Documentação:** "Não verificada" é o ponto de partida e não significa regular. **Registrar conferência documental**
  acrescenta uma constatação com resultado, fonte (CRLV apresentado, consulta feita fora do sistema, relato) e data de
  referência — na data da ocorrência ou posterior. Uma regularidade posterior aparece separada e não corrige a
  ocorrência. Corrigir placa, UF, RENAVAM, marca, modelo, espécie ou data/hora pede nova conferência; as anteriores
  ficam em "Todas as conferências", marcadas como anteriores à retificação.
- **Andamento:** etapa administrativa, pagamento e contestação são independentes (pagamento informado e recurso em
  análise podem coexistir). Valores em reais (`1.234,56`). O sistema não calcula prazos, descontos nem decide nada.
- **O que aconteceu:** uma frase com o fato (quem constatou, veículo, local, data), as três velocidades quando é excesso
  de velocidade e o relato escrito; os dados técnicos ficam em "Detalhes do registro".
- **Perguntas frequentes:** no fim da lista e da ficha, os termos explicados (AIT, RENAVAM, velocidade considerada,
  Inmetro, CRLV, "Não verificada", autuação, penalidade, defesa prévia, recurso, enquadramento).
- **Mais ações:** retificar (registrado: exige motivo; o original fica no histórico) e **cancelar registro** (interno,
  com motivo; o registro fica somente leitura e não é anulação do auto).
- **Erros:** campo inválido volta com tudo o que foi digitado e um resumo com links; se outra pessoa salvou antes, a tela
  mostra "Salvo agora × Sua versão" e salvar de novo é uma decisão consciente. Enviar o mesmo formulário duas vezes não
  duplica nada.

## Manutenção (`/manutencao`)

Uma lista, da mais urgente para a mais distante: **Em andamento**, **Urgentes** (imediatas ou vencidas), **Próximos 7
dias** e **Programadas depois**, mais **Encerradas recentemente**. Cada ordem mostra "tipo · endereço", código,
número da ordem, motivo e prazo.

| Ação | Onde | Efeito |
|---|---|---|
| Abrir ordem | Ficha → Manutenção → "Abrir ordem de manutenção" | Corretiva ou preventiva, imediata ou programada (com data). Não muda a situação. |
| Iniciar intervenção | Manutenção ou ficha | O equipamento passa para "Em manutenção". **Só uma por equipamento:** com outra em execução, aparece "Aguardando: a ordem #N está em execução". |
| Concluir | "Concluir esta intervenção" | Informe o que foi feito e **como o equipamento ficou**. Essa situação passa a valer. |
| Concluir em falha | Resultado "Continua com mau funcionamento" | A ordem fecha como concluída e o equipamento fica em mau funcionamento; abra uma nova corretiva se precisar. |
| Cancelar | Ficha → "Cancelar esta ordem" | Só ordens não iniciadas; o motivo é obrigatório e fica registrado. |

Concluir uma preventiva de equipamento com periodicidade agenda a próxima automaticamente.

## Alertas

Nascem sozinhos quando há uma condição (furto, mau funcionamento, sem sinal ou posição recente, manutenção imediata ou
vencida) e se resolvem sozinhos quando ela acaba. **Registrar ciência** (na ficha resumida ou completa) só informa que
alguém viu: não resolve a causa.

## Revisões recomendadas (`/antecipacao`)

Parte 3 do desafio. Começa por **Revisar primeiro** e **Acompanhar**: cada item diz **por quê** (ex.: "1 falha nos
últimos 30 dias", "Última preventiva há 85 dias") e **o que fazer**. Os já indisponíveis, os de histórico curto e os
fora de serviço ficam em seções recolhidas. A "prioridade sugerida" (0–100) **não é a chance de falhar**; o histórico é
sintético e o retrospecto, no fim da página, mostra que o método acerta pouco (cerca de 2× o acaso). Operadores podem
**Recalcular agora**. Detalhes em [análise preventiva](analise-preventiva.md).

## Planejar reforço (`/planejar`)

Planejamento preventivo semanal ([método e resultados](planejamento-por-setores.md)), em três passos:

- **Resumo da semana (faixa escura no topo):** período, "Reforce primeiro: <bairros>", quantos sinistros são esperados
  ali na semana, até quando vão os dados e se a previsão é automática. **Como ler estes números** abre as definições e
  a origem: contagens reais agregadas do Infosiga-SP; agentes e missões fictícios.
- **1. Onde reforçar:** responda "Quantos lugares a equipe consegue cobrir nesta semana?" (1 a 4). O mapa mostra a
  grade de quadrados iguais de 2 km e só os prioritários numerados; a lista ao lado traz os bairros de referência, o
  setor, a parcela dos registros do último ano, os sinistros esperados na semana e se o quadrado **já tem agente
  designado**. Toque num quadrado (no mapa ou na lista) para escolhê-lo. **Por que este quadrado?** mostra a conta e
  as frases sobre o setor dele.
- **2. Quem enviar:** agentes de trânsito em atividade e sem missão, o sugerido primeiro (posição atual antes de
  posição atrasada, e esta antes de só a base; depois, o mais perto em linha reta **do quadrado**). Guarda e polícia
  não entram; **Por que outros agentes não aparecem?** diz o motivo de cada um.
- **3. Confirmar a missão:** com o **Sugerido** marcado, não há o que justificar; se escolher outro agente, aparece
  **Por que escolheu este agente e não o sugerido?**, obrigatório. Vale o sugerido que a tela mostrou, mesmo que a
  ordem mude enquanto você preenche. Ponto de apoio e observação são opcionais; marque que é uma missão fictícia e
  **Confirmar missão**. Se faltar algo, a tela volta no passo 3 com o aviso junto do campo e o que você digitou. A
  missão guarda o quadrado e o setor.
- **Missões:** "agente → quadrado", situação, quem confirmou; **Concluir** ("como foi?") ou **Cancelar** ("por quê?")
  liberam o agente. **Detalhes e histórico** mostra o motivo registrado e as transições. Se duas pessoas confirmarem o
  mesmo agente ao mesmo tempo, só uma vale.
- **Semana encerrada:** a tela avisa e não confirma missões até a atualização seguinte.

### Retrospecto (`/planejar/retrospecto`)

Faixa tracejada grafite/turquesa = **histórico**, não a previsão atual. Escolha a semana do teste de 2025 (ou use
semana anterior/próxima). A tabela mostra a previsão do **modelo congelado em 01/01/2025** e as contagens de
entrada daquela semana; a coluna "Registrado" fica **oculta** até **Revelar o que foi registrado** (os números nem
são enviados antes). Depois, a página diz quantos registros houve e quantos caíram no setor priorizado por cada
método. Abaixo, sempre, o resultado do teste inteiro e os limites — uma semana isolada não prova nada.

## Entrar e contas

- **Entrar:** use a conta cadastrada pelo administrador. Depois do login você volta para a página que tinha pedido;
  **Sair** leva de volta ao login. Mensagem única para usuário ou
  senha errados. Depois de 5 tentativas erradas em 15 minutos, o acesso daquela conta fica bloqueado por esse tempo
  (também se as tentativas forem simultâneas). Sessão expira após 30 min sem uso ou 8 h.
- **Contas** (só administrador): é aqui que se dá acesso ao sistema. Criar conta (usuário, nome, papel) — a senha é
  gerada e mostrada **uma única vez**; desativar/reativar (a conta desativada perde o acesso na hora). Não é possível desativar a própria conta nem o último administrador ativo.
- **Operador** cadastra, edita, registra manutenção e alertas. **Administrador** faz isso e gerencia contas.
- Primeira conta (no servidor): `php bin/fiscaliza user:create nome --role=ADMIN --name="Nome"` (ver [operação](operacao.md)).

## Movimento e acessibilidade

Animações curtas só em resposta a ações. "Reduzir animações", no rodapé, desliga todas (o sistema também respeita a
preferência do aparelho). Tudo funciona pelo teclado: `Tab` percorre, `Enter`/`Espaço` ativa marcadores e botões,
`Esc` fecha ficha resumida, menu e avisos. Nenhuma informação depende de cor, passar o mouse ou animação.
