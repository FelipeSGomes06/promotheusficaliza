# ADR-0008 — Interface para quem nunca usou o sistema

Data: 18/10/2026 · Situação: **substituída em parte** pela [ADR-0012](0012-identidade-motion-e-build-de-assets.md)
(paleta neutra, fonte do sistema, "sem framework nem biblioteca nova" e movimento só em CSS deram lugar à identidade de
trânsito, Tailwind/Alpine CSP/GSAP e build Vite). Hierarquia, três resumos, filtros essenciais, mapa e lista com o
mesmo conjunto, títulos "tipo · endereço" e estados com cor + forma + texto continuam valendo.

**Contexto.** A avaliação de 18/10/2026 mostrou uma interface completa, mas intimidadora: fundo escuro com brilho
laranja, faixa listrada de perigo permanente, seis indicadores com o mesmo peso, rótulos em caixa alta, códigos como
título e o mapa abaixo da primeira tela no celular (y≈825 px em 390×844). O público da demonstração inclui pessoas
sem conhecimento de fiscalização.

**Decisão.**

- Fundo claro e um sistema pequeno de tokens (`public/assets/css/app.css`, removido na ADR-0012): 6 cores base
  (`--paper #f3f5f7`, `--surface #fff`, `--ink #18212c`, `--muted #4f5b69`, `--line #d9dfe6`, `--signal #1b5eab`)
  e cores de estado usadas só com significado operacional. O vocabulário vem da **sinalização viária brasileira**:
  azul de indicação para ações e navegação, amarelo de advertência e vermelho de regulamentação reservados a
  manutenção/atraso e falha.
- Texto na fonte do sistema operacional (familiar e legível, sem download) e Space Grotesk, já presente no projeto,
  só em títulos e números. Sem rótulos em caixa alta; frases em caixa normal.
- Central com título humano ("Visão geral de Marília"), uma ação principal ("Ver o que precisa de atenção"), **três**
  resumos (funcionando, precisam de atenção, manutenções urgentes) e os demais indicadores/fórmulas em "Mais
  indicadores". Busca, tipo e situação à mão; bairro, órgão e "só o que precisa de atenção" em "Mais filtros".
- Mapa e lista mostram o mesmo resultado; a seleção liga marcador, linha da lista e ficha resumida e sobrevive à
  troca de visão e às atualizações. Marcadores próximos são agrupados (número + "!" quando há algo a atender);
  instalação, planejado, base e última posição têm forma e texto próprios.
- Títulos "tipo · endereço" (código como plaqueta secundária); nada de endereço inventado: sem endereço, vale o bairro
  ou "sem endereço cadastrado".
- Miniaturas redesenhadas em traço claro, uma por categoria, com a legenda "ilustração da categoria".
- Movimento curto (140–200 ms) só em resposta a ações (seleção, abertura de painel, confirmação); nada pulsa nem anima
  a cada consulta. `prefers-reduced-motion` e a preferência salva no rodapé desligam tudo.
- Manutenção numa lista única por andamento e urgência (em vez de quatro colunas), com o efeito de cada ação escrito
  ao lado do botão. "Antecipação" vira "Revisões recomendadas" (a URL `/antecipacao` e a referência à parte 3 ficam).

**Consequências.** Sem framework nem biblioteca nova: Twig, CSS e JavaScript próprios. Contraste medido (texto ≥ 4,5:1,
bordas de controles ≥ 3:1). O tema escuro deixou de existir; não há modo escuro opcional (não havia escopo para
validá-lo). O agrupamento de marcadores é próprio (≈60 linhas em `central.js`), suficiente para dezenas de pontos.

**Alternativas.** Só trocar a paleta (mantinha a mesma hierarquia confusa); biblioteca de componentes ou
Leaflet.markercluster (dependências novas para um volume pequeno); manter quatro colunas de manutenção (exigia
leitura lateral e repetia caixas).
