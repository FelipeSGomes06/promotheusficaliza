# Domínio, dados e regras a tornar explícitas

Este é um modelo conceitual. O Claude define tabelas, tipos, nomes, índices, SQL e estrutura de classes depois da prova de compatibilidade. Não converter mecanicamente cada linha abaixo em uma tabela.

## Conceitos principais

| Conceito | Informação relevante | Regra central |
|---|---|---|
| Equipamento | Identificador interno, patrimônio opcional, série, marca, modelo, tipo e órgão | Identidade é estável mesmo após mudança de lugar ou situação. |
| Tipo e capacidades | Categoria e características próprias | Radar, câmera e veículo aceitam propriedades adequadas e validadas no servidor. |
| Órgão responsável | Nome e identificação de demonstração | Permite atribuição e filtro de equipamentos e agentes. |
| Local de instalação/base | Endereço e coordenadas | Representa instalação fixa ou base; não confundir base com posição atual de recurso móvel. |
| Bairro/região | Referência geográfica e agrupamento | Região deve ter regra documentada, sem inventar divisão oficial. |
| Agente | Identificação fictícia de exibição, função, órgão, situação e base | Agente operacional é diferente de conta de acesso ao sistema. |
| Posição observada | Recurso, coordenadas, instante observado/recebido e origem | Posição antiga não é apresentada como atual. |
| Manutenção | Necessidade, prioridade, planejamento, vencimento e conclusão | Encerramento e reagendamento preservam histórico. |
| Evento/histórico | Recurso, fato, instantes, origem e identificação de duplicidade | Um evento explica uma mudança; retenção e volume são controlados. |
| Alerta | Motivo, gravidade, recurso, início e estado | Reconhecer alerta não resolve automaticamente a causa. |
| Análise preventiva | Alvo, horizonte, evidências, método, versão e execução | Resultado é reproduzível e expõe suas limitações. |
| Conta e auditoria | Papel, autoria e ação | Autorização no servidor e registro mínimo, sem segredos. |

## Campos e validações

Equipamentos precisam distinguir categoria, subtipo e capacidades. Para um radar de velocidade, limite em km/h é um dado estruturado; para fiscalização semafórica, não impor limite obrigatório. Para câmeras, representar resolução e recursos como visão noturna e imagem colorida. Não guardar tudo em observações livres.

Escolher entre detalhes específicos por tipo e atributos extensíveis com esquema validado. Não presumir que recursos JSON de outro banco existam no Firebird escolhido. Evitar um mecanismo EAV genérico sem necessidade; documentar como incluir um novo tipo com segurança.

Definir política de série: um identificador interno é sempre único; a série do fabricante não é universalmente única entre marcas. O Claude deve escolher e documentar a composição adequada, incluindo séries desconhecidas, sem depender apenas da validação visual. Concorrência precisa ser coberta por restrições do banco quando aplicável.

Validar CEP como formato brasileiro e permitir referência textual sem exigir número de rua. Não incluir CPF, contato pessoal, credenciais, IP de dispositivo ou senha de equipamento. Cidade/UF da demonstração são Marília/SP; caso o produto fixe esse município, a validação do servidor deve refletir isso.

Coordenadas usam latitude/longitude WGS84 e unidades declaradas. Validar faixas e rejeitar valores não numéricos. Documentar a ordem das coordenadas em cada fronteira, especialmente se houver GeoJSON, cuja ordem difere das convenções de várias bibliotecas de mapa. Uma área retangular de visualização não prova que um ponto está dentro do limite municipal.

Para equipamentos ainda não instalados, usar endereço/coordenadas planejados ou da base, identificados como tal. Recursos sem coordenadas válidas aparecem em lista de pendências; não recebem posição fictícia 0,0. No conjunto de demonstração, todos os recursos necessários ao mapa devem ter localização válida.

## Situação, manutenção e comunicação

Manter dimensões separadas:

- **Situação operacional:** ainda não instalado, operacional, mau funcionamento, em manutenção, desativado, furtado.
- **Necessidade de manutenção:** nenhuma, imediata ou programada/periódica; com datas quando cabíveis.
- **Atualidade da observação:** atual, atrasada ou sem informação; derivada do tempo e da origem.
- **Situação do agente:** opções operacionais coerentes, como em atividade, indisponível e fora de serviço, definidas pelo Claude.

