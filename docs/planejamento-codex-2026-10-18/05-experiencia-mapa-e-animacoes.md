# Experiência, mapa de Marília e animações

## Conceito de produto

Uma central urbana com personalidade: leitura rápida de operação, mapa como foco e linguagem visual inspirada no fogo/conhecimento do Prometheus. O Claude decide composição, tipografia e paleta final. A experiência deve parecer um produto de gestão bem cuidado, com momentos lúdicos breves, sem dificultar a tarefa do gestor.

Reaproveitar a ideia de controle de movimento e som do portfólio. Assets, estilos e scripts do desafio são próprios e instaláveis no clone público; identidade comum não exige dependência do build privado do portal.

## Áreas de navegação

| Área | Pergunta que resolve | Conteúdo mínimo |
|---|---|---|
| Central | O que requer atenção agora? | Mapa, indicadores, filtros e alertas. |
| Inventário | O que existe e em que situação está? | Equipamentos, agentes, busca, cadastro e detalhe. |
| Manutenção | O que preciso revisar e quando? | Urgentes, vencidas, próximas e histórico de intervenções. |
| Antecipação | O que vale prevenir nos próximos dias? | Uma análise explicável, evidências e recomendações. |
| Sobre a demonstração | O que é real, simulado e implementado? | Limitações, fontes, tecnologia, IA utilizada e link público do projeto. |

Não criar landing page longa como obstáculo ao sistema. O visitante deve chegar rapidamente ao mapa e encontrar a indicação de dados fictícios. Em telas pequenas, mapa e lista podem alternar, mantendo seleção/filtro e acesso a todas as informações.

## Mapa

Abrir em **Marília, São Paulo, Brasil**, com enquadramento aproximado da área urbana. Centro de visualização pode partir de aproximadamente latitude −22,22 e longitude −49,95 e deve ser conferido visualmente pelo Claude; é referência de viewport, não posição levantada ou limite oficial.

Recomendação inicial: Leaflet e tiles de provedor com política compatível. A biblioteca fornece marcadores, popups e ícones customizados; o Claude escolhe uma versão estável mantida e compatível com a CSP, sem adotar versão de desenvolvimento só por aparecer em uma referência. [Fonte: documentação oficial do Leaflet](https://leafletjs.com/reference.html).

Equipamentos fixos mostram instalação ou posição planejada, conforme cadastro. Veículos e agentes mostram última posição simulada com horário; a base permanece informação distinta. Pontos atrasados/sem posição têm tratamento explícito. Ao selecionar um item na lista, localizar e destacar o mesmo recurso no mapa sem perder filtros.

Clicar um ponto abre identificação, miniatura do tipo, marca/modelo, situação, órgão, endereço/referência, última atualização e manutenção. O detalhe completo deve estar acessível por teclado e lista alternativa, não só por gesto no mapa.

## Como mostrar o que cada ponto representa

Usar um vocabulário visual consistente: radar, câmera, balança, cancela, totem, veículo, patinete e agente possuem silhuetas distinguíveis. Ícone mostra categoria; forma/contorno e texto mostram situação; seleção tem destaque próprio. Cor nunca é o único sinal.

Criar miniaturas ilustrativas próprias ou usar assets com licença compatível: um radar parece radar e uma câmera parece câmera. Para tipos específicos, a ficha destaca a capacidade, como velocidade ou fiscalização semafórica. Não confundir a imagem ilustrativa de categoria com o modelo comercial real do equipamento.

**Modelos 3D são opcionais**, carregados ao abrir uma ficha ou seção específica, com miniatura estática de fallback. Não renderizar um objeto 3D para cada marcador. Definir orçamento de tamanho, licença, navegação por teclado e redução de movimento antes de incluí-los. A versão 2D precisa atender integralmente ao pedido de reconhecimento dos pontos.

Agrupar marcadores sobrepostos quando necessário. Um agrupamento deve informar quantidade e permitir aproximação. Rótulos, tooltip e popup usam dados escapados, conforme a segurança descrita no pacote.

## Regiões, fontes e cartografia

Mostrar resumo por bairro/região sem sugerir que uma divisão manual é oficial. Se houver polígonos, usar fonte e licença documentadas; se não houver, uma lista agrupada por bairro já fornece consolidação. Heatmap é diferencial e precisa indicar a variável representada, janela e escala.

Não apresentar pontos inventados como radares reais. A consulta encontrou publicação municipal histórica sobre fiscalização, mas isso não comprova localização, estado ou quantidade atual em 2026. A entrega inicial usa posições ilustrativas e não faz alegação de mapa oficial.

Tiles padrão do OpenStreetMap exigem atribuição, respeito ao cache e à identificação do cliente, e não autorizam download em massa ou prefetch indiscriminado. O serviço não tem garantia de disponibilidade; selecionar provedor adequado à carga e manter a opção configurável. Cache não deve contrariar a política do provedor. [Fonte: política de tiles da OSMF](https://operations.osmfoundation.org/policies/tiles/).

A atribuição deve permanecer visível e legível no mapa e em capturas. Se o mapa falhar, manter lista, indicadores e detalhes úteis, além de mensagem de indisponibilidade. Não baixar tiles para um pacote offline sem autorização do provedor.

Geocodificação não é requisito do núcleo. Se incluída, não usar Nominatim público como autocomplete por tecla: a política limita carga, exige identificação/atribuição e restringe usos. Preferir seleção no mapa ou coordenadas preenchidas, com busca explícita, cache e provedor apropriado. [Fonte: política do Nominatim](https://operations.osmfoundation.org/policies/nominatim/).

## Animações com função

- Entrada breve do painel, sem adiar acesso a dados e formulário.
- Destaque de seleção e abertura/fechamento de ficha com continuidade visual.
- Atualização discreta de indicador quando o valor muda, sem reiniciar toda a tela.
- Alerta novo com um sinal breve, mantendo texto e histórico depois do movimento.
- Movimento simulado de recurso móvel apenas quando houver observações suficientes, sem inventar posição atual entre dados muito antigos.
- Confirmação de cadastro ou manutenção com microinteração curta e mensagem textual.

Evitar pulsação infinita de todos os radares, flashes, parallax sobre o mapa, sons automáticos e animações que roubem o foco. Não deslocar o mapa sozinho a cada atualização. Se um item estiver sendo lido, preservar foco e seleção.

Respeitar preferência do sistema e oferecer controle manual de redução de movimento. Conteúdo permanece compreensível sem animação. A WCAG descreve técnicas para suprimir movimentos não essenciais e controles para conteúdo que atualiza continuamente. [Fontes: redução de movimento, W3C](https://www.w3.org/WAI/WCAG22/Techniques/css/C39) e [pausar conteúdo em atualização, W3C](https://www.w3.org/WAI/WCAG22/Understanding/pause-stop-hide).

## Estados que precisam de desenho

Carregando, sem dados, nenhum resultado do filtro, erro parcial, banco indisponível, tiles indisponíveis, sessão expirada, localização ausente, observação antiga, análise sem histórico, sucesso e conflito de edição. Erro deve indicar uma ação possível, sem expor detalhe técnico.

Formulários têm rótulos, ajuda de formato/unidade, erros junto aos campos e preservação dos valores válidos. Modais/fichas devolvem foco ao controle de origem. Verificar contraste, foco visível, zoom e navegação por teclado. Gráficos e mapa têm alternativa textual.

O detalhe lúdico final pode ser uma pequena narrativa do Prometheus explicando um evento ou prevenção. Priorizar clareza e domínio do problema; não incluir chat com LLM nem dependência paga para gerar mensagens que podem ser escritas no próprio produto.
