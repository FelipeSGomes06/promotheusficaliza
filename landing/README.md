# Landing pública do Promotheus Fiscaliza

Página de entrada servida em `/` de `https://fiscaliza.devfelipegomes.com.br/`, no lugar do antigo "Portal simulado".
Apresenta o sistema, monta a marca em 3D na hero, mostra um plano 3D noturno depois do inventário e objetos 3D junto das
capturas, e leva ao acesso existente em `/promotheusfiscaliza/` — quem decide login e conta continua sendo a aplicação
([ADR-0014](../docs/adr/0014-acesso-somente-com-conta-cadastrada.md)).
Decisões de composição, materiais, coreografia e arquitetura: [ADR-0015](../docs/adr/0015-landing-publica-e-marca-3d.md);
seis pilares, foto de Marília, ponteiro e 3D sob demanda: [ADR-0018](../docs/adr/0018-landing-seis-pilares-e-foto-de-marilia.md);
planos 3D das cenas (e o diagnóstico dos objetos que eles substituem): [ADR-0019](../docs/adr/0019-landing-planos-3d-cinematograficos.md);
enfeites 3D junto das capturas: [ADR-0020](../docs/adr/0020-landing-enfeites-3d-junto-das-capturas.md).
Verificação e capturas: [evidência de 20/10/2026](../docs/evidencias/2026-10-20-landing-publica.md),
[atualização de 22/10/2026](../docs/evidencias/2026-10-22-landing-atualizada.md) e
[planos 3D, 23/10/2026](../docs/evidencias/2026-10-23-planos-3d.md) e
[enfeites 3D, 23/10/2026](../docs/evidencias/2026-10-23-enfeites-3d.md).

## Estrutura

```
landing/
  config.php          único ponto de ajuste: URL do sistema, URL pública, vídeo do avaliador, ajustes da hero
  pagina.php          template (HTML semântico, todo o texto da página)
  public/             document root de "/" (só isto é servido)
    index.php         cabeçalhos (CSP própria), escape, versão dos arquivos e renderização
    robots.txt
    landing-assets/promotheus/
      boot.js         script clássico mínimo, antes da pintura (classe .js e preferência de movimento)
      build/          CSS, JS e fontes gerados pelo Vite (versionados; produção não precisa de Node)
      img/            capturas reais do sistema (WebP, com recorte de celular), foto de Marília (Pedro Monteiro),
                      imagem de compartilhamento e favicon
      video/          vídeo do avaliador (apresentacao.mp4, capa.webp) e LEIA-ME.txt
  src/                fontes do CSS e do JS: pagina.js (entrada), palco.js + cena3d.js (marca 3D da hero),
                      planos.js + planos3d.js (plano 3D noturno, renderer próprio), enfeites.js + enfeites3d.js
                      (objetos 3D junto das capturas, renderer próprio), rolagem.js, navegacao.js, movimento.js,
                      ampliar.js
  vite.config.js      build da landing, separado do build do sistema
```

Requisitos de produção: **PHP 8.1 ou mais novo** (o código usa só recursos do PHP 8.0; a imagem do projeto roda 8.5),
Apache e arquivos estáticos. Sem banco, sessão, cookies, Node, Composer ou processo de build no servidor.

## Rodar localmente

```bash
php -S 127.0.0.1:8095 -t landing/public
```

