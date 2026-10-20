<?php

declare(strict_types=1);

/**
 * Template da landing. Recebe $config e $video de public/index.php; e() e asset() escapam/versionam o que sai.
 *
 * @var array<string, mixed> $config
 * @var array<string, mixed> $video
 */

$sistema = $config['sistema_url'];
$hero = $config['hero'];
$img = static fn (string $nome): string => e(asset('img/' . $nome));
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>Promotheus Fiscaliza · Central de fiscalização urbana de Marília</title>
  <meta name="description" content="Equipamentos, agentes e manutenção em uma central para acompanhar a fiscalização urbana de Marília/SP. Demonstração com dados fictícios para o DSIN Coder Challenge 2026.">
  <meta name="theme-color" content="#0f1a2b">
  <meta property="og:title" content="Promotheus Fiscaliza">
  <meta property="og:description" content="A cidade em movimento. A operação sob controle. Central de fiscalização urbana de Marília/SP (demonstração).">
  <meta property="og:type" content="website">
  <link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
  <link rel="preload" href="/landing-assets/promotheus/build/overpass-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="<?= e(construido('landing/src/landing.css')) ?>">
  <script src="<?= e(asset('boot.js')) ?>"></script>
  <script type="module" src="<?= e(construido('landing/src/pagina.js')) ?>"></script>
</head>
<body>
<svg class="sprite" aria-hidden="true" focusable="false">
  <!-- Marca oficial: public/assets/img/favicon.svg (mesmo desenho do símbolo i-brand da aplicação). -->
  <symbol id="marca" viewBox="0 0 32 32"><rect width="32" height="32" rx="8" fill="#2152E8"/><path fill="#fff" d="M7.5 7.5A1.5 1.5 0 0 1 9 6h15.5a1.5 1.5 0 0 1 1.5 1.5v3a1.5 1.5 0 0 1-1.5 1.5H13.5v3h5.5a1.5 1.5 0 0 1 1.5 1.5v3A1.5 1.5 0 0 1 19 20h-5.5v5a1.5 1.5 0 0 1-1.5 1.5H9A1.5 1.5 0 0 1 7.5 25z"/><path fill="none" stroke="#2152E8" stroke-width="1.4" stroke-linecap="round" d="M10.5 23.6v-1.8M10.5 18.6v-1.8M10.5 13.6v-1.8M15.2 9h1.8M20.2 9h1.8"/><circle cx="17.3" cy="17" r="1.5" fill="#FFB21A"/><path fill="none" stroke="#7CF5E1" stroke-width="2" stroke-linecap="round" d="M23.2 14.6a3.4 3.4 0 0 1 0 4.8M25.6 12.4a6.6 6.6 0 0 1 0 9.2"/></symbol>
  <symbol id="i-seta" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M5 12h13M13 7l5 5-5 5"/></symbol>
  <symbol id="i-desce" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M12 5v13M7 13l5 5 5-5"/></symbol>
  <symbol id="i-chave" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="10.5" width="14" height="10" rx="2"/><path d="M8.5 10.5V8a3.5 3.5 0 0 1 7 0v2.5M12 14.5v2"/></g></symbol>
  <symbol id="i-menu" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" d="M4 7h16M4 12h16M4 17h10"/></symbol>
  <symbol id="i-fechar" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></symbol>
  <symbol id="i-movimento" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M3 12h4l2-5 4 10 2-5h6"/></symbol>
  <symbol id="i-pular" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M6 6.5l7 5.5-7 5.5zM16.5 6.5v11"/></symbol>
  <symbol id="i-rever" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M20 11.5A8 8 0 0 0 6 6.6L4 8.5M4 4v4.5h4.5M4 12.5a8 8 0 0 0 14 4.9l2-1.9M20 20v-4.5h-4.5"/></symbol>
</svg>

<a class="pular-link" href="#conteudo">Pular para o conteúdo</a>

<header class="topo" data-topo>
  <div class="topo__barra">
    <a class="marca" href="#inicio">
      <svg class="marca__icone" aria-hidden="true" focusable="false"><use href="#marca"></use></svg>
      <span class="marca__nome"><span>Promotheus</span> <strong>Fiscaliza</strong></span>
    </a>
    <button class="menu-btn" type="button" aria-controls="navegacao" aria-expanded="false" data-menu-btn hidden>
      <svg class="icone" aria-hidden="true" focusable="false"><use href="#i-menu"></use></svg><span>Menu</span>
    </button>
    <nav class="nav" id="navegacao" aria-label="Seções da página">
      <ul class="nav__links">
        <li><a href="#sistema">O sistema</a></li>
        <li><a href="#recursos">Recursos</a></li>
        <li><a href="#avaliador">Para o avaliador</a></li>
      </ul>
      <div class="nav__acoes">
        <button class="movimento" type="button" aria-pressed="false" data-movimento hidden>
          <svg class="icone" aria-hidden="true" focusable="false"><use href="#i-movimento"></use></svg><span data-movimento-rotulo>Reduzir movimento</span>
        </button>
        <a class="btn btn--primario btn--compacto" href="<?= e($sistema) ?>">Acessar sistema</a>
      </div>
    </nav>
  </div>
