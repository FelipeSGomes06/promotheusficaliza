# Landing pública com a marca montada em 3D — 19–20/10/2026

Pedido: landing pública em `/` de `fiscaliza.devfelipegomes.com.br`, no lugar do "Portal simulado", com identidade de
trânsito, hero com a marca oficial montada em 3D (Three.js + GSAP), capturas reais do sistema, recursos ligados por uma
via, chamada final e uma última seção preta "Para o avaliador" pronta para o vídeo. Decisões na
[ADR-0015](../adr/0015-landing-publica-e-marca-3d.md); uso e publicação em [landing/README.md](../../landing/README.md).

## O que mudou

| Onde | Mudança |
|---|---|
| `landing/` (novo) | `config.php` (URL do sistema, vídeo, ajustes da hero), `pagina.php` (template), `public/index.php` (CSP, escape, versão de arquivos), `src/` (CSS e JS), `vite.config.js`, build versionado em `public/landing-assets/promotheus/build/`, capturas WebP em `img/`, pasta `video/` |
| `docker/php/apache-site.conf` | `DocumentRoot /app/landing/public` (antes `/var/www/portal-stub`); `Alias /promotheusfiscaliza` intacto |
| `package.json` / `package-lock.json` | `three@0.186.1` (MIT, fixado) e o script `build:landing` |
| Documentação | ADR-0015, `landing/README.md`, README, operação, licenças, esta evidência |

## Onde a raiz é servida (conferido antes de mexer)

`curl -sI` e DNS em 19/10/2026: `fiscaliza.devfelipegomes.com.br` → 143.95.175.67 (`143-95-175-67.unifiedlayer.com`),
outro servidor que não o do portal (`devfelipegomes.com.br` → 50.6.184.29). Respostas com `Via: 1.1 Caddy` e
`Server: Apache`; a raiz devolve exatamente os 641 bytes de `docker/php/portal-stub/index.html` (ETag `281-…`),
`/index.php` responde 404 e `/promotheusfiscaliza/` responde 302 para o login com a CSP do `SecurityHeaders`. Ou
seja: Caddy → contêiner `app` do `compose.yaml` padrão, e a raiz vem de `/var/www/portal-stub`. Nada foi publicado.

## Capturas do sistema usadas na página

A captura de 19/10 sugerida (`02-central-depois-do-login.jpg`) estava sem o mapa de fundo e com o aviso "Mapa de
fundo indisponível", e o Docker local não subiu. As quatro capturas novas foram feitas no **sistema publicado**, no
Chrome do autor, com a conta dele já aberta por ele, só navegando (abrir uma ficha e rolar listas; nada foi
alterado): visão geral com o radar da Av. das Esmeraldas, inventário de equipamentos, central com um radar em mau
funcionamento e revisões recomendadas. Zoom de CSS para um layout de 1536 px, duas metades nativas unidas, WebP.

## Verificação

Servidor: `php -S 127.0.0.1:8095 -t landing/public` (PHP 8.5.9) com um roteador de teste fora do repositório para
simular "sem WebGL" (contexto WebGL anulado antes dos scripts) e "sem JavaScript" (HTML sem `<script>`). Navegadores:
painel de navegador do app (Chromium) e Chrome do autor (Windows, 1920×1080 a 90%).

