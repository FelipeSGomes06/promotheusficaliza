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

- **Resumos:** quantos equipamentos em serviço estão funcionando, quantos recursos precisam de atenção e quantas
  manutenções são urgentes. "Mais indicadores e como são calculados" abre as fórmulas e a tabela por bairro.
- **Ver o que precisa de atenção:** filtra mapa, lista e números para os recursos com problema, manutenção urgente ou
  alerta aberto. Cada um mostra o motivo em texto.
- **Filtros:** buscar (endereço, tipo, código ou nome, sem precisar de acentos), tipo, situação; em **Mais filtros**,
  bairro, órgão e "só o que precisa de atenção". O número de filtros extras aparece no botão; **Limpar filtros**
  volta tudo.
- **Mapa ou Lista:** mostram exatamente os mesmos resultados. A escolha fica no endereço da página (`?visao=lista`).
- **Escolher um recurso** (toque no ponto ou na linha): abre a ficha resumida — o que é, onde está, situação,
  atualidade, motivos de atenção, alertas e manutenção — com **Ver ficha completa**. A seleção continua ao trocar entre
  mapa e lista e ao atualizar. `Esc` ou **Fechar** fecha e devolve o foco a quem abriu.
- **Números redondos no mapa** juntam recursos próximos: toque para aproximar; se estiverem no mesmo ponto, aparece
  uma lista para escolher. O "!" indica que algum deles precisa de atenção.
- **Formas no mapa** (ver **Legenda**): quadrado = equipamento no local de instalação; borda tracejada = local
  planejado (não instalado); círculo = agente ou veículo na última posição recebida; borda pontilhada com ⌛ = posição
  antiga; "B" = base de trabalho, quando não há posição recente.
- Recursos sem coordenadas aparecem só na lista (o mapa avisa quantos são).
- **Atualização:** a cada poucos segundos, sem mexer no mapa nem no que você está fazendo. "Pausar atualização" fica
  abaixo do mapa; sem conexão, a tela avisa e tenta de novo sozinha.

**Exemplo.** Para achar um radar: Tipo → "Radar de fiscalização" (ou busque "radar"), toque num ponto e leia
"Radar · Avenida…", a situação ("Operacional") e "Sinal atual · há 2 min".

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
