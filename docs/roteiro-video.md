# Roteiro do vídeo de demonstração (3–5 min)

Antes de gravar: simulador ligado, conta de avaliação criada, navegador em 1440×900, **nenhum** `.env`, senha,
painel de hospedagem ou dado pessoal na tela. A duração é sugestão deste roteiro, não regra da DSIN.

| Tempo | Cena | Fala/mostrar |
|---|---|---|
| 0:00 | Visão geral | Começar na tela de login: "só entra quem o administrador cadastrou". Entrar. "Promotheus Fiscaliza: inventário, central e revisões preventivas para Marília. Tudo aqui é fictício" — abrir o aviso "Demonstração". Mostrar os três resumos e abrir "Mais indicadores" (disponibilidade registrada × confirmada por sinal). |
| 0:35 | Mapa | Tocar num grupo (aproxima), num radar → ficha resumida: tipo e endereço primeiro, código como plaqueta, situação, "Sinal atual · há…", ilustração da categoria. Abrir a legenda: instalado, planejado, base, última posição. |
| 1:05 | Atenção | "Ver o que precisa de atenção": mapa, lista e números mudam juntos, cada item com o motivo ("Sem sinal recente — não confirma falha"). Alternar para Lista e voltar ao Mapa com a mesma seleção. |
| 1:30 | Tempo real | Linha "Atualizado às…" abaixo do mapa; a ficha aberta continua aberta e o mapa não se move. Pausar e retomar. |
| 1:50 | Cadastro | Cadastrar radar: marcar no mapa, deixar "Velocidade" sem limite (erro com resumo, digitação mantida), corrigir e salvar. Instalar (Não instalado → Operacional) e mostrar o histórico com autor. |
| 2:40 | Alerta | "Simular falha" → aparece em "precisam de atenção". Registrar ciência: continua aberto até a causa sumir. |
| 3:05 | Manutenção | Lista por andamento e urgência; iniciar uma urgente (efeito escrito ao lado); mostrar que outra ordem do mesmo equipamento fica "Aguardando"; concluir informando "Continua com mau funcionamento". |
| 3:35 | Revisões recomendadas | "Revisar primeiro" com o porquê e o que fazer; abrir o retrospecto e dizer: "prioridade heurística, histórico sintético, cerca de 2× o acaso, revocação baixa". |
| 4:10 | Engenharia | Terminal: `docker compose exec app vendor/bin/phpunit` (testes no Firebird real, inclusive concorrência com processos separados). Abrir README e docs. |
| 4:30 | Fecho | Publicação proposta em origem dedicada. |