| Cenário | Resultado |
|---|---|
| Montagem (quadros fixos da timeline) | quadrado → haste e braço instalados → encaixe 1 (1,27 s) → braço curto de lado → encaixe 2 (1,88 s) → sensor, ondas e faixas pintadas pelo pulso → pose quase frontal com o nome ([folha](2026-10-20-landing-publica/capturas/02-montagem-quadro-a-quadro.jpg), [animação](2026-10-20-landing-publica/videos/montagem-da-marca.webp)) |
| Cores renderizadas (pixel da captura) | base `#1d51e9` (oficial `#2152e8`), vias ≈ papel `#edf1f7`, sensor âmbar; a variante sem mapa de ambiente escurecia o azul e deixava o F cinza e foi descartada |
| Uma vez só | a montagem roda uma vez quando a hero aparece; rolar e redimensionar não a reiniciam (o botão "Pular animação"/"Rever montagem" foi retirado depois, a pedido do autor) |
| 360, 390, 768, 1366 (× 657 e 768), 1920 (× 960 e 1080) e ~2100 px | sem rolagem horizontal; em 360×640 e 1366×657 título e chamadas cabem na primeira tela (a nota de acesso em 1366×657 termina no limite) |
| Celular (390) | menu abre com o botão, fecha com Esc devolvendo o foco e ao tocar num link; painel com "Reduzir movimento" e "Acessar sistema" |
| Teclado | ordem: pular para o conteúdo → marca → âncoras → movimento → acessar → conhecer → acessar → acessar (placa) → autor; foco visível (turquesa no escuro, azul no claro) |
| Movimento reduzido (botão e sistema) | escolha salva em `pf-motion`; marca pronta sem sequência, sem entrada do texto, sem *scrub* nem transformações |
| Sem WebGL | nenhum canvas; marca oficial em SVG, nome, título e chamadas no lugar; objetos das cenas ausentes sem deixar buraco |
| Sem JavaScript | links da navegação visíveis, botões de menu e movimento ocultos, todo o conteúdo legível |
| Vídeo ausente (`src` nulo ou arquivo inexistente) | "Vídeo de apresentação em breve.", sem player nem requisição |
| Vídeo configurado (arquivo de teste, removido depois) | `<video>` com controles, `playsinline`, sem autoplay, faixa de legenda `pt-BR` ativa; sem violação de CSP |
| Console e requisições | 0 erros e 0 violações de CSP; todas as requisições 200 (HTML, CSS, `boot.js`, JS, pedaço 3D, fonte, WebP) |
| PHP | `php -l` sem erros em `config.php`, `pagina.php` e `public/index.php` |

### Desempenho (Lighthouse 12.8.2, Chrome headless, servidor PHP embutido **sem gzip**)

| Perfil | Desempenho | Acessibilidade | Boas práticas | SEO | FCP | LCP | TBT | CLS |
|---|---|---|---|---|---|---|---|---|
| Desktop | 90 | 100 | 100 | 100 | 0,7 s | 1,2 s | 210 ms | 0 |
| Celular (Moto G simulado, CPU 4×, 4G lento) | 46 | 100 | 100 | 100 | 3,4 s | 6,1 s | 1.360 ms | 0 |

O TBT do celular vem de duas tarefas: avaliar o three.js (~0,95 s com CPU 4×) e montar a cena (geração do mapa de
ambiente e shaders, ~0,96 s), ambas depois do carregamento e com a página já pintada. Sem o mapa de ambiente o TBT
caía para 510 ms, mas as cores da marca não se sustentavam. Com a GPU ligada no headless o resultado foi o mesmo
(48), então o custo é de CPU. Em produção o Apache comprimia o CSS (`DEFLATE`), o que o servidor de teste não faz;
**correção de 22/10/2026:** o JavaScript **não** era comprimido, porque o Apache o serve como `text/javascript` e a
diretiva só listava `application/javascript` (medido na produção pela auditoria de 22/10). Corrigido e testado no
contêiner local em [2026-10-22-landing-atualizada.md](2026-10-22-landing-atualizada.md#compressão).

## Não executado aqui

- Apache e Docker: o Docker Desktop desta máquina não subiu; a mudança do `apache-site.conf` (duas diretivas e um
  bloco `<Directory>` nos mesmos moldes dos existentes) não foi testada num Apache real.
- Publicação: nada foi enviado ao servidor.
- Leitor de tela e aparelho celular físico; Safari e Firefox.
- PHP 8.1 de verdade: a compatibilidade (nada além de PHP 8.0 no código) foi conferida por leitura, com `php -l` só no 8.5.
- Vídeo em tempo real: a gravação contínua pelo painel do navegador ficou instável (a página caiu para 4 quadros por
  segundo); a animação entregue foi gerada quadro a quadro da própria timeline, a 30 quadros por segundo.
