# Roteiro do vídeo de demonstração (3–5 min)

Antes de gravar: simulador ligado, conta de avaliação criada, navegador em 1440×900, **nenhum** `.env`, senha,
painel de hospedagem ou dado pessoal na tela. A duração é sugestão deste roteiro, não regra da DSIN.

| Tempo | Cena | Fala/mostrar |
|---|---|---|
| 0:00 | Central | "Promotheus Fiscaliza: inventário, central e antecipação para Marília. Tudo aqui é fictício — veja a faixa." Mostrar KPIs e abrir a fórmula da disponibilidade (registrada × confirmada por sinal). |
| 0:35 | Mapa | Ícones por categoria (quadrado = equipamento, círculo = agente), glifo + cor de situação, tracejado = observação antiga. Clicar num radar → ficha com miniatura e limite em km/h. |
| 1:05 | Filtros | Filtrar "Radar" + "Mau funcionamento": mapa, lista e cartões mudam juntos; agentes saem do escopo com aviso. Aba Bairros. |
| 1:30 | Tempo real | Barra de sincronização (horário dos dados, último evento, duplicatas e atrasos ignorados). Pausar e retomar. |
| 1:50 | Cadastro | Entrar. Novo radar: tentar "velocidade" sem limite (erro), série já usada pelo fabricante (erro do banco), salvar. Instalar (Não instalado → Operacional) e mostrar o histórico com autor. |
| 2:40 | Alerta | "Simular falha" num equipamento → alerta na central (anúncio acessível). Reconhecer: continua aberto até a causa sumir. |
| 3:05 | Manutenção | Abrir ordem, iniciar (equipamento vai para manutenção), concluir declarando a situação real; preventiva periódica agenda a próxima. |
| 3:35 | Antecipação | Lista de atenção com fatores e ação; método; avaliação ajuste × avaliação com a referência; dizer em voz alta: "índice heurístico, histórico sintético, ~2× o acaso, revocação baixa". |
| 4:10 | Engenharia | Terminal: `docker compose exec app vendor/bin/phpunit` (62 testes no Firebird real). Abrir README e docs. |
| 4:30 | Fecho | Publicação proposta em origem dedicada; IA declarada (Codex no plano, Claude na implementação). |
