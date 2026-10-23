<?php

declare(strict_types=1);

/**
 * Template da landing. Recebe $config e $video de public/index.php; e() e asset() escapam/versionam o que sai.
 *
 * @var array<string, mixed> $config
 * @var array<string, mixed> $video
 */

$sistema = $config['sistema_url'];
$publica = $config['url_publica'];
$hero = $config['hero'];
$img = static fn (string $nome): string => e(asset('img/' . $nome));

/**
 * Captura do sistema: link para a versão maior (ampliação; sem JavaScript, abre o arquivo), recorte próprio do
 * celular quando existe e as larguras do desktop. $maior = [largura, altura] da última largura listada.
 *
 * @param list<int> $larguras
 * @param array{0: int, 1: int} $maior
 * @param array{0: int, 1: int}|null $celular
 */
$captura = static function (string $nome, array $larguras, array $maior, string $sizes, string $alt, ?array $celular = null, string $classe = 'cena__tela') use ($img): string {
    $srcset = implode(', ', array_map(static fn (int $l): string => $img("{$nome}-{$l}.webp") . " {$l}w", $larguras));
    $fonte = $celular
        ? '<source media="(max-width: 719.98px)" srcset="' . $img("{$nome}-celular-{$celular[0]}.webp") . '" width="' . $celular[0] . '" height="' . $celular[1] . '">'
        : '';

    return '<a class="captura" href="' . $img($nome . '-' . end($larguras) . '.webp') . '" data-ampliar data-largura="' . $maior[0] . '" data-altura="' . $maior[1] . '">'
        . '<picture>' . $fonte
        . '<img class="' . e($classe) . '" src="' . $img("{$nome}-{$larguras[0]}.webp") . '" srcset="' . $srcset . '" sizes="' . e($sizes) . '"'
        . ' width="' . $maior[0] . '" height="' . $maior[1] . '" loading="lazy" decoding="async" alt="' . e($alt) . '">'
        . '</picture>'
        . '<span class="captura__ampliar"><svg class="icone" aria-hidden="true" focusable="false"><use href="#i-ampliar"></use></svg>Ampliar</span>'
        . '</a>';
};
$tamanhoCena = '(min-width: 960px) 560px, calc(100vw - 96px)';

/**
 * Plano 3D entre as cenas de recursos (planos.js e planos3d.js). Sem JavaScript ou sem WebGL fica o cartaz em CSS; o
 * canvas entra por cima dele. A legenda é texto de verdade; rótulo, tarjas e situação são decoração.
 */