</header>

<main id="conteudo">
  <section class="hero" id="inicio" aria-labelledby="hero-titulo">
    <div class="hero__asfalto" aria-hidden="true"></div>
    <div class="hero__via" aria-hidden="true"><div class="hero__pista" data-hero-pista></div></div>
    <div class="hero__grade">
      <div class="hero__texto">
        <p class="placa-sinal"><span>Central de fiscalização urbana</span><span class="placa-sinal__cidade">Marília · SP</span></p>
        <h1 id="hero-titulo"><span class="hero__contexto">A cidade em movimento.</span> <span>A operação sob controle.</span></h1>
        <p class="hero__apoio">Equipamentos, agentes e manutenção em uma central para acompanhar a fiscalização urbana e entender o que precisa de atenção.</p>
        <div class="hero__ctas">
          <a class="btn btn--primario" href="#sistema">Conhecer o sistema <svg class="icone icone--desce" aria-hidden="true" focusable="false"><use href="#i-desce"></use></svg></a>
          <a class="btn btn--contorno" href="<?= e($sistema) ?>">Acessar sistema <svg class="icone icone--seta" aria-hidden="true" focusable="false"><use href="#i-seta"></use></svg></a>
        </div>
        <p class="hero__acesso"><svg class="icone" aria-hidden="true" focusable="false"><use href="#i-chave"></use></svg>Acesso com conta fornecida pelo administrador.</p>
      </div>

      <div class="palco" data-palco data-montagem="<?= $hero['montagem'] ? '1' : '0' ?>" data-duracao="<?= e((float) $hero['duracao']) ?>" data-ponteiro="<?= $hero['ponteiro'] ? '1' : '0' ?>">
        <div class="palco__sombra" aria-hidden="true" data-palco-sombra></div>
        <div class="palco__cena" data-palco-cena>
          <div class="palco__estatico" data-palco-estatico>
            <svg viewBox="0 0 32 32" aria-hidden="true" focusable="false"><use href="#marca"></use></svg>
          </div>
        </div>
        <p class="palco__nome" aria-hidden="true" data-palco-nome><span>Promotheus</span> <strong>Fiscaliza</strong></p>
        <button class="palco__ctl" type="button" data-palco-ctl hidden>
          <svg class="icone" aria-hidden="true" focusable="false"><use href="#i-pular"></use></svg><span data-palco-ctl-rotulo>Pular animação</span>
        </button>
      </div>
    </div>
  </section>

  <section class="secao" id="sistema" aria-labelledby="sistema-titulo">
    <div class="secao__cabeca">
      <p class="rotulo">O sistema</p>
      <h2 id="sistema-titulo">Uma visão da operação, do mapa à manutenção.</h2>
      <p class="secao__lead">A visão geral de Marília reúne o que está em campo, o que mudou e o que pede atenção. Mapa e lista mostram o mesmo resultado, e a ficha de cada recurso abre ao lado, sem sair da tela.</p>
    </div>
    <figure class="vitrine" data-vitrine>
      <div class="vitrine__quadro" data-vitrine-quadro>
        <img src="<?= $img('sistema-visao-geral-1280.webp') ?>"
             srcset="<?= $img('sistema-visao-geral-800.webp') ?> 800w, <?= $img('sistema-visao-geral-1280.webp') ?> 1280w, <?= $img('sistema-visao-geral-1920.webp') ?> 1918w"
             sizes="(min-width: 1260px) 1180px, calc(100vw - 32px)" width="1918" height="884" loading="lazy" decoding="async"
             alt="Tela Visão geral de Marília no Promotheus Fiscaliza: menu lateral, resumos (34 de 43 equipamentos em serviço funcionando, 13 recursos que precisam de atenção, 4 manutenções urgentes), filtros, lista de recursos, mapa da cidade e a ficha aberta de um radar operacional na Avenida das Esmeraldas.">
        <span class="vitrine__ponto vitrine__ponto--1" aria-hidden="true">1</span>
        <span class="vitrine__ponto vitrine__ponto--2" aria-hidden="true">2</span>
        <span class="vitrine__ponto vitrine__ponto--3" aria-hidden="true">3</span>
        <span class="vitrine__ponto vitrine__ponto--4" aria-hidden="true">4</span>
      </div>
      <figcaption>
        <ol class="vitrine__legenda" data-vitrine-legenda>
          <li><strong>Resumos.</strong> Funcionando, precisam de atenção e manutenções urgentes, com as fórmulas a um toque.</li>
          <li><strong>Filtros.</strong> Tipo, situação e “precisa de atenção” valem ao mesmo tempo para a lista e o mapa.</li>
          <li><strong>Mapa.</strong> Instalado, planejado, base e última posição com formas diferentes; atualiza sozinho a cada 5 segundos.</li>
          <li><strong>Ficha.</strong> Situação, sinal, manutenção e cadastro do recurso, com o próximo passo à mão.</li>
        </ol>
        <p class="vitrine__fonte">Captura do sistema publicado em 19/10/2026, com dados fictícios. Mapa © colaboradores do OpenStreetMap.</p>
      </figcaption>
    </figure>
  </section>

  <section class="secao recursos" id="recursos" aria-labelledby="recursos-titulo">
    <div class="secao__cabeca">
      <p class="rotulo">Recursos</p>
      <h2 id="recursos-titulo">Do campo à próxima revisão.</h2>
      <p class="secao__lead">Três momentos da operação, na ordem em que acontecem no dia a dia da central.</p>
    </div>

    <div class="via" data-via>
      <div class="via__pista" aria-hidden="true"><div class="via__faixa" data-via-faixa></div></div>

      <article class="cena" data-cena>
        <span class="via__no" aria-hidden="true"></span>
        <div class="cena__texto">
          <p class="cena__marco" aria-hidden="true">01</p>
          <h3>Saiba o que está em campo.</h3>
          <p>Radares, câmeras, balanças, cancelas, totens, veículos e patinetes ficam no mesmo inventário, cada tipo com as características que importam — do limite de velocidade do radar à visão noturna da câmera. Agentes de trânsito, guarda e polícia aparecem com órgão, situação, base e última posição.</p>
          <ul class="cena__fatos">
            <li>Endereço, ponto no mapa e situação de cada equipamento</li>
            <li>Local instalado, planejado ou base de recurso móvel</li>
            <li>Histórico de cada mudança, com antes, depois e autoria</li>
          </ul>
        </div>
        <div class="cena__visual">
          <img class="cena__tela" src="<?= $img('inventario-720.webp') ?>" srcset="<?= $img('inventario-720.webp') ?> 720w, <?= $img('inventario-1200.webp') ?> 1200w" sizes="(min-width: 960px) 560px, calc(100vw - 64px)" width="1200" height="661" loading="lazy" decoding="async"
               alt="Tela Equipamentos: busca, filtros por tipo e situação e a lista de 48 equipamentos, com balanças e câmeras operacionais e uma câmera em manutenção.">
          <div class="cena__objeto" data-objeto="radar" aria-hidden="true"></div>
        </div>
      </article>

      <article class="cena cena--inversa" data-cena>
        <span class="via__no" aria-hidden="true"></span>
        <div class="cena__texto">
          <p class="cena__marco" aria-hidden="true">02</p>
          <h3>Encontre o que precisa de atenção.</h3>
          <p>A central junta mapa e lista com o mesmo filtro. Falhas, sinal atrasado e manutenções vencidas sobem para o topo, e a ficha mostra o alerta aberto, o histórico e o que fazer em seguida.</p>
          <ul class="cena__fatos">
            <li>Busca, tipo, situação, bairro e órgão no mesmo conjunto</li>
            <li>Manutenção imediata ou programada, uma intervenção por vez</li>
            <li>Só o que mudou ganha destaque a cada atualização</li>
          </ul>
        </div>
        <div class="cena__visual">
          <img class="cena__tela" src="<?= $img('atencao-720.webp') ?>" srcset="<?= $img('atencao-720.webp') ?> 720w, <?= $img('atencao-1200.webp') ?> 1200w" sizes="(min-width: 960px) 560px, calc(100vw - 64px)" width="1200" height="654" loading="lazy" decoding="async"
               alt="Central com a lista de recursos que precisam de atenção, o mapa do centro de Marília e a ficha de um radar em mau funcionamento, com o alerta aberto e o botão para registrar ciência.">
          <div class="cena__objeto" data-objeto="via" aria-hidden="true"></div>
        </div>
      </article>

      <article class="cena" data-cena>
        <span class="via__no" aria-hidden="true"></span>
        <div class="cena__texto">
          <p class="cena__marco" aria-hidden="true">03</p>
          <h3>Priorize a próxima revisão.</h3>
          <p>As revisões recomendadas para os próximos sete dias ordenam os equipamentos por uma prioridade de 0 a 100 e mostram os motivos: falhas recentes, falhas antigas e preventiva atrasada. É uma análise heurística e explicável — uma sugestão para planejar a rota, não uma previsão garantida.</p>
          <ul class="cena__fatos">
            <li>Cada recomendação diz por que e o que fazer</li>
            <li>Método e retrospecto abertos na própria tela</li>
            <li>Nada muda sozinho no sistema</li>
          </ul>
        </div>
        <div class="cena__visual">
          <img class="cena__tela" src="<?= $img('revisoes-720.webp') ?>" srcset="<?= $img('revisoes-720.webp') ?> 720w, <?= $img('revisoes-1200.webp') ?> 1200w" sizes="(min-width: 960px) 560px, calc(100vw - 64px)" width="1200" height="661" loading="lazy" decoding="async"
               alt="Tela Revisões recomendadas para os próximos 7 dias: explicação de como ler a prioridade e um radar a revisar primeiro, com prioridade 35 de 100, o motivo e a ação sugerida.">
          <div class="cena__objeto" data-objeto="placa" aria-hidden="true"></div>
        </div>
      </article>
    </div>
  </section>

  <section class="chamada" aria-labelledby="chamada-titulo">
    <div class="placa-final" data-placa>
      <div class="placa-final__face">
        <svg class="placa-final__seta" viewBox="0 0 48 48" aria-hidden="true" focusable="false"><path d="M8 24h28M25 12l12 12-12 12" fill="none" stroke="currentColor" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <div class="placa-final__texto">
          <h2 id="chamada-titulo">Conheça o Promotheus Fiscaliza.</h2>
          <p>Veja como a central organiza os recursos e ajuda a orientar a próxima ação.</p>
        </div>
        <div class="placa-final__acao">
          <a class="btn btn--placa" href="<?= e($sistema) ?>">Acessar sistema <svg class="icone icone--seta" aria-hidden="true" focusable="false"><use href="#i-seta"></use></svg></a>
          <p class="placa-final__nota"><svg class="icone" aria-hidden="true" focusable="false"><use href="#i-chave"></use></svg>O acesso pede uma conta fornecida pelo administrador.</p>
        </div>
      </div>
      <span class="placa-final__poste placa-final__poste--a" aria-hidden="true"></span>
      <span class="placa-final__poste placa-final__poste--b" aria-hidden="true"></span>
    </div>
  </section>

  <section class="avaliador" id="avaliador" aria-labelledby="avaliador-titulo">
    <div class="avaliador__conteudo">
      <h2 id="avaliador-titulo">Para o avaliador</h2>
      <p>Apresentação do projeto para o DSIN Coder Challenge.</p>
      <div class="video">
        <?php if ($video['ativo']): ?>
          <video controls playsinline preload="metadata" width="1280" height="720"<?= $video['capa'] ? ' poster="' . e($video['capa']) . '"' : '' ?>>
            <source src="<?= e($video['src']) ?>" type="<?= e($video['tipo']) ?>">
            <?php if ($video['legendas']): ?>
              <track kind="captions" src="<?= e($video['legendas']) ?>" srclang="pt-BR" label="Português (Brasil)" default>
            <?php endif ?>
            <p>Seu navegador não reproduz este vídeo. <a href="<?= e($video['src']) ?>">Baixe o arquivo</a>.</p>
          </video>
        <?php else: ?>
          <div class="video__vazio">
            <svg class="video__marca" viewBox="0 0 32 32" aria-hidden="true" focusable="false"><use href="#marca"></use></svg>
            <p>Vídeo de apresentação em breve.</p>
          </div>
        <?php endif ?>
      </div>
    </div>
  </section>
</main>

<footer class="rodape">
  <div class="rodape__conteudo">
    <p class="rodape__marca"><svg class="icone" aria-hidden="true" focusable="false"><use href="#marca"></use></svg>Promotheus Fiscaliza · DSIN Coder Challenge 2026, etapa F.O.G.O.</p>
    <p>Desenvolvido por <a href="<?= e($config['autor']['url']) ?>" rel="author"><?= e($config['autor']['nome']) ?></a>.</p>
    <p class="rodape__aviso">Projeto demonstrativo com dados fictícios. Sem integração com órgãos ou equipamentos reais.</p>
  </div>
</footer>
</body>
</html>
