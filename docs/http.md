# Rotas HTTP e permissões

Todas as rotas ficam sob `APP_BASE_PATH` (padrão `/promotheusfiscaliza`). Redirecionamentos, formulários, `fetch`,
assets e cookie de sessão usam o prefixo. Escrita = `POST` com token CSRF (`_csrf` no corpo ou `X-CSRF-Token`).

| Método | Caminho | Acesso | Resposta |
|---|---|---|---|
| GET | `/` | público | Central (HTML renderizado no servidor + JSON inicial embutido). |
| GET | `/api/central` | público | Instantâneo JSON (ver abaixo). `Cache-Control: no-store`. |
| GET | `/equipamentos` | público | Lista paginada (20). Query: `q`, `categoria[]`, `situacao[]`, `bairro[]`, `orgao[]`, `ordem` ∈ {codigo, tipo, situacao, bairro, atualizado}, `arquivados=1`, `pagina`. |
| GET | `/equipamentos/{id}` | público | Ficha, características, manutenção, alertas, análise e histórico. |
| GET | `/equipamentos/novo` · `/equipamentos/{id}/editar` | operador | Formulários. Visitante → `302 /entrar?voltar=…`. |
| POST | `/equipamentos` · `/equipamentos/{id}` | operador | Cria / altera (com `version`). 303 → ficha; 422 com erros por campo; 409 em conflito de versão. |
| POST | `/equipamentos/{id}/situacao` | operador | `status`, `note` (motivo obrigatório). Só transições permitidas. |
| POST | `/equipamentos/{id}/arquivar` | operador | `reason`. |
| POST | `/equipamentos/{id}/simular-falha` | operador | Evento fictício de telemetria pelo caminho do simulador. |
| POST | `/equipamentos/{id}/manutencoes` | operador | `kind`, `priority`, `due_date` (programada), `reason`. |
| POST | `/manutencoes/{id}/iniciar` · `/concluir` · `/cancelar` | operador | Concluir: `resolution`, `result_status`; cancelar: `reason`. |
| POST | `/alertas/{id}/reconhecer` | operador | `note` opcional. Com `Accept: application/json` responde JSON. |
| GET | `/agentes`, `/agentes/{id}` | público | Lista e ficha. Formulários e escrita como nos equipamentos (operador). |
| GET | `/manutencao` | público | Quadro por urgência e encerradas recentes. |
| GET | `/antecipacao` | público | Última análise e avaliação. `POST /antecipacao/recalcular`: operador. |
| GET | `/sobre` | público | O que é real, simulado e implementado. |
| GET/POST | `/entrar` · POST `/sair` | público | Login (sessão regenerada) e logout (sessão destruída). |
| GET/POST | `/contas`, POST `/contas/{id}/situacao` | administrador | Lista, cria (senha exibida uma vez), ativa/desativa. |
| GET | `/saude` | público | `{"status":"ok"}` ou 503 — sem versões, caminhos ou dados. |

Códigos: 401 (precisa entrar), 403 (papel insuficiente, CSRF inválido ou `Origin` estranho), 404, 405, 409 (regra ou
conflito), 422 (validação), 503 (banco indisponível), 500 (inesperado). Páginas de erro trazem só mensagem segura e
um **código de referência** que também aparece no log privado.

## Contrato de `GET /api/central`

```jsonc
{
  "generated_at": "2026-10-18T11:31:40-03:00",   // instante do instantâneo (America/Sao_Paulo)
  "filters": {"categoria": [], "situacao": [], "bairro": [], "orgao": []},   // filtros efetivamente aplicados
  "scope": {"equipment": true, "agents": true},
  "kpis": {"equipment": {"total": 48, "operational": 38, "in_service": 43, "availability": 0.8837, "availability_confirmed": 0.8372, …},
           "agents": {"total": 21, "active": 14, "position_not_current": 1, …}},
  "resources": [{"kind": "EQUIPAMENTO", "id": 7, "code": "RAD-0002", "category": "RADAR", "title": "…", "status": "OPERACIONAL",
                 "freshness": "ATUAL", "last_signal_at": "…", "position": {"lat": -22.2097, "lng": -49.9526, "kind": "INSTALADO", "observed_at": null},
                 "maintenance": {"urgency": "PROXIMA", "due_date": "2026-10-22", …} | null, "alerts": 0, "highlights": [{"label": "…", "value": "…"}], …}],
  "regions": [{"name": "Fragata", "equipment": 9, "unavailable": 2, "availability": 0.7778, "agents_active": 3, "alerts": 2, …}],
  "maintenance": {"urgent": […], "upcoming": […]},
  "alerts": [{"id": 3, "severity": "ALTA", "state": "ABERTO", "message": "…", …}],
  "sync": {"last_event_id": 5198, "last_event_received_at": "…", "simulator_last_tick_at": "…", "simulator_running": true,
           "duplicates_ignored": 4, "late_events": 3}
}
```

`position.kind`: `INSTALADO`, `PLANEJADO`, `BASE` ou `ULTIMA_POSICAO` (com `observed_at` e `freshness`). Recurso sem
coordenadas vem com `lat: null` e aparece só na lista. Volume de demonstração: dezenas de recursos, sem paginação
oculta. Texto do banco é escapado (`JSON_HEX_TAG`/`JSON_HEX_AMP`) e o cliente o insere com `textContent`.