Abra <http://127.0.0.1:8095/>. O botão "Acessar sistema" aponta para `/promotheusfiscaliza/`, que só existe com a
aplicação no ar (no Docker do projeto, `docker compose up -d --build` serve as duas: landing em
<http://localhost:8080/> e sistema em <http://localhost:8080/promotheusfiscaliza/>).

Mudar CSS ou JS (Node 20.19+): edite `landing/src/` e gere de novo os arquivos versionados.

```bash
npm ci
```

```bash
npm run build:landing
```

O PHP encontra os nomes com hash pelo `build/manifest.json`; não é preciso mexer no template.

## Configuração (`landing/config.php`)

| Chave | Padrão | Efeito |
|---|---|---|
| `sistema_url` | `/promotheusfiscaliza/` | destino dos três botões "Acessar sistema" |
| `url_publica` | `https://fiscaliza.devfelipegomes.com.br/` | `canonical`, `og:url` e endereço absoluto da imagem de compartilhamento |
| `avaliador.usuario`, `avaliador.senha` | `AvaliadorDsin` e a senha da conta de avaliação | cartão "Acesso para avaliação", abaixo do vídeo da seção "Para o avaliador" (texto público: trocar a senha na aplicação e aqui) |
| `video.src` | `/landing-assets/promotheus/video/apresentacao.mp4` | vídeo da seção "Para o avaliador"; `null` (ou arquivo ausente) mostra "Vídeo de apresentação em breve." |
| `video.tipo` | `video/mp4` | tipo do arquivo (`video/webm` para WebM) |
| `video.legendas` | `null` | arquivo WebVTT em português (faixa `captions`, ligada por padrão) |
| `video.capa` | `/landing-assets/promotheus/video/capa.webp` | imagem 16:9 mostrada antes do play (`null` = sem capa) |
| `hero.montagem` | `true` | `false` mostra a marca 3D já pronta, sem a sequência |
| `hero.duracao` | `4.0` | duração total da montagem, em segundos (as etapas mantêm a proporção) |
| `hero.ponteiro` | `true` | depois de montada, a marca gira um pouco e a foto de Marília se desloca até ±12 × ±8 px com o mouse (nunca no toque nem com movimento reduzido) |

Todo valor que vai para o HTML passa por `htmlspecialchars`.

## Trocar o vídeo do avaliador

1. Envie o arquivo para `landing/public/landing-assets/promotheus/video/` (ex.: `apresentacao.mp4`, H.264 + AAC;
   opcionalmente `apresentacao.pt-BR.vtt` e `capa.webp` em 16:9).
2. Em `landing/config.php`:

   ```php
   'video' => [
       'src' => '/landing-assets/promotheus/video/apresentacao.mp4',
       'tipo' => 'video/mp4',
       'legendas' => '/landing-assets/promotheus/video/apresentacao.pt-BR.vtt',
       'capa' => '/landing-assets/promotheus/video/capa.webp',
   ],
   ```

3. Recarregue a página. O player aparece com controles, `playsinline`, sem autoplay e com a legenda. Se o caminho
   estiver errado (arquivo ausente), a página continua mostrando "Vídeo de apresentação em breve." em vez de um
   player quebrado. Só arquivos deste site são aceitos (a CSP da landing tem `media-src 'self'`). Não é preciso
   reiniciar nada: a pasta do projeto é montada no contêiner.

## Publicação no servidor atual

**Onde a raiz é servida de verdade (conferido em 19–20/10/2026).** `fiscaliza.devfelipegomes.com.br` resolve para
143.95.175.67 (`*.unifiedlayer.com`, infraestrutura HostGator/Newfold), um servidor diferente da hospedagem
compartilhada do portal (`devfelipegomes.com.br`, 50.6.184.29). As respostas trazem `Via: 1.1 Caddy` e
`Server: Apache`: o Caddy faz o TLS e repassa tudo para o contêiner `app` deste projeto (`compose.yaml` padrão). O
"Portal simulado" da raiz é o arquivo `docker/php/portal-stub/index.html`, copiado para dentro da imagem e servido
pelo `DocumentRoot /var/www/portal-stub` de `docker/php/apache-site.conf` (`/index.php` responde 404 e a página tem
exatamente os 641 bytes desse arquivo). **Não há `public_html` envolvido**: copiar a landing para a hospedagem
compartilhada não mudaria nada nesse subdomínio.

A mudança, portanto, é só dentro do contêiner: `apache-site.conf` passa a usar `DocumentRoot /app/landing/public`
(a pasta do projeto já é montada em `/app`). O `Alias /promotheusfiscaliza`, a aplicação, o login, os assets do
sistema e o Caddy ficam como estão; não há regra que capture todas as rotas (um caminho desconhecido na raiz recebe
404 do Apache, como antes).

### Caminho 1 — Git (o mesmo fluxo de "Atualizar uma instalação publicada")

Na pasta do projeto no servidor, depois de esta branch entrar na branch publicada:

```bash
git pull
```

```bash
docker compose up -d --build
```

O `--build` é necessário uma vez, porque o `apache-site.conf` é copiado para a imagem. Banco, contas e simulador não
são tocados.

### Caminho 2 — Gerenciador de arquivos / SFTP

Use o pacote `dist/promotheus-landing-<data>.zip` (gerado como abaixo). Ele espelha os caminhos do projeto:

```
landing/config.php, landing/pagina.php, landing/README.md, landing/public/…   (a landing pronta, com o build)
docker/php/apache-site.conf                                                    (raiz → landing)
restaurar/apache-site.conf.anterior                                            (configuração anterior, para voltar)
```

1. Envie e extraia o conteúdo **na pasta do projeto no servidor** (a mesma que tem `compose.yaml`), mantendo as
   pastas. A pasta `restaurar/` pode ficar fora do projeto.
2. No terminal do servidor, na pasta do projeto: `docker compose up -d --build`.

Para gerar de novo a parte da landing do pacote a partir de um commit (o `.zip` fica em `dist/`, ignorado pelo Git;
a configuração anterior sai de `git show aa4a450:docker/php/apache-site.conf`):

```bash
git archive --format=zip -o dist/promotheus-landing.zip HEAD landing/config.php landing/pagina.php landing/README.md landing/public docker/php/apache-site.conf
```

### Conferir depois de publicar

```bash
curl -sI https://fiscaliza.devfelipegomes.com.br/
```

Esperado: `200`, `Content-Security-Policy: default-src 'none'; script-src 'self'; …` (a da landing) e nenhum
`Set-Cookie`. Depois:

- `curl -sI https://fiscaliza.devfelipegomes.com.br/promotheusfiscaliza/` → `302` para `/promotheusfiscaliza/entrar?voltar=…` (como antes);
- `curl -sI https://fiscaliza.devfelipegomes.com.br/landing-assets/promotheus/boot.js` → `200`;
- `curl -s -o /dev/null -D - -H "Accept-Encoding: gzip"` num dos `build/*.js` (nome no `manifest.json`) →
  `Content-Type: text/javascript`, `Content-Encoding: gzip` e `Vary: Accept-Encoding`;
- no navegador: a marca se monta, "Acessar sistema" abre o login e o console não mostra erros.

### Voltar à página anterior

A página antiga continua no repositório (`docker/php/portal-stub/index.html`) e dentro da imagem
(`/var/www/portal-stub`). Para voltar a ela:

- **Git:** `git checkout <commit-anterior> -- docker/php/apache-site.conf` (ou `git revert` do commit da landing) e
  `docker compose up -d --build`;
- **Arquivo:** troque `docker/php/apache-site.conf` por `restaurar/apache-site.conf.anterior` (ou só a linha
  `DocumentRoot /app/landing/public` por `DocumentRoot /var/www/portal-stub`) e rode `docker compose up -d --build`.

A pasta `landing/` pode ficar no servidor: com o `DocumentRoot` antigo ela não tem rota.

### Outra hospedagem (se um dia a landing sair do contêiner)

Qualquer Apache com PHP 8.1+ serve: aponte o document root do (sub)domínio para `landing/public` e mantenha
`config.php` e `pagina.php` um nível acima, fora do alcance da web. No cPanel isso é o "Document Root" do
subdomínio — confira-o no painel em vez de supor `public_html`. Ative compressão (gzip/brotli) para CSS e JS —
incluindo o tipo `text/javascript`, que é como o Apache serve `.js` (o contêiner já faz isso em `apache-site.conf`).

## Verificação rápida

```bash
php -l landing/public/index.php
```

Com o servidor local no ar: título e botões aparecem sem JavaScript; a marca em SVG e os cartazes dos planos ficam no
lugar quando não há WebGL (os enfeites somem); "Reduzir movimento" mostra a marca pronta, um quadro parado no plano e
nos enfeites e desliga as entradas; nada de erro no console.

## Referência do código

Cada função PHP e JavaScript da landing (palco, marca 3D, plano noturno, enfeites, rolagem, navegação, ampliação e
preferência de movimento): [referência — landing](../docs/referencia/codigo/landing/landing.md).