Um equipamento operacional pode ter revisão preventiva próxima. Agendar manutenção não deve colocá-lo automaticamente fora de operação. Iniciar intervenção e concluir intervenção são mudanças explícitas; concluir uma ordem não prova que o equipamento voltou a funcionar. Furto não é um tipo de manutenção.

Cada transição relevante preserva antes/depois, autoria/origem e instante. Definir conflitos entre atualização manual e simulação, fechamento de alertas e tratamento de eventos atrasados. Arquivamento retira da operação atual, mas não apaga períodos históricos.

## Indicadores que o avaliador consegue conferir

**Total cadastrado:** equipamentos não arquivados no escopo indicado. **Operacionais:** subconjunto cuja situação é operacional. **Indisponíveis:** definir como os que deveriam estar em serviço e estão em falha, intervenção ou furto. Mostrar ainda não instalados e desativados separadamente para não esconder essas categorias.

**Disponibilidade atual:** operacionais divididos pelos equipamentos considerados em serviço. A proposta exclui ainda não instalados e desativados desse denominador, mas inclui falhas, intervenções e furtos. Sem denominador, mostrar “não aplicável”, não 0% nem 100%. Se a telemetria estiver atrasada, informar quantos estados são desconhecidos/antigos e não afirmar disponibilidade atual confirmada com base neles. O Claude deve definir o tratamento final e deixar a fórmula visível.

**Agentes ativos:** agentes não arquivados em atividade, no mesmo escopo dos filtros. **Manutenções próximas:** prioridade imediata ou data dentro de janela configurada; distinguir vencida de futura. Indicadores de recursos diferentes não devem somar agentes e equipamentos sem explicar a unidade.

Disponibilidade histórica exige duração de estados e intervalo analisado, não a média ingênua das porcentagens dos cartões. Tratar intervalos sem observação, alterações no inventário e períodos fora de serviço de forma documentada. Não prometer essa métrica se não houver histórico suficiente.

## Parte 3 proposta: atenção preventiva para sete dias

Pergunta operacional: **quais equipamentos merecem revisão nos próximos sete dias e por quê?**

Entradas possíveis: recorrência e duração das falhas observadas, tempo desde última manutenção, revisões previstas, atualidade e cobertura do histórico. O Claude escolhe regras, janelas e pesos simples, registra a justificativa e verifica o resultado contra casos conhecidos. Manutenção já vencida é um sinal administrativo distinto de uma tendência de falha.

Saída mínima: classificação de atenção, horizonte, fatores determinantes, período e quantidade de dados analisados, data do cálculo e ação recomendada. Preferir categorias compreensíveis. Se houver pontuação numérica, informar que é um índice heurístico, **não probabilidade calibrada de falha**.

Histórico insuficiente ou simulado deve estar explícito. Não declarar acurácia municipal real, previsão de crime ou eficácia de intervenção a partir desse conjunto. Para demonstrar antecipação, reservar um trecho temporal futuro do cenário e verificar se a regra emite avisos antes dos eventos; comparar com uma referência simples e registrar falsos alertas e ocorrências não antecipadas. Não usar o mesmo trecho para ajustar e avaliar e não escolher o futuro depois de observar a saída.

Uma conclusão honesta de utilidade limitada é melhor que um número de acurácia inventado. Toda recomendação continua sendo apoio humano, sem alterar recursos automaticamente.

## Dados demonstrativos

Proposta de escala, ajustável: dezenas de equipamentos de categorias variadas, agentes fictícios em órgãos distintos e algumas semanas de histórico. Cobrir cada situação, manutenção imediata/vencida/próxima, falha de comunicação, recurso móvel, entrada duplicada e evento atrasado. Não é necessário produzir milhares de pontos.

Usar coordenadas plausíveis para apresentação em Marília, sempre identificadas como ilustrativas. Não usar nomes ou matrículas reais de policiais/agentes nem associar pontos fictícios a instalações reais. Se dados oficiais forem acrescentados depois, registrar fonte, licença, data, qualidade e separação dos simulados.

Sementes e exemplos são públicos e reproduzíveis; credenciais de login não são dados de domínio. Backups e arquivo físico do banco não entram no repositório. Fazer ao menos um teste de restauração com dados sintéticos.
