# Evidência — rótulos harmônicos e explicações no "?" (24/10/2026)

Pedido do autor, a partir da auditoria do Codex (fora do repositório, `.codex/entregas-para-claude/2026-10-24-auditoria-labels`):
os controles de uma mesma linha começavam em alturas diferentes (até 70 px entre Placa e UF) e muita explicação
continuava fixa entre o rótulo e o campo, mesmo depois da ajuda contextual.

Regra seguida: nenhuma regra de negócio, validação, cálculo ou banco mudou. Toda orientação que saiu da tela foi
antes incorporada ao "?" do campo (`templates/_ajuda.twig`); mensagens de erro, estados atuais e consequências
específicas continuam à vista.

## A causa e a correção compartilhada

- **Macro** (`templates/_ui.twig`): `ui.input` e `ui.select` desenhavam `opts.help` entre o rótulo e o controle, e só
  criavam `.field__head` quando havia "?". Agora todo campo tem a mesma estrutura: `.field__head` (rótulo, marcador
  e, se houver, o "?") e `.field__body` (controle e erro). `opts.help` deixou de existir; a explicação vai em
  `opts.help_tip`. `ui.select` mostra "(opcional)" quando dá para deixar em branco (`opts.empty`), como `ui.input`.
- **CSS** (`resources/css/app.css`): `.field__head` tem 24 px de altura mínima (a do "?"), com ou sem ajuda. Em
  `.form-grid`, cada item ocupa duas faixas e o campo herda as faixas (`grid-template-rows: subgrid`): o controle
  começa na mesma altura do vizinho mesmo com rótulo em duas linhas, erro embaixo de um deles ou só um com "?".
  Invólucros com um único campo (`data-show-if`, `data-check-effective`) também herdam as faixas. A regra própria
  de `.follow-form` ficou desnecessária e saiu.
- **`.form-grid--pair`** saiu: repetia a grade de duas colunas e, por vir depois, anulava o empilhamento abaixo de
  680 px (Código/Desdobramento ficavam a 22 px de diferença em 390 e 360 px). Cor/Ano foi para a linha inteira.
- **Filtros**: `.filters` já alinhava os controles embaixo (`align-items: end`) e foi mantido; "Mais filtros" ganhou
  o mesmo alinhamento e todos os rótulos de filtro ganharam o cabeçalho comum.
- Duas folgas que já faltavam: o mapa da multa colado nos campos de endereço e "Descrição curta" colada nas
  coordenadas abertas (`.form-grid + .picker`, `.advanced[open] + .form-grid`).

## Por tela

