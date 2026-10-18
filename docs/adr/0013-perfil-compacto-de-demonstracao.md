# ADR-0013 — Perfil compacto de demonstração (23 recursos)

Data: 18/10/2026 · Situação: aceita

**Contexto.** O cenário tinha 69 recursos (48 equipamentos e 21 agentes). Na apresentação, isso virava telas cheias de
itens parecidos competindo por atenção. Os 69 não são exigência de nenhum requisito: o que importa é cobrir categorias,
características, situações e exemplos de manutenção e localização.

**Decisão.** Dois perfis em [`DemoScenario`](../../src/Demo/DemoScenario.php), escolhidos por `DEMO_PROFILE` ou
`demo:seed --perfil=…`:

- **compacto (padrão): 16 equipamentos e 7 agentes** — 4 radares, 4 câmeras, 1 balança, 1 cancela, 1 totem,
  3 veículos e 2 patinetes; 3 agentes de trânsito, 2 guardas e 2 policiais. Redução de 66,7% (46 de 69).
- **completo: 48 e 21**, para testes de volume e para a metodologia da análise.

O compacto é uma **curadoria por série** (não corte aleatório): cada item cobre mais de uma capacidade.

| Exemplo exigido | Onde está no perfil compacto |
|---|---|
| Operacional, falha, manutenção, furto, desativado, planejado | vários · radar da rodovia · veículo MV-04 · câmera e patinete · totem · radar da Av. João Ramalho |
| Manutenção em execução, imediata, vencida, próxima, futura | veículo na oficina · radar em falha · lombada (−4 dias) · balança (+3) · cancela (+18) |
| Sinal recente, atrasado, sem informação | demais · câmera da saída norte (concentrador com 25 min de atraso fixo) · câmera do terminal (silenciosa) |
| Base × última posição | veículos e patinete circulando (base separada); patinete furtado com posição antiga; agentes em turno × fora de serviço |
| Agente em turno sem posição recente; agente indisponível | Hermes 06; Têmis 04 |
| Evento atrasado que não altera a situação | radar multifunção da Av. Sampaio Vidal |
| Histórico curto (análise sem classificação) | câmera instalada há 45 dias |
| Todas as características por tipo | radar multifunção e lombada; câmera PTZ 4MP, 4K e 1080p; balança dinâmica; cancela por TAG; totem; veículos flex/elétrico/diesel |

Para que vencida, próxima e futura existam em qualquer semente, três preventivas abertas têm data fixa relativa à carga
(`PENDING_PREVENTIVE`); antes isso dependia do sorteio do histórico. O "sinal atrasado" sobrevive ao simulador ligado
porque o simulador entrega o sinal desse equipamento sempre com a mesma defasagem (`LAGGING_SERIALS`).

As contagens são verdadeiras: os indicadores leem o banco carregado, não escondem itens de um cenário cheio
(`DemoProfileTest` confere 16/7 nos indicadores, todas as situações, urgências, atualidades, tipos de posição e a
integridade das relações; o perfil completo continua 48/21).

**Segurança dos dados.** Nada muda nas proteções: trocar de perfil num banco com dados exige
`demo:seed --reset`, `DEMO_ALLOW_RESET=true` e o marcador do conjunto sintético; instalação com dados reais é recusada.

**Consequências.** Títulos sem "(ref. ilustrativa)"/"(fictícia)" em cada linha: a transparência fica no aviso global
"Demonstração", na página Sobre e na ficha ("ponto ilustrativo"); nomes de órgãos continuam com "(fictício)" para não
serem confundidos com órgãos reais. A análise preventiva tem menos pares no compacto; os números foram remedidos e
publicados lado a lado com o completo em [avaliação das sementes](../evidencias/2026-10-18-avaliacao-sementes.md#perfis-compacto-e-completo-18102026).
