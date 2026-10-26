# ADR-0018 — Landing: seis pilares, foto de Marília e 3D sob demanda

Data: 22/10/2026 · Situação: aceita · Atualiza: [ADR-0015](0015-landing-publica-e-marca-3d.md) · Relacionadas:
[ADR-0016](0016-planejamento-preventivo-por-setores.md) (Planejar), [ADR-0017](0017-aba-multas.md) (Multas)

## Contexto

A auditoria da landing publicada (22/10/2026) apontou
que a página apresentava uma versão menor do produto: Multas e Planejar reforço não apareciam (só no rodapé e na meta
description), as capturas de 19/10 tinham o menu antigo, a distinção entre operação fictícia e dados reais ficava no
fim, o JavaScript saía sem compressão, o cabeçalho apertava perto de 900 px, as capturas desktop ficavam ilegíveis no
celular e o 3D era baixado mesmo na entrada direta por `#sistema` (achados A01–A06). O autor pediu também uma foto real
de Marília atrás da marca, reagindo ao mouse em camadas de profundidade.

## Decisões

### Conteúdo: seis pilares, não 30 cartões
- Estrutura: hero → **O sistema** (central) → **Recursos** em quatro cenas (inventário e agentes, manutenção, multas,
  revisões) → **Planejar reforço** em destaque próprio, escuro, com o mapa de calor → **Dados e acesso** → placa de
  chamada → **Para o avaliador** → rodapé. Âncoras antigas mantidas (`#inicio`, `#conteudo`, `#sistema`, `#recursos`,
  `#avaliador`) e novas (`#inventario`, `#manutencao`, `#multas`, `#revisoes`, `#planejamento`, `#dados`). O menu do
  topo continua com três links; um índice dentro de Recursos leva às funções.
- Capacidades secundárias ficam em um `<details>` nativo por cena (funciona sem JavaScript). A matriz F01–F30 da
  auditoria é o instrumento de revisão; a cobertura está na [evidência](../evidencias/2026-10-22-landing-atualizada.md).
- Limites perto de onde importam: nota curta na hero (operação fictícia; Planejar com dados reais agregados do
  Infosiga-SP), selo "Registro demonstrativo" e nota em Multas (sem consulta, emissão, cobrança ou protocolo), nota em
  Revisões (pontuação não é probabilidade), legenda junto do mapa de calor (cor = estimativa semanal, não emergência) e
  a seção Dados e acesso. A rotina de atualização é descrita como agendável, sem anunciar sua ativação em produção.

### Capturas
- Feitas em 22/10/2026 num Compose isolado (`pf-landing`, porta 8099) com `demo:seed` (perfil compacto),
  `urban:import` e uma conta de teste criada com `user:create`; só navegação e leitura nas telas. Desktop 1440 × 900 @2x,
  celular 390 × 844 @2x.
- Cada captura tem **recorte próprio do celular** (`<picture>` com `<source media="(max-width: 719.98px)">`), não a
  mesma imagem desktop reduzida. Os quatro pontos da central têm posições próprias no recorte móvel (o 4º marca o
  indicador "Ao vivo" das consultas a cada 5 s, já que a ficha não aparece nele).
- **Ampliar**: cada captura é um link para a versão maior. Sem JavaScript, abre o arquivo; com JavaScript, abre um
  `<dialog>` modal (`ampliar.js`): Esc, Fechar ou clique fora fecham, e o foco volta ao link. No celular a imagem fica
  com 1000 px e rola dentro do diálogo. O rótulo "Ampliar" fica abaixo da imagem, sem cobrir conteúdo nem o crédito do
  mapa. Objetos 3D das cenas ficam no vão entre a via e a captura no desktop e saem no celular (cobririam a imagem).

### Hero com a foto de Marília
- Foto: "Marília panorama3.JPG", Filipe Mesquita de Oliveira, Wikimedia Commons, **CC BY-SA 3.0** (licença conferida
  na API do Commons em 22/10/2026). É a candidata A da auditoria: horizonte de prédios reconhecível, muito céu atrás
  das ondas da marca. Recortada (sem o objeto do canto superior nem o telhado e a antena do primeiro plano),
  dessaturada, escurecida em gradiente para o grafite e servida localmente em WebP: 1280 × 764 (29 KB) e 720 × 430
  (11 KB). O derivado segue CC BY-SA 3.0; crédito no rodapé e em [licenças e fontes](../licencas-e-fontes.md). Foto de
  2006, não atual.
- É HTML comum (`<picture>` atrás do canvas), não textura: nenhum renderer novo, e continua visível sem WebGL, sem o
  módulo 3D ou sem JavaScript. Máscara linear estática nas bordas; nada de filtro, blur ou máscara animada.