| Tela | O que alinhou | Textos que foram para o "?" | Estados conferidos |
|---|---|---|---|
| Multas: cadastrar, completar, retificar (`fines/form.twig`) | Tipo × Data/hora (19 px → 0), Enquadramento × Órgão autuador (49 → 0), Número × Bairro (2 → 0), Placa × UF (70 → 0), Código × Desdobramento no celular (22 → 0). Espécie e RENAVAM lado a lado; "Não tem o RENAVAM?" e "Cor e ano" em linha inteira. | Tipo (novo "?"), Data/hora (horário de Marília, futuro e, na edição, a data mais antiga anotada), Enquadramento, Órgão autuador (novo: quando a observação passa a ser exigida), AIT e a ausência dele, Descrição, Relato, Radar do inventário, Agente operador, Velocidades (margem 7 km/h / 7%), Referência da evidência, Agente da constatação, Observação sobre a origem (texto integral), Placa (exigência desde o rascunho com radar), Espécie, RENAVAM (formato não confirma cadastro), ausência do RENAVAM, Motivo da retificação | Origem radar/agente/ainda não sei; excesso de velocidade e outra infração; envio recusado com erro de placa e RENAVAM; rascunho (574) e registrada (567); disclosures abertos |
| Equipamentos: cadastrar e editar (`equipment/form.twig`) | Órgão × Série (26 → 0), CEP × Cidade (2 → 0), Preventiva × Data de instalação (21 → 0); características dos sete tipos com caixas de marcar ao lado de campos | Série, Patrimônio, regra do local (estava repetida fora da ajuda), aviso de veículo/patinete (no "?" de "Este ponto é"), as quatro orientações `help` do catálogo (agora em `ajuda.spec(f)`), Preventiva, Data de instalação, "mude a situação pela ficha" (já no "?" da Situação) | Radar, Câmera, Balança, Cancela, Totem, Veículo, Patinete; edição |
| Agentes: cadastrar e editar (`agents/form.twig`) | Órgão × Situação inicial (2 → 0), CEP × Cidade (2 → 0) | Explicação da base e da posição atual (no "?" de Nome da base); frase do CEP do endereço (já no "?" do CEP) | Cadastro e edição |
| Ficha do agente (`agents/show.twig`) | Providência: O que foi feito × Próxima conferência pela grade comum | Nota da ciência, Responsável, Próxima conferência, Relato, efeito da nova situação sobre o alerta de posição | Alerta com ciência registrada (agente 303) |
| Ficha do equipamento e Manutenção | Abrir ordem: Tipo × Quando × Data × Motivo no mesmo cabeçalho | Resultado real da manutenção (os dois textos unidos ao "?"), data programada, mudanças possíveis da situação; marcadores `*` nos campos obrigatórios | Ordem aberta, concluir, cancelar, mudar situação |
| Ficha da multa (`fines/show.twig`) | A conferência se refere a × Data a que se refere (48 → 0), Fonte × Referência da fonte (47 → 0) | Data a que se refere (rótulo encurtado), Referência da fonte, O que estava errado, Motivo/observação, lembrete (o lembrete atual desta multa continua à vista), data do lembrete, Motivo da correção; Fase ganhou o "?" das fases | Conferência posterior com pendência e "Escolher data"; "Não verificada"; fase encerrada + pagamento informado + recurso indeferido |
| Planejar reforço (`planning/index.twig`) | Ponto de apoio × Observação (47 → 0) | Justificativa de outro agente; Ponto de apoio, incluindo que o centro do quadrado não é local seguro de parada | Disclosure aberto; concluir/cancelar missão sem mudança (rótulo oculto + placeholder, coerente) |
| Contas | Mesmo cabeçalho em todos os campos | Formato do usuário (estava no texto e no "?") | Formulário de nova conta |
| Filtros de Equipamentos, Agentes e Multas | Mais filtros de Multas (30 → 0); rótulo "Agente (da constatação ou operador do radar)" virou "Agente" + "?" | Explicação do filtro de agente | Mais filtros abertos em 1440 e 768 px |
| Central, Revisões, Retrospecto, Sobre, Login | Sem degrau antes nem depois | Nada: os textos dessas telas são informação principal | Capturas em 1440 e 390 px |

## Verificação

- **Medidas** (`medidas-antes.json`, `medidas-depois.json`): topo de cada controle por linha de grade, em 127
  combinações de tela, estado e largura (1440, 768, 720 = zoom de 200% em 1440, 390 e 360 px). Antes: até 70,2 px.
  Depois: 0 px em todas as linhas, sem rolagem horizontal.
- **Ajuda** (`ajuda-verificacao-navegador.json`, `scripts/verificacao-interface/ajuda.cjs`, só leitura): as 21
  verificações passaram (mouse, Tab/Enter, Esc, toque em 375 e 360 px, caixinha dentro da tela, leitor de tela,
  movimento reduzido, sem JavaScript, console sem erros nem CSP).
- **Referências**: em 18 telas e estados, nenhum `aria-describedby` aponta para descrição removida; os 139 "?"
  visíveis abrem por toque inteiros dentro de uma tela de 360 × 740.
- **Testes**: `HelpTipTest` passou a fixar a estrutura comum (fixture `ui-campos-sem-ajuda.html` regravada), a
  conferir que não sobra texto entre rótulo e controle e que o `help` do catálogo chega ao "?". Suíte completa: 283
  testes passando. Assets gerados com `npm run build`.

Ambiente: pilha local `pf-rev` (Docker, banco de demonstração), conta de teste local. A única gravação foi a
ciência de um alerta de demonstração (agente 303), para abrir o formulário de providência; os erros de formulário
vieram de envios recusados pelo servidor (nada salvo).

## Capturas

Pares `capturas/<tela>@<largura>-antes.jpg` e `-depois.jpg` (página inteira; em 1440 px sem a barra lateral e
reduzidas a 75%). Estados: `multa-nova-radar-velocidade` (origem radar + excesso de velocidade, disclosures
abertos), `multa-nova-erros` (placa e RENAVAM recusados), `multa-ficha-conferencia-posterior` (conferência
posterior, com pendência, "Escolher data"), `multa-ficha-situacao` (encerrada, paga, recurso indeferido),
`equip-novo-radar`/`-camera` (tipos), `agente-ficha` (alerta com ciência), `planejar` (passo 3, com o "?" do ponto
de apoio aberto), `filtros-multas` (Mais filtros aberto).

## Não verificado

- Leitor de tela real (NVDA/VoiceOver): conferida só a descrição acessível pelo navegador.
- Zoom de 200% foi simulado com a largura de 720 px, não com o zoom do navegador.
- O site publicado não foi alterado nem conferido: nada foi publicado.