$plano = static function (string $nome, string $marco, string $rotulo, string $legenda): string {
    $estado = $nome === 'manutencao' ? '<p class="plano__estado" aria-hidden="true" data-plano-estado></p>' : '';

    return '<figure class="plano plano--' . e($nome) . '" data-plano="' . e($nome) . '">'
        . '<div class="plano__tela" data-plano-tela>'
        . '<svg class="plano__cartaz" aria-hidden="true" focusable="false"><use href="#plano-cartaz"></use></svg>'
        . '<span class="plano__tarja plano__tarja--cima" aria-hidden="true"></span>'
        . '<span class="plano__tarja plano__tarja--baixo" aria-hidden="true"></span>'
        . '<p class="plano__rotulo" aria-hidden="true"><span>' . e($marco) . '</span>' . e($rotulo) . '</p>'
        . $estado
        . '</div>'
        . '<figcaption class="plano__legenda">' . e($legenda) . '</figcaption>'
        . '</figure>';
};
$dataCaptura = 'Captura da demonstração em 22/10/2026, com operação fictícia.';
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>Promotheus Fiscaliza · Operação urbana e planejamento em Marília</title>
  <meta name="description" content="Conheça a demonstração do Fiscaliza: equipamentos, agentes, manutenção, multas e planejamento de reforço em Marília com dados agregados do Infosiga-SP.">
  <meta name="theme-color" content="#0f1a2b">
  <link rel="canonical" href="<?= e($publica) ?>">
  <meta property="og:type" content="website">
  <meta property="og:locale" content="pt_BR">
  <meta property="og:url" content="<?= e($publica) ?>">
  <meta property="og:title" content="Promotheus Fiscaliza · Marília/SP">
  <meta property="og:description" content="Da operação ao planejamento: conheça os recursos, as prioridades explicadas e os limites da demonstração.">
  <meta property="og:image" content="<?= e(rtrim($publica, '/') . asset('img/compartilhar-1200x630.jpg')) ?>">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:image:alt" content="Marca do Promotheus Fiscaliza sobre a foto do terminal rodoviário de Marília/SP.">
  <meta name="twitter:card" content="summary_large_image">
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
  <symbol id="i-info" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="12" r="8.5"/><path d="M12 11v5.5M12 7.8v.2"/></g></symbol>
  <symbol id="i-ampliar" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="10.5" cy="10.5" r="6"/><path d="M15 15l5 5M10.5 8v5M8 10.5h5"/></g></symbol>
  <symbol id="i-menu" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" d="M4 7h16M4 12h16M4 17h10"/></symbol>
  <symbol id="i-fechar" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></symbol>
  <!-- Cartaz dos planos 3D (sem JavaScript, sem WebGL ou enquanto a cena carrega): a avenida à noite, em 2,39:1. -->
  <linearGradient id="cartaz-ceu" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#02040a"/><stop offset="1" stop-color="#121c2c"/></linearGradient>
  <linearGradient id="cartaz-pista" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#121820"/><stop offset="1" stop-color="#05070a"/></linearGradient>
  <linearGradient id="cartaz-facho" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#ffd9a8" stop-opacity="0.32"/><stop offset="1" stop-color="#ffd9a8" stop-opacity="0"/></linearGradient>
  <radialGradient id="cartaz-luz"><stop offset="0" stop-color="#ffe2bd"/><stop offset="0.25" stop-color="#ffc98a" stop-opacity="0.5"/><stop offset="1" stop-color="#ffc98a" stop-opacity="0"/></radialGradient>
  <radialGradient id="cartaz-ambar"><stop offset="0" stop-color="#ffb21a" stop-opacity="0.7"/><stop offset="1" stop-color="#ffb21a" stop-opacity="0"/></radialGradient>
  <symbol id="plano-cartaz" viewBox="0 0 239 100" preserveAspectRatio="xMidYMid slice">
    <rect width="239" height="48" fill="url(#cartaz-ceu)"/>
    <rect y="47" width="239" height="53" fill="#06080c"/>
    <g fill="#080b11"><rect x="0" y="18" width="34" height="31"/><rect x="36" y="29" width="22" height="20"/><rect x="60" y="34" width="20" height="15"/><rect x="82" y="38" width="16" height="11"/><rect x="141" y="37" width="15" height="12"/><rect x="158" y="31" width="22" height="18"/><rect x="182" y="22" width="26" height="27"/><rect x="210" y="10" width="29" height="39"/></g>
    <g fill="#ffc98a" opacity="0.55"><rect x="6" y="24" width="2" height="2"/><rect x="18" y="30" width="2" height="2"/><rect x="26" y="22" width="2" height="2"/><rect x="42" y="34" width="2" height="2"/><rect x="66" y="38" width="2" height="2"/><rect x="163" y="36" width="2" height="2"/><rect x="190" y="28" width="2" height="2"/><rect x="200" y="40" width="2" height="2"/><rect x="216" y="16" width="2" height="2"/><rect x="228" y="30" width="2" height="2"/></g>
    <polygon points="113,48 126,48 239,100 0,100" fill="url(#cartaz-pista)"/>
    <g stroke="#c9cfd8" stroke-width="0.6" fill="none" opacity="0.55"><path d="M114 48 6 100M125 48 233 100"/><path d="M117 48 76 100M122 48 163 100" stroke-dasharray="4 5"/></g>
    <polygon points="150,46 156,46 176,74 130,74" fill="url(#cartaz-facho)"/><polygon points="85,46 90,46 110,70 66,70" fill="url(#cartaz-facho)"/>
    <circle cx="153" cy="46" r="7" fill="url(#cartaz-luz)"/><circle cx="87.5" cy="46" r="6" fill="url(#cartaz-luz)"/><circle cx="133" cy="47" r="3" fill="url(#cartaz-luz)"/>
    <circle cx="186" cy="58" r="12" fill="url(#cartaz-ambar)"/><rect x="182" y="55" width="8" height="4" rx="0.6" fill="#ffb21a" opacity="0.9"/><rect x="185.4" y="59" width="1.2" height="15" fill="#1f2733"/>
    <path d="M192 55a4 4 0 0 1 0 5M194.5 53.5a6.5 6.5 0 0 1 0 8" fill="none" stroke="#7cf5e1" stroke-width="0.8" stroke-linecap="round" opacity="0.8"/>
  </symbol>
  <symbol id="i-movimento" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M3 12h4l2-5 4 10 2-5h6"/></symbol>
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
        <p class="hero__apoio">Reúna equipamentos, agentes, manutenção e multas. Planeje revisões e reforços em Marília com prioridades que mostram de onde vieram.</p>
        <div class="hero__ctas">
          <a class="btn btn--primario" href="#sistema">Conhecer o sistema <svg class="icone icone--desce" aria-hidden="true" focusable="false"><use href="#i-desce"></use></svg></a>
          <a class="btn btn--contorno" href="<?= e($sistema) ?>">Acessar sistema <svg class="icone icone--seta" aria-hidden="true" focusable="false"><use href="#i-seta"></use></svg></a>
        </div>
        <p class="hero__acesso"><svg class="icone" aria-hidden="true" focusable="false"><use href="#i-chave"></use></svg>Acesso com conta fornecida pelo administrador.</p>
        <p class="hero__acesso hero__nota"><svg class="icone" aria-hidden="true" focusable="false"><use href="#i-info"></use></svg><span>Demonstração com operação fictícia. Planejamento com <a href="#dados">dados reais agregados do Infosiga-SP</a>.</span></p>
      </div>

      <div class="palco" data-palco data-montagem="<?= $hero['montagem'] ? '1' : '0' ?>" data-duracao="<?= e((float) $hero['duracao']) ?>" data-ponteiro="<?= $hero['ponteiro'] ? '1' : '0' ?>">
        <div class="palco__foto" aria-hidden="true">
          <!-- Terminal rodoviário de Marília/SP: foto de Pedro Monteiro (@pvictormonteiro), só recortada, com as cores originais. -->
          <picture data-palco-foto>
            <source media="(max-width: 959.98px)" srcset="<?= $img('marilia-720.webp') ?>" width="720" height="360">
            <img src="<?= $img('marilia-1280.webp') ?>" width="1280" height="640" alt="" decoding="async" fetchpriority="high">
          </picture>
        </div>
        <div class="palco__sombra" aria-hidden="true" data-palco-sombra></div>
        <div class="palco__cena" data-palco-cena>
          <div class="palco__estatico" data-palco-estatico>
            <svg viewBox="0 0 32 32" aria-hidden="true" focusable="false"><use href="#marca"></use></svg>
          </div>
        </div>
        <p class="palco__nome" aria-hidden="true" data-palco-nome><span>Promotheus</span> <strong>Fiscaliza</strong></p>
      </div>
    </div>
  </section>

  <section class="secao" id="sistema" aria-labelledby="sistema-titulo">
    <div class="secao__cabeca">
      <p class="rotulo">O sistema</p>
      <h2 id="sistema-titulo">Veja o que está em campo e o que pede atenção.</h2>
      <p class="secao__lead">A visão geral reúne equipamentos e agentes no mapa de Marília. Mapa, lista e resumos respondem aos mesmos filtros; a ficha mostra a situação do recurso e a próxima ação sem sair da tela.</p>
    </div>
    <figure class="vitrine" data-vitrine>
      <div class="vitrine__quadro" data-vitrine-quadro>
        <?= $captura('sistema-visao-geral', [800, 1280, 1920], [1920, 1200], '(min-width: 1260px) 1180px, calc(100vw - 32px)',
            'Tela Visão geral de Marília no Promotheus Fiscaliza: menu com Visão geral, Equipamentos, Agentes, Multas, Manutenção, Revisões recomendadas e Planejar reforço; resumos (9 de 14 equipamentos em serviço funcionando, 9 recursos que precisam de atenção, 3 manutenções urgentes), filtros, a lista de recursos, o mapa do centro de Marília e a ficha aberta de um radar na Avenida Rio Branco com manutenção vencida.',
            [780, 1300], 'vitrine__tela') ?>
      </div>
      <figcaption>
        <ol class="vitrine__legenda" data-vitrine-legenda>
          <li><strong>Acompanhe.</strong> Funcionando, precisam de atenção e manutenções urgentes, com a explicação dos números.</li>
          <li><strong>Encontre.</strong> Combine busca, tipo, situação, bairro e órgão para chegar ao mesmo conjunto no mapa e na lista.</li>
          <li><strong>Entenda a posição.</strong> Local instalado, planejado, base e última posição recebida têm formas diferentes. Falta de sinal recente não significa falha.</li>
          <li><strong>Continue de onde estava.</strong> Os dados simulados são consultados a cada 5 segundos sem tirar sua seleção, deslocar o mapa ou roubar o foco.</li>
        </ol>
        <p class="vitrine__fonte"><?= e($dataCaptura) ?> Mapa © colaboradores do OpenStreetMap.</p>
      </figcaption>
    </figure>
  </section>

  <section class="secao recursos" id="recursos" aria-labelledby="recursos-titulo">
    <div class="secao__cabeca">
      <p class="rotulo">Recursos</p>
      <h2 id="recursos-titulo">Da operação ao próximo passo.</h2>
      <p class="secao__lead">Saiba o que está disponível, cuide dos recursos em campo e acompanhe cada registro com seu contexto.</p>
      <nav class="indice" aria-label="Recursos do sistema">
        <ol>
          <li><a href="#inventario">Inventário e agentes</a></li>
          <li><a href="#manutencao">Manutenção</a></li>
          <li><a href="#multas">Multas</a></li>
          <li><a href="#revisoes">Revisões</a></li>
          <li><a href="#planejamento">Planejar reforço</a></li>
        </ol>
      </nav>
    </div>

    <div class="via" data-via>
      <div class="via__pista" aria-hidden="true"><div class="via__faixa" data-via-faixa></div></div>

      <article class="cena" id="inventario" aria-labelledby="inventario-titulo" data-cena>
        <span class="via__no" aria-hidden="true"></span>
        <div class="cena__texto">
          <p class="cena__marco" aria-hidden="true">01</p>
          <h3 id="inventario-titulo">Cada recurso com a informação que importa.</h3>
          <p>Radares, câmeras, balanças, cancelas, totens, veículos e patinetes compartilham o inventário, com características próprias para cada tipo. Agentes de trânsito, guarda e polícia têm órgão, situação e base de trabalho separados da última posição recebida.</p>
          <ul class="cena__fatos">
            <li>Cadastro por etapas, endereço e ponto no mapa</li>
            <li>Local de instalação, local planejado ou base de recurso móvel</li>
            <li>Histórico das alterações com antes, depois, autoria e origem</li>
          </ul>
          <details class="detalhe">
            <summary>Mais sobre o cadastro</summary>
            <p>O tipo escolhido define os campos necessários, como funções e limite do radar ou recursos de imagem da câmera. A série é conferida por fabricante. A ficha reúne características, situação e histórico; arquivar um recurso o retira da operação sem apagar seus registros.</p>
          </details>
        </div>
        <figure class="cena__visual">
          <?= $captura('inventario', [720, 1200], [1200, 673], $tamanhoCena,
              'Tela Equipamentos: busca, filtros por tipo e situação e a lista de 16 equipamentos — balança, câmeras, cancela e patinetes com código, bairro, marca e modelo, cada um com sua situação, como operacional, furtado ou sem sinal recente.',
              [780, 1400]) ?>
          <?= $captura('agente', [480, 800], [800, 676], $tamanhoCena,
              'Ficha do agente de trânsito Hermes 01, fictício: órgão, situação em atividade, base de trabalho com endereço fixo e, separada dela, a última posição recebida pelo rádio simulado.',
              [780, 1200], 'cena__tela cena__tela--detalhe') ?>
          <figcaption class="cena__fonte"><?= e($dataCaptura) ?></figcaption>
        </figure>
      </article>

      <?= $plano('radar', '01', 'Em campo', 'Ilustração 3D: uma lombada eletrônica em serviço numa avenida à noite, um dos tipos de equipamento que o inventário acompanha.') ?>

      <article class="cena cena--inversa" id="manutencao" aria-labelledby="manutencao-titulo" data-cena>
        <span class="via__no" aria-hidden="true"></span>
        <div class="cena__texto">
          <p class="cena__marco" aria-hidden="true">02</p>
          <h3 id="manutencao-titulo">Cuide da manutenção do início à conclusão.</h3>
          <p>Abra uma intervenção imediata ou programe a próxima revisão. A ficha do equipamento orienta o que fazer, enquanto a manutenção registra o andamento e o resultado sem perder o histórico.</p>
          <ul class="cena__fatos">
            <li>Manutenção corretiva, programada e preventiva periódica</li>
            <li>Uma intervenção em execução por equipamento, com início e conclusão registrados</li>
            <li>Alertas e ordens urgentes ajudam a organizar a próxima ação</li>
          </ul>
          <details class="detalhe">
            <summary>Como o histórico é preservado</summary>
            <p>É possível registrar ciência de alertas e cancelar ordens com motivo. Falha e falta de sinal recente aparecem como coisas diferentes. Equipamentos com intervenção em execução precisam ter a ordem concluída antes do arquivamento. Alterações de cadastro e de situação mantêm autoria e origem, e o sistema avisa quando um cadastro mudou durante a edição.</p>
          </details>
        </div>
        <figure class="cena__visual">
          <?= $captura('manutencao', [720, 1200], [1200, 827], $tamanhoCena,
              'Tela Manutenção: ordens abertas da mais urgente para a mais distante, com abas Em andamento, Urgentes, Próximos 7 dias e Programadas depois; um veículo em execução com o botão para concluir a intervenção e dois radares urgentes com o motivo e o botão Iniciar intervenção.',
              [780, 1200]) ?>
          <figcaption class="cena__fonte"><?= e($dataCaptura) ?></figcaption>
        </figure>
      </article>

      <?= $plano('manutencao', '02', 'Intervenção', 'Ilustração 3D: uma intervenção em campo, da falha registrada à conclusão, com o equipamento de volta à operação.') ?>

      <article class="cena" id="multas" aria-labelledby="multas-titulo" data-cena>
        <span class="via__no" aria-hidden="true"></span>
        <div class="cena__texto">
          <p class="cena__marco" aria-hidden="true">03</p>
          <p class="selo">Registro demonstrativo</p>
          <h3 id="multas-titulo">Da ocorrência à conferência, cada registro tem contexto.</h3>
          <p>Salve a ocorrência como rascunho, informe o radar ou o agente de trânsito e complete os dados do veículo. A ficha mostra o que falta conferir e mantém separadas a documentação na ocorrência, as conferências posteriores e as informações de andamento.</p>
          <ul class="cena__fatos">
            <li>Origem ligada ao inventário, com um retrato preservado ao registrar</li>
            <li>Placa e RENAVAM do veículo autuado, sem confundir com as viaturas do sistema</li>
            <li>Etapa, pagamento informado e contestação acompanhados separadamente</li>
          </ul>
          <p class="nota"><svg class="icone" aria-hidden="true" focusable="false"><use href="#i-info"></use></svg><span>Os registros são fictícios e inseridos manualmente. O sistema não consulta Detran/Senatran, emite multas, cobra valores ou protocola recursos.</span></p>
          <details class="detalhe">
            <summary>O que também pode ser acompanhado</summary>
            <p>O código interno não substitui o número do AIT. Quando um identificador está ausente, o registro pode pedir uma justificativa. No excesso de velocidade, regulamentada e medida são informadas separadamente, e a considerada é calculada pelo sistema. Dados do medidor e referência da evidência ajudam a indicar o que precisa de conferência.</p>
            <p>Cada conferência documental informa resultado, fonte e data de referência. “Não verificada” é uma situação legítima; uma conferência posterior não muda o que foi anotado sobre a ocorrência. Corrigir a identidade do veículo ou a data pede nova conferência. O lembrete de conferir novamente é opcional e não representa prazo legal.</p>
            <p>Busca, filtros e três resumos mostram o mesmo conjunto de multas. Rascunhos podem ser completados, registros podem ser retificados com histórico e o cancelamento interno exige motivo. Formulários mostram os campos pertinentes ao caso, avisam sobre conflito de edição e evitam duplicar um envio repetido.</p>
          </details>
        </div>
        <figure class="cena__visual">
          <?= $captura('multa', [720, 1200], [1200, 745], $tamanhoCena,
              'Ficha de uma multa fictícia de conversão proibida: código interno separado do AIT, situação registrado, fase ainda não informada e documentos não conferidos; a próxima ação, conferir os documentos do carro; a origem da constatação por um agente de trânsito, gravada no registro; e o veículo autuado com placa, modelo e RENAVAM não disponível.',
              [780, 1040]) ?>
          <figcaption class="cena__fonte"><?= e($dataCaptura) ?> Placas, códigos e pessoas são fictícios.</figcaption>
        </figure>
      </article>

      <article class="cena cena--inversa" id="revisoes" aria-labelledby="revisoes-titulo" data-cena>
        <span class="via__no" aria-hidden="true"></span>
        <div class="cena__texto">
          <p class="cena__marco" aria-hidden="true">04</p>
          <h3 id="revisoes-titulo">Entenda o que revisar primeiro.</h3>
          <p>As revisões recomendadas para os próximos sete dias organizam os equipamentos por prioridade e mostram os motivos: falhas recentes, histórico de falhas e preventiva atrasada. Cada sugestão indica o que fazer em seguida.</p>
          <ul class="cena__fatos">
            <li>Prioridade explicada, com o motivo de cada recomendação</li>
            <li>Método e avaliação retrospectiva disponíveis para consulta</li>
            <li>A equipe decide a ação; a recomendação não altera o equipamento sozinha</li>
          </ul>
          <p class="nota"><svg class="icone" aria-hidden="true" focusable="false"><use href="#i-info"></use></svg><span>A pontuação é uma prioridade heurística sobre o histórico da demonstração. Não é probabilidade de falha nem garantia de previsão.</span></p>
        </div>
        <figure class="cena__visual">
          <?= $captura('revisoes', [720, 1200], [1200, 827], $tamanhoCena,
              'Tela Revisões recomendadas para os próximos 7 dias: aviso de como ler a prioridade, que não é a chance de falhar, e os equipamentos a revisar primeiro — uma cancela com prioridade 32 de 100 e um patinete com 27 de 100 —, cada um com os motivos e o que fazer.',
              [780, 785]) ?>
          <figcaption class="cena__fonte"><?= e($dataCaptura) ?></figcaption>
        </figure>
      </article>

      <?= $plano('revisoes', '04', 'Prioridade', 'Ilustração 3D: equipamentos lado a lado, cada um com a sua prioridade de revisão; a mais alta vem primeiro.') ?>
    </div>
  </section>

  <section class="planejar" id="planejamento" aria-labelledby="planejamento-titulo">
    <div class="planejar__conteudo">
      <div class="planejar__cabeca">
        <p class="rotulo rotulo--claro">Planejamento · dados reais agregados</p>
        <h2 id="planejamento-titulo">Veja onde concentrar o reforço da semana.</h2>
        <p class="planejar__lead">O planejamento usa o histórico agregado de sinistros registrados em Marília para comparar prioridades na área de estudo. O mapa de calor mostra as estimativas por quadrado de cerca de 2 × 2 km e ajuda a escolher de um a quatro lugares para concentrar a presença da equipe.</p>
      </div>
      <figure class="planejar__mapa">
        <?= $captura('planejar', [800, 1280, 1800], [1800, 1630], '(min-width: 1100px) 640px, calc(100vw - 32px)',
            'Tela Planejar reforço, passo Onde reforçar: semana de 20 a 26/10/2026, dados do Infosiga até 31/07/2026; o mapa de calor de Marília em quadrados de 2 km, do amarelo claro ao vermelho, com os quatro quadrados prioritários numerados, e ao lado a lista — Centro, Jardim Portal do Sol e Jardim Polyana com 3,8 sinistros esperados na semana, seguido de 1,7, 1,1 e 0,9.',
            [780, 1812], 'planejar__tela') ?>
        <figcaption>
          <p class="planejar__cor"><strong>Cor = estimativa de registros de sinistros na semana por quadrado.</strong> Não indica uma emergência em andamento. Agentes, posições e missões são fictícios.</p>
          <p class="planejar__fonte">Fonte: Infosiga-SP / Detran-SP, CC BY 4.0. Agregação e método pelo projeto Promotheus Fiscaliza; período conforme a execução mostrada na captura. <?= e($dataCaptura) ?> Mapa © colaboradores do OpenStreetMap.</p>
        </figcaption>
      </figure>
      <div class="planejar__passos">
        <ol class="passos">
          <li><strong>Escolha onde.</strong> Compare o mapa, os lugares priorizados e a explicação do histórico.</li>
          <li><strong>Veja quem pode ir.</strong> Consulte a sugestão de agente de trânsito disponível, com a posição ou a base usada como referência.</li>
          <li><strong>Confirme a missão.</strong> O gestor decide e confirma uma missão fictícia; concluir ou cancelar libera o agente para outra.</li>
        </ol>
        <figure class="planejar__passo2">
          <?= $captura('planejar-passo2', [720, 1200], [1200, 633], '(min-width: 1100px) 480px, calc(100vw - 32px)',
              'Passos 2 e 3 do planejamento: o agente de trânsito Hermes 01, sugerido por estar dentro do quadrado, e Hermes 06 a 1,8 km em linha reta contando da base; depois, a confirmação da missão fictícia de demonstração, que não envia mensagem nem aciona ninguém.',
              null, 'planejar__tela') ?>
        </figure>
        <ul class="planejar__fatos">
          <li>Retrospecto compara previsões registradas antes do resultado.</li>
          <li>Escolher outro agente pede uma justificativa.</li>
          <li>Dados de origem, período de referência e limites do método ficam visíveis.</li>
        </ul>
        <details class="detalhe detalhe--escuro">
          <summary>Como interpretar essas prioridades</summary>
          <p>O método apresentado usa a referência histórica. Os setores são divisões experimentais da área de estudo, não limites oficiais de bairros ou cobertura de todo o município. A sugestão de agente considera sua disponibilidade e uma distância em linha reta; não calcula rota nem tempo de chegada. Uma missão depende de confirmação e mantém o agente reservado até ser concluída ou cancelada.</p>
          <p>Os dados de sinistros têm atraso de publicação e o retrospecto tem limitações. A estimativa não prova que deslocar agentes evita acidentes. O projeto também oferece uma rotina agendável para atualização mensal da base e cálculo semanal; a frequência efetiva depende da configuração da instalação.</p>
        </details>
      </div>
    </div>
  </section>

  <section class="secao dados" id="dados" aria-labelledby="dados-titulo">
    <div class="dados__conteudo">
      <div>
        <p class="rotulo">Dados e acesso</p>
        <h2 id="dados-titulo">Saiba o que é real e o que é demonstração.</h2>
        <p class="secao__lead">Equipamentos, agentes, posições, missões e registros de multas fazem parte de uma operação fictícia. No planejamento, a camada real é o histórico de contagens agregadas do Infosiga-SP. As recomendações ajudam a comparar prioridades; a decisão continua com quem opera.</p>
      </div>
      <div class="perguntas">
        <details class="detalhe">
          <summary>Posso acessar sem conta?</summary>
          <p>O sistema exige uma conta cadastrada pelo administrador. Não há cadastro aberto. As imagens desta página apresentam os recursos antes do acesso.</p>
        </details>
        <details class="detalhe">
          <summary>O Fiscaliza está ligado a órgãos ou equipamentos reais?</summary>
          <p>Não. A operação é demonstrativa. Não há integração com prefeitura, polícia, radares ou serviços de emissão e cobrança de multas. A origem real usada no planejamento é a base agregada de sinistros indicada acima.</p>
        </details>
        <details class="detalhe">
          <summary>Como posso acompanhar as mudanças?</summary>
          <p>As fichas registram o histórico de alterações, com autoria e contexto. Conferências documentais e missões têm seus próprios registros; o sistema avisa sobre conflitos de edição e preserva os dados informados para revisão.</p>
        </details>
      </div>
    </div>
  </section>

  <section class="chamada" aria-labelledby="chamada-titulo">
    <div class="placa-final" data-placa>
      <div class="placa-final__face">
        <svg class="placa-final__seta" viewBox="0 0 48 48" aria-hidden="true" focusable="false"><path d="M8 24h28M25 12l12 12-12 12" fill="none" stroke="currentColor" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <div class="placa-final__texto">
          <h2 id="chamada-titulo">Conheça a operação por completo.</h2>
          <p>Equipamentos, agentes, manutenção, multas e planejamento, com contexto para decidir o próximo passo.</p>
        </div>
        <div class="placa-final__acao">
          <a class="btn btn--placa" href="<?= e($sistema) ?>">Acessar sistema <svg class="icone icone--seta" aria-hidden="true" focusable="false"><use href="#i-seta"></use></svg></a>
          <p class="placa-final__nota"><svg class="icone" aria-hidden="true" focusable="false"><use href="#i-chave"></use></svg>Acesso com conta fornecida pelo administrador.</p>
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
    <p class="rodape__aviso">Projeto demonstrativo: equipamentos, agentes, posições, missões e multas são fictícios. O planejamento usa contagens reais agregadas de sinistros. Fonte: Infosiga-SP / Detran-SP, CC BY 4.0. Sem integração com órgãos ou equipamentos reais.</p>
    <p class="rodape__aviso">Foto de Marília na abertura: Pedro Monteiro (@pvictormonteiro); recortada. Mapas das capturas © colaboradores do OpenStreetMap.</p>
  </div>
</footer>

<dialog class="ampliacao" data-ampliacao aria-labelledby="ampliacao-titulo">
  <div class="ampliacao__barra">
    <p class="ampliacao__titulo" id="ampliacao-titulo">Captura ampliada</p>
    <button class="ampliacao__fechar" type="button" data-ampliacao-fechar autofocus><svg class="icone" aria-hidden="true" focusable="false"><use href="#i-fechar"></use></svg>Fechar</button>
  </div>
  <div class="ampliacao__area" tabindex="0" role="region" aria-label="Imagem ampliada; role para ver as partes que não cabem">
    <img data-ampliacao-imagem alt="">
  </div>
  <p class="ampliacao__legenda" data-ampliacao-legenda></p>
</dialog>
</body>
</html>