- **Um único dono do ponteiro**: `palco.js` ouve `pointermove` na hero (só mouse, `hover: hover` e `pointer: fine`),
  normaliza a posição no palco (−1…1, retângulo lido uma vez por entrada e de novo só depois de rolar/redimensionar) e
  move a foto até ±12 × ±8 px (camada distante, sentido oposto) com `gsap.quickTo`; a marca recebe `controle.apontar(x,
  y)` e gira até ~5° × 3,5° (antes ~12° × 7°, com seu próprio ouvinte em `window`, removido). A foto tem 6% de sobra
  (`scale: 1.06`), e a borda não aparece nos quatro extremos. Volta ao repouso ao sair da hero ou parado 2,2 s; com
  movimento reduzido (sistema ou `pf-motion`, inclusive trocado depois da carga) volta na hora e não responde. Só
  depois da montagem; no toque, nunca.

### Carga do 3D sob demanda
- O pedaço 3D (three.js) só é pedido quando a hero fica ao menos 35% visível por 300 ms, ou quando um objeto das cenas
  chega a 300 px da tela (os dois usam o mesmo `WebGLRenderer`). Na entrada por uma âncora abaixo da hero, a vigia só
  começa depois da rolagem inicial até ela (`scrollend`, ou 1,5 s): a página pinta no topo e rola depois, e isso não
  pode contar como "hero na tela". Medido: `/#sistema` deixou de baixar 617 KB de JS e de criar os canvas (detalhes na
  evidência).

### Cabeçalho e compressão
- O menu compacto passa a valer abaixo de **1080 px** (a barra completa precisa de ~1020 px); só as regras da
  navegação mudaram, as cenas e a chamada seguem em 900 px. Em janelas baixas o painel do menu rola sozinho
  (`max-height` + `overflow`), porque o cabeçalho fixo não deixaria a página alcançá-lo.
- `AddOutputFilterByType DEFLATE` ganhou `text/javascript` nas duas configurações Apache (`apache-site.conf` e
  `apache-origem-dedicada.conf`): o Apache serve `.js` com esse tipo, e só `application/javascript` estava na lista.
  Commit isolado.

### Metadados
Title, description e Open Graph novos, `canonical` e `og:url` a partir de `url_publica` em `landing/config.php`, e
imagem de compartilhamento 1200 × 630 local (`img/compartilhar-1200x630.jpg`, foto + marca + nome, também CC BY-SA 3.0
pela foto).

## Consequências
- A página ficou mais longa (seis pilares e a seção de dados); imagens na carga inicial do desktop sobem de ~93 KB para
  ~170 KB (mais capturas e a foto), todas lazy abaixo da dobra, exceto a foto.
- Com gzip no Apache, a carga de `/` no desktop cai de ~908 KB para ~435 KB transferidos; o custo de CPU da cena 3D na
  hero continua igual (é a mesma cena) e segue sendo o principal custo no celular.
- Trocar a foto: substituir `marilia-1280.webp` e `marilia-720.webp` mantendo o recorte (céu em cima, prédios
  embaixo) e atualizar crédito no rodapé e em licenças.

## Alternativas descartadas
Foto como textura no Three.js (dependeria do WebGL e de pós-processamento); um segundo ouvinte de ponteiro na foto
(disputaria a rotação com o da marca); distorção de água, partículas, vídeo em autoplay ou mapa "ao vivo" fictício
(proibidos pelo pedido); mesma imagem desktop maior no celular (continuaria ilegível); zoom só por hover (inacessível
no toque e no teclado).

## Atualização (22/10/2026, mesma data)
A foto do Commons foi trocada, a pedido do autor, por uma foto aérea do terminal rodoviário de Marília de **Pedro
Monteiro** (@pvictormonteiro), com crédito no rodapé e em [licenças e fontes](../licencas-e-fontes.md). Recorte
1280 × 640 sem a marca d'água, mesmo tratamento (dessaturação, escurecimento em gradiente), 77 KB no desktop e 28 KB no
celular. O comportamento do palco não mudou.

## Atualização (22/10/2026, ajustes visuais)
A pedido do autor: a foto da hero volta às **cores originais** (só o recorte sem a marca d'água, 101 KB no desktop e
38 KB no celular), com uma máscara elíptica no lugar das duas faixas retas, para o terminal aparecer de fato atrás da
marca. A ficha do agente, na cena de inventário, passa a ter a mesma largura da captura principal (antes ficava estreita
e alinhada à direita). Os objetos 3D saem da via, onde encostavam no texto, e vão para o **canto da página**, do lado da
captura, no vão vertical depois de cada cena: não cobrem texto, captura nem a via em nenhuma largura de desktop; no
celular continuam fora (nem pedem o 3D).

## Atualização (23/10/2026)
Os objetos 3D das cenas foram substituídos por planos cinematográficos no fluxo da página, com renderer próprio; a hero
não mudou e deixou de ser baixada por causa das cenas. Diagnóstico e decisões no
[ADR-0019](0019-landing-planos-3d-cinematograficos.md).
