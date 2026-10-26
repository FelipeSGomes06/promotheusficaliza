# Catálogo de símbolos

<!-- Gerado por scripts/documentacao/inventario.mjs --catalogo. Não edite à mão: rode o comando de novo. -->

Índice de **todas as classes, funções e métodos nomeados** do código próprio (PHP, JavaScript, Python e scripts),
com a assinatura real e o link para a página de referência que explica o contrato. Funções anônimas não têm
linha própria: são explicadas no fluxo que as contém. Bibliotecas de terceiros e saídas de build ficam fora
(ver [cobertura](../cobertura-documentacao.md#pastas-geradas-de-terceiros-e-excluídas)).

**1867 símbolos em 183 arquivos; 1867 encontrados na página de referência do arquivo (todos).**

Coluna **Ref.**: ✓ = o nome qualificado aparece na página indicada (título, tabela ou texto); ✗ = pendente.
Linha = posição no arquivo quando o catálogo foi gerado.

## landing/public

### index.php

Fonte: [`landing/public/index.php`](../../landing/public/index.php) · Referência: [landing](codigo/landing/landing.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`e`](codigo/landing/landing.md) | function | função | `e(mixed $valor): string` | 11 | ✓ |
| [`asset`](codigo/landing/landing.md) | function | função | `asset(string $caminho): string` | 17 | ✓ |
| [`construido`](codigo/landing/landing.md) | function | função | `construido(string $fonte): string` | 25 | ✓ |

## landing/src

### ampliar.js

Fonte: [`landing/src/ampliar.js`](../../landing/src/ampliar.js) · Referência: [landing](codigo/landing/landing.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`iniciarAmpliacao`](codigo/landing/landing.md) | function | export | `iniciarAmpliacao()` | 6 | ✓ |

### cena3d.js

Fonte: [`landing/src/cena3d.js`](../../landing/src/cena3d.js) · Referência: [landing](codigo/landing/landing.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`svgParaCena`](codigo/landing/landing.md) | function | local | `svgParaCena(x, y)` | 41 | ✓ |
| [`formaDePontos`](codigo/landing/landing.md) | function | local | `formaDePontos(pontos)` | 43 | ✓ |
| [`formaSvg`](codigo/landing/landing.md) | function | local | `formaSvg(d, divisoes)` | 53 | ✓ |
| [`extrudar`](codigo/landing/landing.md) | function | local | `extrudar(forma, prof, chanfro, segmentos)` | 59 | ✓ |
| [`capsula`](codigo/landing/landing.md) | function | local | `capsula(comprimento, raio)` | 69 | ✓ |
| [`arco`](codigo/landing/landing.md) | function | local | `arco(raio, meio, espessura)` | 80 | ✓ |
| [`materiais`](codigo/landing/landing.md) | function | local | `materiais()` | 90 | ✓ |
| [`luzes`](codigo/landing/landing.md) | function | local | `luzes(cena, celular)` | 100 | ✓ |
| [`criarCena`](codigo/landing/landing.md) | function | export | `criarCena({ host, reduzido, celular, duracao, ponteiro, nome, sombra, aoEstado })` | 115 | ✓ |
| [`criarCena.pontoNoPercurso`](codigo/landing/landing.md) | function | local | `pontoNoPercurso(s, alvoV)` | 225 | ✓ |
| [`criarCena.aplicar`](codigo/landing/landing.md) | function | local | `aplicar()` | 234 | ✓ |
| [`criarCena.impacto`](codigo/landing/landing.md) | function | local | `impacto(linha, t, eixo, peca, escala, compressao)` | 278 | ✓ |
| [`criarCena.criarTimeline`](codigo/landing/landing.md) | function | local | `criarTimeline()` | 291 | ✓ |
| [`criarCena.criarTimeline.onComplete`](codigo/landing/landing.md) | method | local | `onComplete()` | 306 | ✓ |
| [`criarCena.desenhar`](codigo/landing/landing.md) | function | local | `desenhar()` | 361 | ✓ |
| [`criarCena.invalidar`](codigo/landing/landing.md) | function | local | `invalidar()` | 366 | ✓ |
| [`criarCena.tique`](codigo/landing/landing.md) | function | local | `tique()` | 369 | ✓ |
| [`criarCena.redimensionar`](codigo/landing/landing.md) | function | local | `redimensionar()` | 378 | ✓ |
| [`criarCena.repousar`](codigo/landing/landing.md) | function | local | `repousar()` | 403 | ✓ |
| [`criarCena.iniciar`](codigo/landing/landing.md) | method | local | `iniciar(aoComecar)` | 415 | ✓ |
| [`criarCena.definirReduzido`](codigo/landing/landing.md) | method | local | `definirReduzido(valor)` | 425 | ✓ |
| [`criarCena.ligarPonteiro`](codigo/landing/landing.md) | method | local | `ligarPonteiro()` | 433 | ✓ |
| [`criarCena.apontar`](codigo/landing/landing.md) | method | local | `apontar(x, y)` | 437 | ✓ |

### enfeites.js

Fonte: [`landing/src/enfeites.js`](../../landing/src/enfeites.js) · Referência: [landing](codigo/landing/landing.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`iniciarEnfeites`](codigo/landing/landing.md) | function | export | `iniciarEnfeites()` | 17 | ✓ |
| [`iniciarEnfeites.entrar`](codigo/landing/landing.md) | function | local | `entrar(item)` | 51 | ✓ |
| [`iniciarEnfeites.tique`](codigo/landing/landing.md) | function | local | `tique(_, dt)` | 68 | ✓ |
| [`iniciarEnfeites.carregar`](codigo/landing/landing.md) | function | local | `carregar()` | 128 | ✓ |
| [`quandoOcioso`](codigo/landing/landing.md) | function | local | `quandoOcioso(f)` | 163 | ✓ |
| [`quandoOcioso.agendar`](codigo/landing/landing.md) | function | local | `agendar()` | 164 | ✓ |

### enfeites3d.js

Fonte: [`landing/src/enfeites3d.js`](../../landing/src/enfeites3d.js) · Referência: [landing](codigo/landing/landing.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`suave`](codigo/landing/landing.md) | function | local | `suave(a, b, x)` | 24 | ✓ |
| [`faixa`](codigo/landing/landing.md) | function | local | `faixa(a, b, x)` | 28 | ✓ |
| [`quique`](codigo/landing/landing.md) | function | local | `quique(x)` | 30 | ✓ |
| [`passa`](codigo/landing/landing.md) | function | local | `passa(x)` | 36 | ✓ |
| [`imagem`](codigo/landing/landing.md) | function | local | `imagem(w, h, desenhar)` | 41 | ✓ |
| [`textura`](codigo/landing/landing.md) | function | local | `textura(canvas, cor = true)` | 48 | ✓ |
| [`retangulo`](codigo/landing/landing.md) | function | local | `retangulo(w, h, r, x0 = -w / 2, y0 = -h / 2)` | 54 | ✓ |
| [`chapa`](codigo/landing/landing.md) | function | local | `chapa(forma, prof, chanfro = 0.02)` | 67 | ✓ |
| [`fosco`](codigo/landing/landing.md) | function | local | `fosco(cor, rugosidade = 0.55, metal = 0)` | 73 | ✓ |
| [`mancha`](codigo/landing/landing.md) | function | local | `mancha()` | 77 | ✓ |
| [`sombra`](codigo/landing/landing.md) | function | local | `sombra(w, d, forca = 0.32)` | 94 | ✓ |
| [`halo`](codigo/landing/landing.md) | function | local | `halo(cor, tamanho)` | 102 | ✓ |
| [`mapa`](codigo/landing/landing.md) | function | local | `mapa()` | 112 | ✓ |
| [`mapa.atualizar`](codigo/landing/landing.md) | method | local | `atualizar({ entrada, t, giro, x, y, parado })` | 185 | ✓ |
| [`camera`](codigo/landing/landing.md) | function | local | `camera()` | 203 | ✓ |
| [`camera.atualizar`](codigo/landing/landing.md) | method | local | `atualizar({ entrada, t, giro, x, y, parado, ponteiro })` | 239 | ✓ |
| [`cone`](codigo/landing/landing.md) | function | local | `cone()` | 263 | ✓ |
| [`cone.faixaCone`](codigo/landing/landing.md) | function | local | `faixaCone(y0, y1)` | 270 | ✓ |
| [`cone.faixaCone.r`](codigo/landing/landing.md) | function | local | `r(y)` | 271 | ✓ |
| [`cone.atualizar`](codigo/landing/landing.md) | method | local | `atualizar({ entrada, t, giro, x, y, parado })` | 291 | ✓ |
| [`placa`](codigo/landing/landing.md) | function | local | `placa()` | 310 | ✓ |
| [`placa.desenhar`](codigo/landing/landing.md) | function | local | `desenhar()` | 316 | ✓ |
| [`placa.atualizar`](codigo/landing/landing.md) | method | local | `atualizar({ entrada, t, giro, x, y, parado })` | 378 | ✓ |
| [`semaforo`](codigo/landing/landing.md) | function | local | `semaforo()` | 394 | ✓ |
| [`semaforo.qualAcesa`](codigo/landing/landing.md) | function | local | `qualAcesa(t)` | 418 | ✓ |
| [`semaforo.atualizar`](codigo/landing/landing.md) | method | local | `atualizar({ entrada, t, giro, x, y, parado })` | 425 | ✓ |
| [`calor`](codigo/landing/landing.md) | function | local | `calor()` | 443 | ✓ |
| [`calor.corDe`](codigo/landing/landing.md) | function | local | `corDe(v)` | 455 | ✓ |
| [`calor.atualizar`](codigo/landing/landing.md) | method | local | `atualizar({ entrada, t, giro, x, y, parado })` | 502 | ✓ |
| [`criarEnfeites`](codigo/landing/landing.md) | function | export | `criarEnfeites({ lado })` | 529 | ✓ |
| [`criarEnfeites.preparar`](codigo/landing/landing.md) | method | local | `preparar()` | 582 | ✓ |
| [`criarEnfeites.desenhar`](codigo/landing/landing.md) | method | local | `desenhar(tipo, estado, destino)` | 590 | ✓ |
| [`criarEnfeites.aoPerder`](codigo/landing/landing.md) | method | local | `aoPerder(f)` | 604 | ✓ |

### movimento.js

Fonte: [`landing/src/movimento.js`](../../landing/src/movimento.js) · Referência: [landing](codigo/landing/landing.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`avisar`](codigo/landing/landing.md) | function | local | `avisar()` | 10 | ✓ |
| [`movimento`](codigo/landing/landing.md) | object | export | `movimento = { … }` | 12 | ✓ |
| [`movimento.reduzido`](codigo/landing/landing.md) | method | local | `reduzido()` | 13 | ✓ |
| [`movimento.porEscolha`](codigo/landing/landing.md) | method | local | `porEscolha()` | 14 | ✓ |
| [`movimento.alternar`](codigo/landing/landing.md) | method | local | `alternar()` | 15 | ✓ |
| [`movimento.aoMudar`](codigo/landing/landing.md) | method | local | `aoMudar(ouvinte)` | 25 | ✓ |

### navegacao.js

Fonte: [`landing/src/navegacao.js`](../../landing/src/navegacao.js) · Referência: [landing](codigo/landing/landing.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`iniciarNavegacao`](codigo/landing/landing.md) | function | export | `iniciarNavegacao()` | 7 | ✓ |
| [`iniciarNavegacao.abrir`](codigo/landing/landing.md) | function | local | `abrir(aberto, devolverFoco = false)` | 12 | ✓ |
| [`iniciarNavegacao.sincronizar`](codigo/landing/landing.md) | function | local | `sincronizar()` | 31 | ✓ |

### palco.js

Fonte: [`landing/src/palco.js`](../../landing/src/palco.js) · Referência: [landing](codigo/landing/landing.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`iniciarPalco`](codigo/landing/landing.md) | function | export | `iniciarPalco(raiz)` | 16 | ✓ |
| [`iniciarPalco.ativo`](codigo/landing/landing.md) | function | local | `ativo()` | 32 | ✓ |
| [`iniciarPalco.repousar`](codigo/landing/landing.md) | function | local | `repousar(imediato = false)` | 33 | ✓ |
| [`iniciarPalco.limitar`](codigo/landing/landing.md) | function | local | `limitar(v)` | 40 | ✓ |
| [`iniciarPalco.esquecerCaixa`](codigo/landing/landing.md) | function | local | `esquecerCaixa()` | 42 | ✓ |
| [`iniciarPalco.aoEstado`](codigo/landing/landing.md) | function | local | `aoEstado(estado)` | 60 | ✓ |
| [`iniciarPalco.carregar`](codigo/landing/landing.md) | function | local | `carregar()` | 87 | ✓ |
| [`iniciarPalco.comecarVigia`](codigo/landing/landing.md) | function | local | `comecarVigia()` | 120 | ✓ |
| [`depoisDaRolagemInicial`](codigo/landing/landing.md) | function | local | `depoisDaRolagemInicial(f)` | 135 | ✓ |
| [`depoisDaRolagemInicial.uma`](codigo/landing/landing.md) | function | local | `uma()` | 137 | ✓ |
| [`depoisDaRolagemInicial.esperar`](codigo/landing/landing.md) | function | local | `esperar()` | 142 | ✓ |
| [`quandoOcioso`](codigo/landing/landing.md) | function | local | `quandoOcioso(f)` | 150 | ✓ |
| [`quandoOcioso.agendar`](codigo/landing/landing.md) | function | local | `agendar()` | 151 | ✓ |

### planos.js

Fonte: [`landing/src/planos.js`](../../landing/src/planos.js) · Referência: [landing](codigo/landing/landing.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`iniciarPlanos`](codigo/landing/landing.md) | function | export | `iniciarPlanos()` | 18 | ✓ |
| [`iniciarPlanos.onUpdate`](codigo/landing/landing.md) | method | local | `onUpdate(st)` | 46 | ✓ |
| [`iniciarPlanos.onRefresh`](codigo/landing/landing.md) | method | local | `onRefresh(st)` | 49 | ✓ |
| [`iniciarPlanos.escolher`](codigo/landing/landing.md) | function | local | `escolher()` | 64 | ✓ |
| [`iniciarPlanos.dimensionar`](codigo/landing/landing.md) | function | local | `dimensionar()` | 70 | ✓ |
| [`iniciarPlanos.desenhar`](codigo/landing/landing.md) | function | local | `desenhar(plano)` | 75 | ✓ |
| [`iniciarPlanos.congelar`](codigo/landing/landing.md) | function | local | `congelar(plano)` | 80 | ✓ |
| [`iniciarPlanos.preto`](codigo/landing/landing.md) | function | local | `preto(plano)` | 96 | ✓ |
| [`iniciarPlanos.ativar`](codigo/landing/landing.md) | function | local | `ativar(plano)` | 108 | ✓ |
| [`iniciarPlanos.tique`](codigo/landing/landing.md) | function | local | `tique(_, dt)` | 123 | ✓ |
| [`iniciarPlanos.carregar`](codigo/landing/landing.md) | function | local | `carregar()` | 173 | ✓ |
| [`quandoOcioso`](codigo/landing/landing.md) | function | local | `quandoOcioso(f)` | 219 | ✓ |
| [`quandoOcioso.agendar`](codigo/landing/landing.md) | function | local | `agendar()` | 220 | ✓ |

### planos3d.js

Fonte: [`landing/src/planos3d.js`](../../landing/src/planos3d.js) · Referência: [landing](codigo/landing/landing.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`aleatorio`](codigo/landing/landing.md) | function | local | `aleatorio(semente)` | 41 | ✓ |
| [`suave`](codigo/landing/landing.md) | function | local | `suave(a, b, x)` | 50 | ✓ |
| [`linear`](codigo/landing/landing.md) | function | local | `linear(hex, k = 1)` | 54 | ✓ |
| [`imagem`](codigo/landing/landing.md) | function | local | `imagem(w, h, desenhar)` | 56 | ✓ |
| [`pixels`](codigo/landing/landing.md) | function | local | `pixels(w, h, f)` | 64 | ✓ |
| [`textura`](codigo/landing/landing.md) | function | local | `textura(canvas, { cor = true, repetir = null } = {})` | 76 | ✓ |
| [`ruido`](codigo/landing/landing.md) | function | local | `ruido(tam, oitavas, semente)` | 88 | ✓ |
| [`ruido.em`](codigo/landing/landing.md) | function | local | `em(x, y)` | 95 | ✓ |
| [`pintar`](codigo/landing/landing.md) | function | local | `pintar(geo, cor)` | 115 | ✓ |
| [`juntar`](codigo/landing/landing.md) | function | local | `juntar(lista)` | 125 | ✓ |
| [`caixa`](codigo/landing/landing.md) | function | local | `caixa(w, h, d, x = 0, y = 0, z = 0)` | 126 | ✓ |
| [`cilindro`](codigo/landing/landing.md) | function | local | `cilindro(rt, rb, h, seg, x = 0, y = 0, z = 0)` | 127 | ✓ |
| [`perfil`](codigo/landing/landing.md) | function | local | `perfil(pontos)` | 128 | ✓ |
| [`extrusao`](codigo/landing/landing.md) | function | local | `extrusao(forma, largura, chanfro = 0.05, curvas = 6)` | 132 | ✓ |
| [`retangulo`](codigo/landing/landing.md) | function | local | `retangulo(w, h, r)` | 141 | ✓ |
| [`arco`](codigo/landing/landing.md) | function | local | `arco(raio, meio, espessura)` | 157 | ✓ |
| [`quad`](codigo/landing/landing.md) | function | local | `quad(a0, a1, b1, b0, cor)` | 167 | ✓ |
| [`texturas`](codigo/landing/landing.md) | function | local | `texturas()` | 178 | ✓ |
| [`desenharPainel`](codigo/landing/landing.md) | function | local | `desenharPainel(g, valor)` | 242 | ✓ |
| [`materialFacho`](codigo/landing/landing.md) | function | local | `materialFacho(cor, intensidade)` | 285 | ✓ |
| [`criarMundo`](codigo/landing/landing.md) | function | export | `criarMundo({ celular })` | 356 | ✓ |
| [`criarMundo.livre`](codigo/landing/landing.md) | function | local | `livre(lado, z)` | 522 | ✓ |
| [`criarMundo.lampadaPerto`](codigo/landing/landing.md) | function | local | `lampadaPerto(lado, z)` | 543 | ✓ |
| [`criarMundo.refletir`](codigo/landing/landing.md) | function | local | `refletir(cam)` | 565 | ✓ |
| [`criarMundo.luzesVeiculo`](codigo/landing/landing.md) | function | local | `luzesVeiculo(meio, alturaFarol, alturaLanterna, larg, extra = [])` | 624 | ✓ |
| [`criarMundo.rastros`](codigo/landing/landing.md) | function | local | `rastros(meio, alturaFarol, alturaLanterna, larg, compRastro, compFacho)` | 640 | ✓ |
| [`criarMundo.instanciar`](codigo/landing/landing.md) | function | local | `instanciar(geo, mat, lista, sombra)` | 677 | ✓ |
| [`criarMundo.zDe`](codigo/landing/landing.md) | function | local | `zDe(v, t)` | 689 | ✓ |
| [`criarMundo.posicionarTransito`](codigo/landing/landing.md) | function | local | `posicionarTransito(t)` | 693 | ✓ |
| [`criarMundo.lombada`](codigo/landing/landing.md) | function | local | `lombada(escala = 1, comPlaca = true)` | 710 | ✓ |
| [`criarMundo.lombada.desenharPlaca`](codigo/landing/landing.md) | function | local | `desenharPlaca()` | 744 | ✓ |
| [`criarMundo.caminho`](codigo/landing/landing.md) | function | local | `caminho(pontos)` | 795 | ✓ |
| [`criarMundo.quadro`](codigo/landing/landing.md) | function | local | `quadro(nome, { p, t, parado })` | 835 | ✓ |
| [`criarMundo.preparar`](codigo/landing/landing.md) | method | local | `preparar()` | 899 | ✓ |
| [`criarMundo.tamanho`](codigo/landing/landing.md) | method | local | `tamanho(w, h, densidade)` | 905 | ✓ |
| [`criarMundo.simplificar`](codigo/landing/landing.md) | method | local | `simplificar()` | 922 | ✓ |

### rolagem.js

Fonte: [`landing/src/rolagem.js`](../../landing/src/rolagem.js) · Referência: [landing](codigo/landing/landing.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`animarRolagem`](codigo/landing/landing.md) | function | export | `animarRolagem()` | 11 | ✓ |
| [`animarRolagem.montar`](codigo/landing/landing.md) | function | local | `montar()` | 14 | ✓ |

## landing

### vite.config.js

Fonte: [`landing/vite.config.js`](../../landing/vite.config.js) · Referência: [landing](codigo/landing/landing.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`assetFileNames`](codigo/landing/landing.md) | method | local | `assetFileNames(arquivo)` | 31 | ✓ |

## ml/marilia-grade-2km

### experimento.py

Fonte: [`ml/marilia-grade-2km/experimento.py`](../../ml/marilia-grade-2km/experimento.py) · Referência: [treino-e-experimento](codigo/ml/treino-e-experimento.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`cell_of`](codigo/ml/treino-e-experimento.md) | function | módulo | `cell_of(lat: float, lng: float) -> int \| None` | 39 | ✓ |
| [`sector_of`](codigo/ml/treino-e-experimento.md) | function | módulo | `sector_of(cell: int) -> str` | 49 | ✓ |
| [`cutoff`](codigo/ml/treino-e-experimento.md) | function | módulo | `cutoff(as_of: dt.date) -> dt.date` | 54 | ✓ |
| [`norm`](codigo/ml/treino-e-experimento.md) | function | módulo | `norm(text: str) -> str` | 60 | ✓ |
| [`write_json`](codigo/ml/treino-e-experimento.md) | function | módulo | `write_json(path: Path, data: object) -> None` | 64 | ✓ |
| [`prepare`](codigo/ml/treino-e-experimento.md) | function | módulo | `prepare(zip_path: Path) -> None` | 68 | ✓ |
| [`Grid`](codigo/ml/treino-e-experimento.md) | class | módulo | `Grid` | 109 | ✓ |
| [`Grid.__init__`](codigo/ml/treino-e-experimento.md) | method | privada | `__init__(self) -> None` | 110 | ✓ |
| [`Grid.total`](codigo/ml/treino-e-experimento.md) | method | módulo | `total(self, begin: dt.date, end: dt.date) -> np.ndarray` | 123 | ✓ |
| [`Grid.week_rows`](codigo/ml/treino-e-experimento.md) | method | módulo | `week_rows(self, monday: dt.date) -> list[dict]` | 127 | ✓ |
| [`mondays`](codigo/ml/treino-e-experimento.md) | function | módulo | `mondays(first: dt.date, label_until: dt.date) -> list[dt.date]` | 151 | ✓ |
| [`fit`](codigo/ml/treino-e-experimento.md) | function | módulo | `fit(grid: Grid, issued: dt.date, alpha: float) -> tuple[StandardScaler, PoissonRegressor]` | 159 | ✓ |
| [`evaluate`](codigo/ml/treino-e-experimento.md) | function | módulo | `evaluate(grid: Grid, weeks: list[dt.date], scaler: StandardScaler, model: PoissonRegressor) -> dict` | 171 | ✓ |
| [`bootstrap`](codigo/ml/treino-e-experimento.md) | function | módulo | `bootstrap(weeks: list[dict], a: str, b: str, seed: int=2026, draws: int=2000) -> dict` | 205 | ✓ |
| [`validate`](codigo/ml/treino-e-experimento.md) | function | módulo | `validate() -> None` | 218 | ✓ |
| [`test`](codigo/ml/treino-e-experimento.md) | function | módulo | `test() -> None` | 234 | ✓ |

## ml/marilia-setores

### preparar_dados.py

Fonte: [`ml/marilia-setores/preparar_dados.py`](../../ml/marilia-setores/preparar_dados.py) · Referência: [treino-e-experimento](codigo/ml/treino-e-experimento.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`write_json`](codigo/ml/treino-e-experimento.md) | function | módulo | `write_json(path: Path, value: object) -> None` | 20 | ✓ |
| [`load_config`](codigo/ml/treino-e-experimento.md) | function | módulo | `load_config() -> dict` | 27 | ✓ |
| [`sector_for`](codigo/ml/treino-e-experimento.md) | function | módulo | `sector_for(lat: float, lng: float, config: dict) -> str \| None` | 31 | ✓ |
| [`sector_geojson`](codigo/ml/treino-e-experimento.md) | function | módulo | `sector_geojson(config: dict) -> dict` | 40 | ✓ |
| [`normalize`](codigo/ml/treino-e-experimento.md) | function | módulo | `normalize(value: str) -> str` | 58 | ✓ |
| [`sha256`](codigo/ml/treino-e-experimento.md) | function | módulo | `sha256(path: Path) -> str` | 62 | ✓ |
| [`obtain_source`](codigo/ml/treino-e-experimento.md) | function | módulo | `obtain_source(path: Path, config: dict) -> None` | 67 | ✓ |
| [`prepare`](codigo/ml/treino-e-experimento.md) | function | módulo | `prepare(path: Path, config: dict) -> dict` | 85 | ✓ |
| [`main`](codigo/ml/treino-e-experimento.md) | function | módulo | `main() -> None` | 155 | ✓ |

## ml/marilia-setores/tests

### test_protocolo.py

Fonte: [`ml/marilia-setores/tests/test_protocolo.py`](../../ml/marilia-setores/tests/test_protocolo.py) · Referência: [treino-e-experimento](codigo/ml/treino-e-experimento.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`ProtocolTest`](codigo/ml/treino-e-experimento.md#teststest_protocolopy-unittest-classe-protocoltest) | class | módulo | `ProtocolTest` | 10 | ✓ |
| [`ProtocolTest.setUpClass`](codigo/ml/treino-e-experimento.md) | method | módulo | `setUpClass(cls)` | 12 | ✓ |
| [`ProtocolTest.test_publication_cutoff_across_year_boundary`](codigo/ml/treino-e-experimento.md) | method | módulo | `test_publication_cutoff_across_year_boundary(self)` | 16 | ✓ |
| [`ProtocolTest.test_future_observations_do_not_change_features`](codigo/ml/treino-e-experimento.md) | method | módulo | `test_future_observations_do_not_change_features(self)` | 20 | ✓ |
| [`ProtocolTest.test_complete_zero_grid_and_labels_known_before_fit`](codigo/ml/treino-e-experimento.md) | method | módulo | `test_complete_zero_grid_and_labels_known_before_fit(self)` | 30 | ✓ |
| [`ProtocolTest.test_border_has_one_deterministic_sector`](codigo/ml/treino-e-experimento.md) | method | módulo | `test_border_has_one_deterministic_sector(self)` | 39 | ✓ |

## ml/marilia-setores

### treino.py

Fonte: [`ml/marilia-setores/treino.py`](../../ml/marilia-setores/treino.py) · Referência: [treino-e-experimento](codigo/ml/treino-e-experimento.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`date`](codigo/ml/treino-e-experimento.md) | function | módulo | `date(value: str) -> dt.date` | 29 | ✓ |
| [`availability_cutoff`](codigo/ml/treino-e-experimento.md) | function | módulo | `availability_cutoff(as_of: dt.date, lag: int) -> dt.date` | 33 | ✓ |
| [`Counts`](codigo/ml/treino-e-experimento.md) | class | módulo | `Counts` | 40 | ✓ |
| [`Counts.__init__`](codigo/ml/treino-e-experimento.md) | method | privada | `__init__(self, config: dict)` | 41 | ✓ |
| [`Counts.total`](codigo/ml/treino-e-experimento.md) | method | módulo | `total(self, begin: dt.date, end: dt.date, sector: int) -> float` | 65 | ✓ |
| [`Counts.features`](codigo/ml/treino-e-experimento.md) | method | módulo | `features(self, as_of: dt.date, sector: int) -> tuple[list[float], dict]` | 70 | ✓ |
| [`feature_names`](codigo/ml/treino-e-experimento.md) | function | módulo | `feature_names(config: dict) -> list[str]` | 90 | ✓ |
| [`rows`](codigo/ml/treino-e-experimento.md) | function | módulo | `rows(counts: Counts) -> list[dict]` | 95 | ✓ |
| [`labels_available`](codigo/ml/treino-e-experimento.md) | function | módulo | `labels_available(all_rows: list[dict], issued_at: dt.date, config: dict) -> list[dict]` | 110 | ✓ |
| [`fit`](codigo/ml/treino-e-experimento.md) | function | módulo | `fit(selected: list[dict], alpha: float) -> dict` | 115 | ✓ |
| [`predict`](codigo/ml/treino-e-experimento.md) | function | módulo | `predict(fitted: dict, selected: list[dict]) -> np.ndarray` | 130 | ✓ |
| [`baseline`](codigo/ml/treino-e-experimento.md) | function | módulo | `baseline(selected: list[dict]) -> np.ndarray` | 134 | ✓ |
| [`metrics`](codigo/ml/treino-e-experimento.md) | function | módulo | `metrics(selected: list[dict], predicted: np.ndarray, budget: int) -> dict` | 138 | ✓ |
| [`portable`](codigo/ml/treino-e-experimento.md) | function | módulo | `portable(fitted: dict, config: dict) -> dict` | 156 | ✓ |
| [`portable_predict`](codigo/ml/treino-e-experimento.md) | function | módulo | `portable_predict(model: dict, x: list[float]) -> float` | 165 | ✓ |
| [`result_rows`](codigo/ml/treino-e-experimento.md) | function | módulo | `result_rows(selected: list[dict], predicted: np.ndarray, base: np.ndarray) -> list[dict]` | 171 | ✓ |
| [`run`](codigo/ml/treino-e-experimento.md) | function | módulo | `run(output: Path, as_of: dt.date, mode: str) -> dict` | 177 | ✓ |
| [`main`](codigo/ml/treino-e-experimento.md) | function | módulo | `main() -> None` | 287 | ✓ |

## resources/js/lib

### cep.js

Fonte: [`resources/js/lib/cep.js`](../../resources/js/lib/cep.js) · Referência: [entrada-e-bibliotecas](codigo/frontend/entrada-e-bibliotecas.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`insideArea`](codigo/frontend/entrada-e-bibliotecas.md) | function | local | `insideArea(area, a, b)` | 23 | ✓ |
| [`getJson`](codigo/frontend/entrada-e-bibliotecas.md) | function | local | `getJson(url, signal)` | 26 | ✓ |
| [`initCep`](codigo/frontend/entrada-e-bibliotecas.md) | function | export | `initCep()` | 35 | ✓ |
| [`initCep.byId`](codigo/frontend/entrada-e-bibliotecas.md) | function | local | `byId(key)` | 37 | ✓ |
| [`initCep.digitsOf`](codigo/frontend/entrada-e-bibliotecas.md) | function | local | `digitsOf()` | 50 | ✓ |
| [`initCep.say`](codigo/frontend/entrada-e-bibliotecas.md) | function | local | `say(target, message, kind = '')` | 57 | ✓ |
| [`initCep.syncLocate`](codigo/frontend/entrada-e-bibliotecas.md) | function | local | `syncLocate()` | 62 | ✓ |
| [`initCep.addressKey`](codigo/frontend/entrada-e-bibliotecas.md) | function | local | `addressKey()` | 74 | ✓ |
| [`initCep.locate`](codigo/frontend/entrada-e-bibliotecas.md) | function | local | `locate(mode = 'button')` | 75 | ✓ |
| [`initCep.backToAddress`](codigo/frontend/entrada-e-bibliotecas.md) | function | local | `backToAddress()` | 110 | ✓ |
| [`initCep.lookup`](codigo/frontend/entrada-e-bibliotecas.md) | function | local | `lookup(digits)` | 125 | ✓ |

### dom.js

Fonte: [`resources/js/lib/dom.js`](../../resources/js/lib/dom.js) · Referência: [entrada-e-bibliotecas](codigo/frontend/entrada-e-bibliotecas.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`iconsUrl`](codigo/frontend/entrada-e-bibliotecas.md) | function | export | `iconsUrl()` | 5 | ✓ |
| [`icon`](codigo/frontend/entrada-e-bibliotecas.md) | function | export | `icon(id, cls = 'icon')` | 10 | ✓ |
| [`el`](codigo/frontend/entrada-e-bibliotecas.md) | function | export | `el(tag, attrs = {}, ...children)` | 22 | ✓ |
| [`setBusy`](codigo/frontend/entrada-e-bibliotecas.md) | function | export | `setBusy(btn, busy, label = 'Enviando…')` | 42 | ✓ |

### forms.js

Fonte: [`resources/js/lib/forms.js`](../../resources/js/lib/forms.js) · Referência: [entrada-e-bibliotecas](codigo/frontend/entrada-e-bibliotecas.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`busyLabel`](codigo/frontend/entrada-e-bibliotecas.md) | function | local | `busyLabel(btn)` | 22 | ✓ |
| [`initForms`](codigo/frontend/entrada-e-bibliotecas.md) | function | export | `initForms()` | 27 | ✓ |
| [`initForms.openTarget`](codigo/frontend/entrada-e-bibliotecas.md) | function | local | `openTarget(id)` | 61 | ✓ |
| [`initForms.update`](codigo/frontend/entrada-e-bibliotecas.md) | function | local | `update()` | 77 | ✓ |

### motion.js

Fonte: [`resources/js/lib/motion.js`](../../resources/js/lib/motion.js) · Referência: [entrada-e-bibliotecas](codigo/frontend/entrada-e-bibliotecas.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`read`](codigo/frontend/entrada-e-bibliotecas.md) | function | local | `read()` | 11 | ✓ |
| [`write`](codigo/frontend/entrada-e-bibliotecas.md) | function | local | `write(value)` | 14 | ✓ |
| [`notify`](codigo/frontend/entrada-e-bibliotecas.md) | function | local | `notify()` | 21 | ✓ |
| [`motion`](codigo/frontend/entrada-e-bibliotecas.md) | object | export | `motion = { … }` | 24 | ✓ |
| [`motion.reduced`](codigo/frontend/entrada-e-bibliotecas.md) | method | local | `reduced()` | 25 | ✓ |
| [`motion.source`](codigo/frontend/entrada-e-bibliotecas.md) | method | local | `source()` | 30 | ✓ |
| [`motion.systemReduced`](codigo/frontend/entrada-e-bibliotecas.md) | method | local | `systemReduced()` | 34 | ✓ |
| [`motion.set`](codigo/frontend/entrada-e-bibliotecas.md) | method | local | `set(value)` | 37 | ✓ |
| [`motion.onChange`](codigo/frontend/entrada-e-bibliotecas.md) | method | local | `onChange(fn)` | 42 | ✓ |
| [`motion.d`](codigo/frontend/entrada-e-bibliotecas.md) | method | local | `d(seconds)` | 47 | ✓ |
| [`motionPref`](codigo/frontend/entrada-e-bibliotecas.md) | function | export | `motionPref()` | 53 | ✓ |
| [`motionPref.init`](codigo/frontend/entrada-e-bibliotecas.md) | method | local | `init()` | 58 | ✓ |
| [`motionPref.destroy`](codigo/frontend/entrada-e-bibliotecas.md) | method | local | `destroy()` | 61 | ✓ |
| [`motionPref.sync`](codigo/frontend/entrada-e-bibliotecas.md) | method | local | `sync()` | 64 | ✓ |
| [`motionPref.toggle`](codigo/frontend/entrada-e-bibliotecas.md) | method | local | `toggle()` | 69 | ✓ |
| [`motionPref.pressed`](codigo/frontend/entrada-e-bibliotecas.md) | method | local | `pressed()` | 72 | ✓ |
| [`motionPref.stateLabel`](codigo/frontend/entrada-e-bibliotecas.md) | method | local | `stateLabel()` | 75 | ✓ |
| [`motionPref.whyLabel`](codigo/frontend/entrada-e-bibliotecas.md) | method | local | `whyLabel()` | 78 | ✓ |

### picker.js

Fonte: [`resources/js/lib/picker.js`](../../resources/js/lib/picker.js) · Referência: [entrada-e-bibliotecas](codigo/frontend/entrada-e-bibliotecas.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`initPicker`](codigo/frontend/entrada-e-bibliotecas.md) | function | export | `initPicker()` | 12 | ✓ |
| [`initPicker.say`](codigo/frontend/entrada-e-bibliotecas.md) | function | local | `say()` | 29 | ✓ |
| [`initPicker.mark`](codigo/frontend/entrada-e-bibliotecas.md) | function | local | `mark(latlng)` | 35 | ✓ |
| [`initPicker.fromInputs`](codigo/frontend/entrada-e-bibliotecas.md) | function | local | `fromInputs()` | 51 | ✓ |

### popovers.js

Fonte: [`resources/js/lib/popovers.js`](../../resources/js/lib/popovers.js) · Referência: [entrada-e-bibliotecas](codigo/frontend/entrada-e-bibliotecas.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`isHelp`](codigo/frontend/entrada-e-bibliotecas.md) | function | local | `isHelp(d)` | 13 | ✓ |
| [`panelOf`](codigo/frontend/entrada-e-bibliotecas.md) | function | local | `panelOf(d)` | 14 | ✓ |
| [`areaOf`](codigo/frontend/entrada-e-bibliotecas.md) | function | local | `areaOf(d)` | 15 | ✓ |
| [`animateOpen`](codigo/frontend/entrada-e-bibliotecas.md) | function | local | `animateOpen(d)` | 19 | ✓ |
| [`closePopover`](codigo/frontend/entrada-e-bibliotecas.md) | function | export | `closePopover(d, returnFocus = false)` | 27 | ✓ |
| [`closePopover.done`](codigo/frontend/entrada-e-bibliotecas.md) | function | local | `done()` | 30 | ✓ |
| [`place`](codigo/frontend/entrada-e-bibliotecas.md) | function | local | `place(d)` | 46 | ✓ |
| [`openHelp`](codigo/frontend/entrada-e-bibliotecas.md) | function | local | `openHelp(d, peek)` | 62 | ✓ |
| [`initHelp`](codigo/frontend/entrada-e-bibliotecas.md) | function | local | `initHelp(d)` | 68 | ✓ |
| [`initHelp.later`](codigo/frontend/entrada-e-bibliotecas.md) | function | local | `later(fn, ms)` | 72 | ✓ |
| [`initPopovers`](codigo/frontend/entrada-e-bibliotecas.md) | function | export | `initPopovers(root = document)` | 90 | ✓ |
| [`initPopovers.all`](codigo/frontend/entrada-e-bibliotecas.md) | function | local | `all()` | 91 | ✓ |
| [`initPopovers.reposition`](codigo/frontend/entrada-e-bibliotecas.md) | function | local | `reposition()` | 113 | ✓ |

### reveal.js

Fonte: [`resources/js/lib/reveal.js`](../../resources/js/lib/reveal.js) · Referência: [entrada-e-bibliotecas](codigo/frontend/entrada-e-bibliotecas.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`reveal`](codigo/frontend/entrada-e-bibliotecas.md) | function | export | `reveal(el, on, animate = true)` | 9 | ✓ |

### shell.js

Fonte: [`resources/js/lib/shell.js`](../../resources/js/lib/shell.js) · Referência: [entrada-e-bibliotecas](codigo/frontend/entrada-e-bibliotecas.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`nav`](codigo/frontend/entrada-e-bibliotecas.md) | function | export | `nav()` | 11 | ✓ |
| [`nav.init`](codigo/frontend/entrada-e-bibliotecas.md) | method | local | `init()` | 16 | ✓ |
| [`nav.destroy`](codigo/frontend/entrada-e-bibliotecas.md) | method | local | `destroy()` | 37 | ✓ |
| [`nav.toggle`](codigo/frontend/entrada-e-bibliotecas.md) | method | local | `toggle()` | 40 | ✓ |
| [`nav.close`](codigo/frontend/entrada-e-bibliotecas.md) | method | local | `close(returnFocus = false)` | 43 | ✓ |
| [`nav.closeFromKey`](codigo/frontend/entrada-e-bibliotecas.md) | method | local | `closeFromKey()` | 46 | ✓ |
| [`nav.setOpen`](codigo/frontend/entrada-e-bibliotecas.md) | method | local | `setOpen(open, returnFocus, silent = false)` | 49 | ✓ |

### toasts.js

Fonte: [`resources/js/lib/toasts.js`](../../resources/js/lib/toasts.js) · Referência: [entrada-e-bibliotecas](codigo/frontend/entrada-e-bibliotecas.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`region`](codigo/frontend/entrada-e-bibliotecas.md) | function | local | `region()` | 20 | ✓ |
| [`announce`](codigo/frontend/entrada-e-bibliotecas.md) | function | export | `announce(text, assertive = false)` | 22 | ✓ |
| [`build`](codigo/frontend/entrada-e-bibliotecas.md) | function | local | `build(type, title, body, actions, onClose)` | 29 | ✓ |
| [`fill`](codigo/frontend/entrada-e-bibliotecas.md) | function | local | `fill(el, title, body, actions = [])` | 54 | ✓ |
| [`arm`](codigo/frontend/entrada-e-bibliotecas.md) | function | local | `arm(entry)` | 74 | ✓ |
| [`hold`](codigo/frontend/entrada-e-bibliotecas.md) | function | local | `hold(entry)` | 80 | ✓ |
| [`enter`](codigo/frontend/entrada-e-bibliotecas.md) | function | local | `enter(el, type)` | 87 | ✓ |
| [`show`](codigo/frontend/entrada-e-bibliotecas.md) | function | export | `show({ type = 'info', title, body = '', key = null, actions = [], timeout } = {})` | 101 | ✓ |
| [`dismiss`](codigo/frontend/entrada-e-bibliotecas.md) | function | export | `dismiss(key, instant = false)` | 143 | ✓ |
| [`dismiss.remove`](codigo/frontend/entrada-e-bibliotecas.md) | function | local | `remove()` | 149 | ✓ |
| [`adoptServerFlashes`](codigo/frontend/entrada-e-bibliotecas.md) | function | export | `adoptServerFlashes()` | 160 | ✓ |

## resources/js/pages

### central.js

Fonte: [`resources/js/pages/central.js`](../../resources/js/pages/central.js) · Referência: [central](codigo/frontend/central.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`positionText`](codigo/frontend/central.md) | function | local | `positionText(r)` | 29 | ✓ |
| [`safe`](codigo/frontend/central.md) | function | local | `safe(code, fallback)` | 39 | ✓ |
| [`keyOf`](codigo/frontend/central.md) | function | local | `keyOf(r)` | 40 | ✓ |
| [`hasPosition`](codigo/frontend/central.md) | function | local | `hasPosition(r)` | 41 | ✓ |
| [`clock`](codigo/frontend/central.md) | function | local | `clock(iso)` | 42 | ✓ |
| [`dateTime`](codigo/frontend/central.md) | function | local | `dateTime(iso)` | 43 | ✓ |
| [`brDate`](codigo/frontend/central.md) | function | local | `brDate(ymd)` | 44 | ✓ |
| [`pct`](codigo/frontend/central.md) | function | local | `pct(v)` | 45 | ✓ |
| [`ago`](codigo/frontend/central.md) | function | local | `ago(iso)` | 46 | ✓ |
| [`freshnessText`](codigo/frontend/central.md) | function | local | `freshnessText(r)` | 55 | ✓ |
| [`register`](codigo/frontend/central.md) | function | export | `register(Alpine)` | 66 | ✓ |
| [`central`](codigo/frontend/central.md) | function | local | `central()` | 70 | ✓ |
| [`central.on`](codigo/frontend/central.md) | function | local | `on(target, type, fn, opts)` | 103 | ✓ |
| [`central.init`](codigo/frontend/central.md) | method | local | `init()` | 131 | ✓ |
| [`central.init.tweens`](codigo/frontend/central.md) | method | local | `tweens()` | 162 | ✓ |
| [`central.init.layers`](codigo/frontend/central.md) | method | local | `layers()` | 162 | ✓ |
| [`central.destroy`](codigo/frontend/central.md) | method | local | `destroy()` | 164 | ✓ |
| [`central.find`](codigo/frontend/central.md) | method | local | `find(k)` | 175 | ✓ |
| [`central.rowVM`](codigo/frontend/central.md) | method | local | `rowVM(r)` | 178 | ✓ |
| [`central.panelVM`](codigo/frontend/central.md) | method | local | `panelVM(r)` | 202 | ✓ |
| [`central.applySnapshot`](codigo/frontend/central.md) | method | local | `applySnapshot(snap, reason)` | 254 | ✓ |
| [`central.applySnapshot.byTitle`](codigo/frontend/central.md) | function | local | `byTitle(a, b)` | 271 | ✓ |
| [`central.applySnapshot.onEnter`](codigo/frontend/central.md) | method | local | `onEnter(els)` | 293 | ✓ |
| [`central.highlightChanges`](codigo/frontend/central.md) | method | local | `highlightChanges(before, beforeKpi)` | 311 | ✓ |
| [`central.bumpCount`](codigo/frontend/central.md) | method | local | `bumpCount()` | 327 | ✓ |
| [`central.initMap`](codigo/frontend/central.md) | method | local | `initMap()` | 334 | ✓ |
| [`central.pinHtml`](codigo/frontend/central.md) | method | local | `pinHtml(r)` | 357 | ✓ |
| [`central.pinLabel`](codigo/frontend/central.md) | method | local | `pinLabel(r)` | 369 | ✓ |
| [`central.asButton`](codigo/frontend/central.md) | method | local | `asButton(marker, label, activate)` | 372 | ✓ |
| [`central.groups`](codigo/frontend/central.md) | method | local | `groups()` | 385 | ✓ |
| [`central.syncMarkers`](codigo/frontend/central.md) | method | local | `syncMarkers()` | 397 | ✓ |
| [`central.syncMarkers.activate`](codigo/frontend/central.md) | function | local | `activate()` | 423 | ✓ |
| [`central.paintSelection`](codigo/frontend/central.md) | method | local | `paintSelection(k, marker)` | 466 | ✓ |
| [`central.ring`](codigo/frontend/central.md) | method | local | `ring(pin, times = 2)` | 474 | ✓ |
| [`central.popPin`](codigo/frontend/central.md) | method | local | `popPin(k)` | 480 | ✓ |
| [`central.openGroup`](codigo/frontend/central.md) | method | local | `openGroup(members, marker)` | 489 | ✓ |
| [`central.openGroup.onclick`](codigo/frontend/central.md) | method | local | `onclick()` | 499 | ✓ |
| [`central.panTo`](codigo/frontend/central.md) | method | local | `panTo(r, fly = true)` | 507 | ✓ |
| [`central.initPanel`](codigo/frontend/central.md) | method | local | `initPanel()` | 522 | ✓ |
| [`central.playPanel`](codigo/frontend/central.md) | method | local | `playPanel(open)` | 541 | ✓ |
| [`central.select`](codigo/frontend/central.md) | method | local | `select(k, { origin = null, from = 'list', focus = true } = {})` | 554 | ✓ |
| [`central.close`](codigo/frontend/central.md) | method | local | `close()` | 578 | ✓ |
| [`central.ensureRowVisible`](codigo/frontend/central.md) | method | local | `ensureRowVisible(k)` | 590 | ✓ |
| [`central.rowClick`](codigo/frontend/central.md) | method | local | `rowClick(e, key)` | 598 | ✓ |
| [`central.isSelected`](codigo/frontend/central.md) | method | local | `isSelected(key)` | 603 | ✓ |
| [`central.expandOthers`](codigo/frontend/central.md) | method | local | `expandOthers()` | 606 | ✓ |
| [`central.expandOthers.onEnter`](codigo/frontend/central.md) | method | local | `onEnter(els)` | 613 | ✓ |
| [`central.showOnMap`](codigo/frontend/central.md) | method | local | `showOnMap()` | 616 | ✓ |
| [`central.setView`](codigo/frontend/central.md) | method | local | `setView(view)` | 623 | ✓ |
| [`central.viewMap`](codigo/frontend/central.md) | method | local | `viewMap()` | 634 | ✓ |
| [`central.viewList`](codigo/frontend/central.md) | method | local | `viewList()` | 635 | ✓ |
| [`central.mapPressed`](codigo/frontend/central.md) | method | local | `mapPressed()` | 636 | ✓ |
| [`central.listPressed`](codigo/frontend/central.md) | method | local | `listPressed()` | 637 | ✓ |
| [`central.readChips`](codigo/frontend/central.md) | method | local | `readChips()` | 640 | ✓ |
| [`central.queryString`](codigo/frontend/central.md) | method | local | `queryString()` | 656 | ✓ |
| [`central.writeUrl`](codigo/frontend/central.md) | method | local | `writeUrl()` | 661 | ✓ |
| [`central.onFilterChange`](codigo/frontend/central.md) | method | local | `onFilterChange(e)` | 668 | ✓ |
| [`central.onSearchInput`](codigo/frontend/central.md) | method | local | `onSearchInput()` | 672 | ✓ |
| [`central.onSubmit`](codigo/frontend/central.md) | method | local | `onSubmit(e)` | 676 | ✓ |
| [`central.applyFilters`](codigo/frontend/central.md) | method | local | `applyFilters()` | 681 | ✓ |
| [`central.removeChip`](codigo/frontend/central.md) | method | local | `removeChip(id)` | 686 | ✓ |
| [`central.clearFilters`](codigo/frontend/central.md) | method | local | `clearFilters()` | 696 | ✓ |
| [`central.showAttention`](codigo/frontend/central.md) | method | local | `showAttention()` | 705 | ✓ |
| [`central.hasChips`](codigo/frontend/central.md) | method | local | `hasChips()` | 711 | ✓ |
| [`central.schedule`](codigo/frontend/central.md) | method | local | `schedule()` | 714 | ✓ |
| [`central.load`](codigo/frontend/central.md) | method | local | `load(reason)` | 721 | ✓ |
| [`central.reportOffline`](codigo/frontend/central.md) | method | local | `reportOffline()` | 773 | ✓ |
| [`central.reportOffline.onClick`](codigo/frontend/central.md) | method | local | `onClick(btn)` | 779 | ✓ |
| [`central.refreshNow`](codigo/frontend/central.md) | method | local | `refreshNow()` | 782 | ✓ |
| [`central.togglePause`](codigo/frontend/central.md) | method | local | `togglePause()` | 785 | ✓ |
| [`central.pauseLabel`](codigo/frontend/central.md) | method | local | `pauseLabel()` | 789 | ✓ |
| [`central.pausePressed`](codigo/frontend/central.md) | method | local | `pausePressed()` | 790 | ✓ |
| [`central.renderSync`](codigo/frontend/central.md) | method | local | `renderSync()` | 791 | ✓ |
| [`central.ping`](codigo/frontend/central.md) | method | local | `ping()` | 809 | ✓ |
| [`central.announceNewAlerts`](codigo/frontend/central.md) | method | local | `announceNewAlerts(snap, mayAnnounce)` | 814 | ✓ |
| [`central.announceNewAlerts.onClick`](codigo/frontend/central.md) | method | local | `onClick()` | 827 | ✓ |
| [`central.post`](codigo/frontend/central.md) | method | local | `post(url, btn, busyLabel)` | 832 | ✓ |
| [`central.acknowledge`](codigo/frontend/central.md) | method | local | `acknowledge(e, id)` | 850 | ✓ |
| [`central.unconfirmed`](codigo/frontend/central.md) | method | local | `unconfirmed(key, what, message)` | 864 | ✓ |
| [`central.unconfirmed.onClick`](codigo/frontend/central.md) | method | local | `onClick(btn)` | 866 | ✓ |
| [`central.simulateFailure`](codigo/frontend/central.md) | method | local | `simulateFailure(e)` | 870 | ✓ |
| [`central.onMotionChange`](codigo/frontend/central.md) | method | local | `onMotionChange()` | 886 | ✓ |

### multas.js

Fonte: [`resources/js/pages/multas.js`](../../resources/js/pages/multas.js) · Referência: [paginas](codigo/frontend/paginas.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`filled`](codigo/frontend/paginas.md) | function | local | `filled(el)` | 11 | ✓ |
| [`showIf`](codigo/frontend/paginas.md) | function | local | `showIf(form)` | 20 | ✓ |
| [`showIf.value`](codigo/frontend/paginas.md) | function | local | `value(name)` | 26 | ✓ |
| [`showIf.update`](codigo/frontend/paginas.md) | function | local | `update(animate = true)` | 27 | ✓ |
| [`sourceForm`](codigo/frontend/paginas.md) | function | local | `sourceForm(form)` | 33 | ✓ |
| [`sourceForm.agencyOf`](codigo/frontend/paginas.md) | function | local | `agencyOf(select)` | 43 | ✓ |
| [`sourceForm.radarLacks`](codigo/frontend/paginas.md) | function | local | `radarLacks()` | 44 | ✓ |
| [`sourceForm.update`](codigo/frontend/paginas.md) | function | local | `update(animate = true)` | 51 | ✓ |
| [`suggestDescription`](codigo/frontend/paginas.md) | function | local | `suggestDescription(form, kind)` | 80 | ✓ |
| [`suggestDescription.suggestion`](codigo/frontend/paginas.md) | function | local | `suggestion()` | 84 | ✓ |
| [`suggestDescription.fill`](codigo/frontend/paginas.md) | function | local | `fill()` | 86 | ✓ |
| [`checkForm`](codigo/frontend/paginas.md) | function | local | `checkForm(form)` | 95 | ✓ |
| [`checkForm.update`](codigo/frontend/paginas.md) | function | local | `update()` | 99 | ✓ |
| [`reviewQuestion`](codigo/frontend/paginas.md) | function | local | `reviewQuestion(form)` | 111 | ✓ |
| [`reviewQuestion.sync`](codigo/frontend/paginas.md) | function | local | `sync(animate = true)` | 120 | ✓ |
| [`reviewQuestion.onStatus`](codigo/frontend/paginas.md) | function | local | `onStatus(animate = true)` | 127 | ✓ |
| [`init`](codigo/frontend/paginas.md) | function | export | `init()` | 146 | ✓ |

### planejar.js

Fonte: [`resources/js/pages/planejar.js`](../../resources/js/pages/planejar.js) · Referência: [paginas](codigo/frontend/paginas.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`text`](codigo/frontend/paginas.md) | function | local | `text(value)` | 9 | ✓ |
| [`baseMap`](codigo/frontend/paginas.md) | function | local | `baseMap(box, data)` | 15 | ✓ |
| [`band`](codigo/frontend/paginas.md) | function | local | `band(value, min, max)` | 31 | ✓ |
| [`cellsMap`](codigo/frontend/paginas.md) | function | local | `cellsMap(box, data)` | 33 | ✓ |
| [`cellsMap.rect`](codigo/frontend/paginas.md) | function | local | `rect(cell)` | 39 | ✓ |
| [`missionForm`](codigo/frontend/paginas.md) | function | local | `missionForm(form)` | 58 | ✓ |
| [`missionForm.update`](codigo/frontend/paginas.md) | function | local | `update(animate = true)` | 63 | ✓ |
| [`init`](codigo/frontend/paginas.md) | function | export | `init()` | 73 | ✓ |

### sobre.js

Fonte: [`resources/js/pages/sobre.js`](../../resources/js/pages/sobre.js) · Referência: [paginas](codigo/frontend/paginas.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`init`](codigo/frontend/paginas.md) | function | export | `init()` | 33 | ✓ |
| [`entrance`](codigo/frontend/paginas.md) | function | local | `entrance(parts)` | 67 | ✓ |
| [`revealTimeline`](codigo/frontend/paginas.md) | function | local | `revealTimeline({ img, halo, mascot, mascotHop, mascotShadow, codex, codexSpin, kicker, name, url })` | 86 | ✓ |
| [`revealTimeline.onComplete`](codigo/frontend/paginas.md) | method | local | `onComplete()` | 89 | ✓ |
| [`follow`](codigo/frontend/paginas.md) | function | local | `follow({ card, stage, photo, mascot, mascotRig, eyes, codex, codexRig })` | 107 | ✓ |
| [`follow.to`](codigo/frontend/paginas.md) | function | local | `to(target, prop, duration)` | 109 | ✓ |
| [`follow.reach`](codigo/frontend/paginas.md) | function | local | `reach(distance, range)` | 120 | ✓ |
| [`follow.fromCenter`](codigo/frontend/paginas.md) | function | local | `fromCenter(target, event)` | 121 | ✓ |
| [`open`](codigo/frontend/paginas.md) | function | local | `open(event, parts)` | 150 | ✓ |
| [`tremor`](codigo/frontend/paginas.md) | function | local | `tremor({ card, photo, rings, mascotHop, mascotShadow, codexSpin })` | 167 | ✓ |
| [`expand`](codigo/frontend/paginas.md) | function | local | `expand(parts)` | 180 | ✓ |
| [`go`](codigo/frontend/paginas.md) | function | local | `go()` | 213 | ✓ |
| [`cancel`](codigo/frontend/paginas.md) | function | local | `cancel(parts)` | 220 | ✓ |
| [`reset`](codigo/frontend/paginas.md) | function | local | `reset({ card, photo, rings, mascotHop, mascotShadow, codexSpin, kicker, url })` | 233 | ✓ |
| [`copy`](codigo/frontend/paginas.md) | function | local | `copy(node)` | 252 | ✓ |
| [`portal`](codigo/frontend/paginas.md) | function | local | `portal(stage, name, host)` | 264 | ✓ |

## scripts

### capturar-telas.mjs

Fonte: [`scripts/capturar-telas.mjs`](../../scripts/capturar-telas.mjs) · Referência: [scripts](codigo/scripts/scripts.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`sleep`](codigo/scripts/scripts.md) | function | local | `sleep(ms)` | 28 | ✓ |
| [`target`](codigo/scripts/scripts.md) | function | local | `target()` | 30 | ✓ |
| [`send`](codigo/scripts/scripts.md) | function | local | `send(method, params = {})` | 57 | ✓ |
| [`evaluate`](codigo/scripts/scripts.md) | function | local | `evaluate(expression)` | 62 | ✓ |
| [`navigate`](codigo/scripts/scripts.md) | function | local | `navigate(url)` | 69 | ✓ |

## scripts/documentacao

### inventario.mjs

Fonte: [`scripts/documentacao/inventario.mjs`](../../scripts/documentacao/inventario.mjs) · Referência: [scripts](codigo/scripts/scripts.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`git`](codigo/scripts/scripts.md) | function | local | `git(...a)` | 20 | ✓ |
| [`isCode`](codigo/scripts/scripts.md) | function | local | `isCode(f)` | 24 | ✓ |
| [`generated`](codigo/scripts/scripts.md) | function | local | `generated(f)` | 26 | ✓ |
| [`lines`](codigo/scripts/scripts.md) | function | local | `lines(out)` | 34 | ✓ |
| [`jsSymbols`](codigo/scripts/scripts.md) | function | local | `jsSymbols(file)` | 48 | ✓ |
| [`jsSymbols.lineOf`](codigo/scripts/scripts.md) | function | local | `lineOf(offset)` | 50 | ✓ |
| [`jsSymbols.add`](codigo/scripts/scripts.md) | function | local | `add(node, name, kind, exported, params)` | 53 | ✓ |
| [`jsSymbols.isFn`](codigo/scripts/scripts.md) | function | local | `isFn(n)` | 58 | ✓ |
| [`jsSymbols.keyName`](codigo/scripts/scripts.md) | function | local | `keyName(k)` | 59 | ✓ |
| [`jsSymbols.visit`](codigo/scripts/scripts.md) | function | local | `visit(node, scope, exported = false)` | 61 | ✓ |
| [`walkDocs`](codigo/scripts/scripts.md) | function | local | `walkDocs(dir)` | 134 | ✓ |
| [`anchor`](codigo/scripts/scripts.md) | function | local | `anchor(text)` | 147 | ✓ |
| [`writeCatalog`](codigo/scripts/scripts.md) | function | local | `writeCatalog()` | 168 | ✓ |
| [`writeCatalog.rel`](codigo/scripts/scripts.md) | function | local | `rel(to)` | 171 | ✓ |
| [`writeCatalog.esc`](codigo/scripts/scripts.md) | function | local | `esc(t)` | 172 | ✓ |
| [`verify`](codigo/scripts/scripts.md) | function | local | `verify()` | 215 | ✓ |
| [`verify.anchorsIn`](codigo/scripts/scripts.md) | function | local | `anchorsIn(f)` | 231 | ✓ |

### simbolos-py.py

Fonte: [`scripts/documentacao/simbolos-py.py`](../../scripts/documentacao/simbolos-py.py) · Referência: [scripts](codigo/scripts/scripts.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`walk`](codigo/scripts/scripts.md) | function | módulo | `walk(node, file, prefix)` | 12 | ✓ |

## scripts/verificacao-interface

### acompanhamento.cjs

Fonte: [`scripts/verificacao-interface/acompanhamento.cjs`](../../scripts/verificacao-interface/acompanhamento.cjs) · Referência: [scripts](codigo/scripts/scripts.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`sleep`](codigo/scripts/scripts.md) | function | local | `sleep(ms)` | 11 | ✓ |
| [`check`](codigo/scripts/scripts.md) | function | local | `check(nome, ok, detalhe = null)` | 17 | ✓ |
| [`page`](codigo/scripts/scripts.md) | function | local | `page(size, { reduced = false } = {})` | 19 | ✓ |
| [`shot`](codigo/scripts/scripts.md) | function | local | `shot(p, name, full = true)` | 29 | ✓ |
| [`noHScroll`](codigo/scripts/scripts.md) | function | local | `noHScroll(p)` | 30 | ✓ |
| [`flash`](codigo/scripts/scripts.md) | function | local | `flash(p)` | 31 | ✓ |
| [`submit`](codigo/scripts/scripts.md) | function | local | `submit(p, selector)` | 32 | ✓ |
| [`z`](codigo/scripts/scripts.md) | function | local | `z(n)` | 71 | ✓ |

### agentes.cjs

Fonte: [`scripts/verificacao-interface/agentes.cjs`](../../scripts/verificacao-interface/agentes.cjs) · Referência: [scripts](codigo/scripts/scripts.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`sleep`](codigo/scripts/scripts.md) | function | local | `sleep(ms)` | 10 | ✓ |
| [`waitStreet`](codigo/scripts/scripts.md) | function | local | `waitStreet(pg, v)` | 16 | ✓ |
| [`waitStatus`](codigo/scripts/scripts.md) | function | local | `waitStatus(pg, re)` | 17 | ✓ |
| [`check`](codigo/scripts/scripts.md) | function | local | `check(nome, ok, detalhe = null)` | 24 | ✓ |
| [`page`](codigo/scripts/scripts.md) | function | local | `page(size, { reduced = false, js = true, simulated = true } = {})` | 26 | ✓ |
| [`shot`](codigo/scripts/scripts.md) | function | local | `shot(p, name, full = false)` | 54 | ✓ |
| [`noHScroll`](codigo/scripts/scripts.md) | function | local | `noHScroll(p)` | 55 | ✓ |
| [`status`](codigo/scripts/scripts.md) | function | local | `status(p)` | 56 | ✓ |
| [`typeCep`](codigo/scripts/scripts.md) | function | local | `typeCep(p, cep)` | 57 | ✓ |

### ajuda.cjs

Fonte: [`scripts/verificacao-interface/ajuda.cjs`](../../scripts/verificacao-interface/ajuda.cjs) · Referência: [scripts](codigo/scripts/scripts.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`sleep`](codigo/scripts/scripts.md) | function | local | `sleep(ms)` | 8 | ✓ |
| [`check`](codigo/scripts/scripts.md) | function | local | `check(nome, ok, detalhe = null)` | 14 | ✓ |
| [`page`](codigo/scripts/scripts.md) | function | local | `page(size, { reduced = false, js = true, touch = false } = {})` | 16 | ✓ |
| [`shot`](codigo/scripts/scripts.md) | function | local | `shot(p, name)` | 26 | ✓ |
| [`open`](codigo/scripts/scripts.md) | function | local | `open(p, id)` | 27 | ✓ |
| [`inside`](codigo/scripts/scripts.md) | function | local | `inside(p, id)` | 29 | ✓ |
| [`firstLink`](codigo/scripts/scripts.md) | function | local | `firstLink(p, path, sel)` | 33 | ✓ |
| [`strip`](codigo/scripts/scripts.md) | function | local | `strip(h)` | 42 | ✓ |
| [`describe`](codigo/scripts/scripts.md) | function | local | `describe(sel)` | 89 | ✓ |

### auditoria.cjs

Fonte: [`scripts/verificacao-interface/auditoria.cjs`](../../scripts/verificacao-interface/auditoria.cjs) · Referência: [scripts](codigo/scripts/scripts.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`check`](codigo/scripts/scripts.md) | function | local | `check(nome, ok, detalhe = null)` | 16 | ✓ |
| [`key`](codigo/scripts/scripts.md) | function | local | `key(r)` | 25 | ✓ |
| [`groups`](codigo/scripts/scripts.md) | function | local | `groups()` | 44 | ✓ |
| [`until`](codigo/scripts/scripts.md) | function | local | `until(k)` | 46 | ✓ |
| [`toast`](codigo/scripts/scripts.md) | function | local | `toast()` | 78 | ✓ |

### captures.cjs

Fonte: [`scripts/verificacao-interface/captures.cjs`](../../scripts/verificacao-interface/captures.cjs) · Referência: [scripts](codigo/scripts/scripts.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`sleep`](codigo/scripts/scripts.md) | function | local | `sleep(ms)` | 6 | ✓ |
| [`page`](codigo/scripts/scripts.md) | function | local | `page(size, { auth = true, touch = false, reduced = false } = {})` | 12 | ✓ |
| [`shot`](codigo/scripts/scripts.md) | function | local | `shot(p, name, full = false)` | 19 | ✓ |

### comum.cjs

Fonte: [`scripts/verificacao-interface/comum.cjs`](../../scripts/verificacao-interface/comum.cjs) · Referência: [scripts](codigo/scripts/scripts.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`entrar`](codigo/scripts/scripts.md) | function | local | `entrar(page)` | 6 | ✓ |
| [`sessao`](codigo/scripts/scripts.md) | function | local | `sessao(browser)` | 15 | ✓ |
| [`instantaneo`](codigo/scripts/scripts.md) | function | local | `instantaneo(page)` | 24 | ✓ |

### fps.cjs

Fonte: [`scripts/verificacao-interface/fps.cjs`](../../scripts/verificacao-interface/fps.cjs) · Referência: [scripts](codigo/scripts/scripts.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`measure`](codigo/scripts/scripts.md) | function | local | `measure(page, action)` | 3 | ✓ |
| [`measure.loop`](codigo/scripts/scripts.md) | function | local | `loop(ts)` | 4 | ✓ |

### multas.cjs

Fonte: [`scripts/verificacao-interface/multas.cjs`](../../scripts/verificacao-interface/multas.cjs) · Referência: [scripts](codigo/scripts/scripts.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`sleep`](codigo/scripts/scripts.md) | function | local | `sleep(ms)` | 8 | ✓ |
| [`check`](codigo/scripts/scripts.md) | function | local | `check(nome, ok, detalhe = null)` | 14 | ✓ |
| [`page`](codigo/scripts/scripts.md) | function | local | `page(size, { reduced = false, js = true, motionPref = null } = {})` | 16 | ✓ |
| [`shot`](codigo/scripts/scripts.md) | function | local | `shot(p, name, full = false)` | 28 | ✓ |
| [`noHScroll`](codigo/scripts/scripts.md) | function | local | `noHScroll(p)` | 29 | ✓ |
| [`fineId`](codigo/scripts/scripts.md) | function | local | `fineId(p, text)` | 30 | ✓ |
| [`hidden`](codigo/scripts/scripts.md) | function | local | `hidden(sel)` | 67 | ✓ |
| [`shown`](codigo/scripts/scripts.md) | function | local | `shown(...sels)` | 107 | ✓ |

### record.cjs

Fonte: [`scripts/verificacao-interface/record.cjs`](../../scripts/verificacao-interface/record.cjs) · Referência: [scripts](codigo/scripts/scripts.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`sleep`](codigo/scripts/scripts.md) | function | local | `sleep(ms)` | 8 | ✓ |
| [`clip`](codigo/scripts/scripts.md) | function | local | `clip(browser, name, size, fn)` | 11 | ✓ |
| [`hover`](codigo/scripts/scripts.md) | function | local | `hover(page, sel, ms = 500)` | 27 | ✓ |

### revisao-fluxos.cjs

Fonte: [`scripts/verificacao-interface/revisao-fluxos.cjs`](../../scripts/verificacao-interface/revisao-fluxos.cjs) · Referência: [scripts](codigo/scripts/scripts.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`sleep`](codigo/scripts/scripts.md) | function | local | `sleep(ms)` | 15 | ✓ |
| [`check`](codigo/scripts/scripts.md) | function | local | `check(nome, ok, detalhe = null)` | 21 | ✓ |
| [`page`](codigo/scripts/scripts.md) | function | local | `page(size, { reduced = false, login = true } = {})` | 23 | ✓ |
| [`shot`](codigo/scripts/scripts.md) | function | local | `shot(p, name, full = true)` | 34 | ✓ |
| [`noHScroll`](codigo/scripts/scripts.md) | function | local | `noHScroll(p)` | 35 | ✓ |
| [`flash`](codigo/scripts/scripts.md) | function | local | `flash(p)` | 36 | ✓ |
| [`submit`](codigo/scripts/scripts.md) | function | local | `submit(p, selector)` | 37 | ✓ |
| [`text`](codigo/scripts/scripts.md) | function | local | `text(p, selector)` | 38 | ✓ |
| [`signIn`](codigo/scripts/scripts.md) | function | local | `signIn(pg, password)` | 177 | ✓ |

### verify.cjs

Fonte: [`scripts/verificacao-interface/verify.cjs`](../../scripts/verificacao-interface/verify.cjs) · Referência: [scripts](codigo/scripts/scripts.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`sleep`](codigo/scripts/scripts.md) | function | local | `sleep(ms)` | 7 | ✓ |
| [`tileSvg`](codigo/scripts/scripts.md) | function | local | `tileSvg(url)` | 8 | ✓ |
| [`ctx`](codigo/scripts/scripts.md) | function | local | `ctx(browser, opts = {})` | 13 | ✓ |
| [`central`](codigo/scripts/scripts.md) | function | local | `central(page, q = '')` | 28 | ✓ |
| [`diag`](codigo/scripts/scripts.md) | function | local | `diag(page)` | 29 | ✓ |
| [`panelState`](codigo/scripts/scripts.md) | function | local | `panelState(page)` | 30 | ✓ |

## src/Application

### AddressLocator.php

Fonte: [`src/Application/AddressLocator.php`](../../src/Application/AddressLocator.php) · Referência: [endereco](codigo/application/endereco.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`AddressLocator`](codigo/application/endereco.md#addresslocator) | class | undefined | `` | 12 | ✓ |
| [`AddressLocator::__construct`](codigo/application/endereco.md) | method | public | `__construct(private readonly CepLookup $cep, private readonly Geocoder $geocoder)` | 26 | ✓ |
| [`AddressLocator::locate`](codigo/application/endereco.md#addresslocatorlocate) | method | public | `locate(?string $street, ?string $cep, ?string $number = null): array` | 36 | ✓ |

### AddressPoint.php

Fonte: [`src/Application/AddressPoint.php`](../../src/Application/AddressPoint.php) · Referência: [endereco](codigo/application/endereco.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`AddressPoint`](codigo/application/endereco.md#addresspoint) | class | undefined | `` | 18 | ✓ |
| [`AddressPoint::__construct`](codigo/application/endereco.md) | method | public | `__construct(private readonly \Closure $locator, private readonly ReferenceRepository $references)` | 21 | ✓ |
| [`AddressPoint::resolve`](codigo/application/endereco.md#addresspointresolve) | method | public | `resolve(Input $input, array &$values, ?array $current, array $fields, string $what): void` | 32 | ✓ |
| [`AddressPoint::regionOnUpdate`](codigo/application/endereco.md) | method | public | `regionOnUpdate(array $current, array $values, string $latKey, string $lngKey): array` | 73 | ✓ |
| [`AddressPoint::regionAt`](codigo/application/endereco.md) | method | public | `regionAt(float $lat, float $lng): ?int` | 83 | ✓ |
| [`AddressPoint::addressChanged`](codigo/application/endereco.md) | method | private static | `addressChanged(?array $current, array $values, array $keys): bool` | 95 | ✓ |
| [`AddressPoint::keepInsideAddress`](codigo/application/endereco.md) | method | private | `keepInsideAddress(array &$values, array $fields): void` | 110 | ✓ |

### AgentService.php

Fonte: [`src/Application/AgentService.php`](../../src/Application/AgentService.php) · Referência: [agentes](codigo/application/agentes.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`AgentService`](codigo/application/agentes.md#agentservice) | class | undefined | `` | 24 | ✓ |
| [`AgentService::__construct`](codigo/application/agentes.md) | method | public | `__construct( private readonly Database $db, private readonly AgentRepository $agents, private readonly ReferenceRepository $references, private readonly History $history, private readonly AlertService $alerts, private readonly Clock $clock, private readonly AddressPoint $point, )` | 35 | ✓ |
| [`AgentService::create`](codigo/application/agentes.md#agentservicecreate) | method | public | `create(array $data, ?int $actorId, string $source = 'MANUAL'): int` | 46 | ✓ |
| [`AgentService::update`](codigo/application/agentes.md#agentserviceupdate) | method | public | `update(int $id, array $data, int $actorId): void` | 75 | ✓ |
| [`AgentService::changeStatus`](codigo/application/agentes.md#agentservicechangestatus) | method | public | `changeStatus(int $id, string $target, ?int $actorId, string $source = 'MANUAL', ?string $note = null): void` | 96 | ✓ |
| [`AgentService::archive`](codigo/application/agentes.md#agentservicearchive) | method | public | `archive(int $id, string $reason, int $actorId): void` | 120 | ✓ |
| [`AgentService::changeSummary`](codigo/application/agentes.md#agentservicechangesummary) | method | public static | `changeSummary(array $fields): string` | 144 | ✓ |
| [`AgentService::validate`](codigo/application/agentes.md#agentservicevalidate) | method | private | `validate(Input $input, ?array $current = null): array` | 158 | ✓ |

### AlertService.php

Fonte: [`src/Application/AlertService.php`](../../src/Application/AlertService.php) · Referência: [alertas](codigo/application/alertas.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`AlertService`](codigo/application/alertas.md#alertservice) | class | undefined | `` | 26 | ✓ |
| [`AlertService::__construct`](codigo/application/alertas.md) | method | public | `__construct( private readonly Database $db, private readonly AlertRepository $alerts, private readonly EquipmentRepository $equipment, private readonly AgentRepository $agents, private readonly MaintenanceRepository $maintenance, private readonly UserRepository $users, private readonly Clock $clock, )` | 28 | ✓ |
| [`AlertService::reconcile`](codigo/application/alertas.md#alertservicereconcile) | method | public | `reconcile(): array` | 44 | ✓ |
| [`AlertService::acknowledge`](codigo/application/alertas.md#alertserviceacknowledge) | method | public | `acknowledge(int $alertId, int $userId, ?string $note): void` | 68 | ✓ |
| [`AlertService::assign`](codigo/application/alertas.md#alertserviceassign) | method | public | `assign(int $alertId, array $data, int $userId): void` | 85 | ✓ |
| [`AlertService::recordFollowUp`](codigo/application/alertas.md#alertservicerecordfollowup) | method | public | `recordFollowUp(int $alertId, array $data, int $userId): void` | 111 | ✓ |
| [`AlertService::pending`](codigo/application/alertas.md#alertservicepending) | method | private | `pending(int $alertId, mixed $version): array` | 144 | ✓ |
| [`AlertService::eligible`](codigo/application/alertas.md) | method | private | `eligible(int $userId): void` | 160 | ✓ |
| [`AlertService::accountId`](codigo/application/alertas.md) | method | private | `accountId(mixed $value, array &$errors): ?int` | 169 | ✓ |
| [`AlertService::nextCheck`](codigo/application/alertas.md) | method | private | `nextCheck(mixed $value, array &$errors): ?string` | 185 | ✓ |

### AnalysisService.php

Fonte: [`src/Application/AnalysisService.php`](../../src/Application/AnalysisService.php) · Referência: [analise-preventiva](codigo/application/analise-preventiva.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`AnalysisService`](codigo/application/analise-preventiva.md) | class | undefined | `` | 30 | ✓ |
| [`AnalysisService::__construct`](codigo/application/analise-preventiva.md) | method | public | `__construct( private readonly Database $db, private readonly AnalysisRepository $runs, private readonly EquipmentRepository $equipment, private readonly EventRepository $events, private readonly MaintenanceRepository $orders, private readonly Clock $clock, )` | 51 | ✓ |
| [`AnalysisService::run`](codigo/application/analise-preventiva.md#analysisservicerun) | method | public | `run(?int $actorId): array` | 61 | ✓ |
| [`AnalysisService::load`](codigo/application/analise-preventiva.md#analysisserviceload) | method | public | `load(DateTimeImmutable $until): array` | 132 | ✓ |
| [`AnalysisService::features`](codigo/application/analise-preventiva.md#analysisservicefeatures) | method | public | `features(array $e, DateTimeImmutable $cutoff): ?array` | 147 | ✓ |
| [`AnalysisService::intervalAt`](codigo/application/analise-preventiva.md#analysisserviceintervalat) | method | private | `intervalAt(array $e, int $t): ?int` | 206 | ✓ |
| [`AnalysisService::pairs`](codigo/application/analise-preventiva.md) | method | private | `pairs(array $equipment, array $cutoffs, int $threshold, bool $baseline = false): array` | 222 | ✓ |
| [`AnalysisService::cutoffs`](codigo/application/analise-preventiva.md) | method | private | `cutoffs(DateTimeImmutable $now, int $fromDays, int $toDays): array` | 246 | ✓ |
| [`AnalysisService::index`](codigo/application/analise-preventiva.md#analysisserviceindex) | method | private | `index(array $statusEvents, array $orders, array $intervalChanges): void` | 261 | ✓ |

### AuthService.php

Fonte: [`src/Application/AuthService.php`](../../src/Application/AuthService.php) · Referência: [autenticacao](codigo/application/autenticacao.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`AuthService`](codigo/application/autenticacao.md#authservice) | class | undefined | `` | 21 | ✓ |
| [`AuthService::__construct`](codigo/application/autenticacao.md) | method | public | `__construct( private readonly Database $db, private readonly UserRepository $users, private readonly Clock $clock, )` | 29 | ✓ |
| [`AuthService::hash`](codigo/application/autenticacao.md) | method | public static | `hash(#[\SensitiveParameter] string $password): string` | 35 | ✓ |
| [`AuthService::attempt`](codigo/application/autenticacao.md#authserviceattempt) | method | public | `attempt(string $username, #[\SensitiveParameter] string $password, string $ip): array` | 44 | ✓ |
| [`AuthService::reserve`](codigo/application/autenticacao.md#authservicereserve) | method | private | `reserve(string $userKey, string $ipKey, string $at, string $since): array` | 79 | ✓ |
| [`AuthService::activeUser`](codigo/application/autenticacao.md) | method | public | `activeUser(int $id): ?array` | 104 | ✓ |
| [`AuthService::createUser`](codigo/application/autenticacao.md#authservicecreateuser) | method | public | `createUser(string $username, string $displayName, string $role, #[\SensitiveParameter] ?string $password = null): array` | 120 | ✓ |
| [`AuthService::resetPassword`](codigo/application/autenticacao.md#authserviceresetpassword) | method | public | `resetPassword(int $id, int $actorId): string` | 151 | ✓ |
| [`AuthService::setActive`](codigo/application/autenticacao.md#authservicesetactive) | method | public | `setActive(int $id, bool $active, int $actorId): void` | 166 | ✓ |
| [`AuthService::generatePassword`](codigo/application/autenticacao.md) | method | private static | `generatePassword(): string` | 183 | ✓ |
| [`AuthService::users`](codigo/application/autenticacao.md) | method | public | `users(): array` | 189 | ✓ |

### AuthenticationFailed.php

Fonte: [`src/Application/AuthenticationFailed.php`](../../src/Application/AuthenticationFailed.php) · Referência: [erros](codigo/erros.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`AuthenticationFailed`](codigo/erros.md) | class | undefined | `` | 8 | ✓ |

### CentralQuery.php

Fonte: [`src/Application/CentralQuery.php`](../../src/Application/CentralQuery.php) · Referência: [central](codigo/application/central.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`CentralQuery`](codigo/application/central.md) | class | undefined | `` | 31 | ✓ |
| [`CentralQuery::__construct`](codigo/application/central.md) | method | public | `__construct( private readonly Database $db, private readonly EquipmentRepository $equipment, private readonly AgentRepository $agents, private readonly MaintenanceRepository $maintenance, private readonly AlertRepository $alerts, private readonly MissionRepository $missions, private readonly EventRepository $events, private readonly MetaRepository $meta, private readonly ReferenceRepository $references, private readonly Clock $clock, private readonly int $simIntervalSeconds = 5, )` | 33 | ✓ |
| [`CentralQuery::snapshot`](codigo/application/central.md#centralquerysnapshot) | method | public | `snapshot(Filters $filters): array` | 48 | ✓ |
| [`CentralQuery::equipmentResource`](codigo/application/central.md#centralqueryequipmentresource) | method | private | `equipmentResource(array $e, array $orders, array $alertReasons, \DateTimeImmutable $now): array` | 137 | ✓ |
| [`CentralQuery::agentResource`](codigo/application/central.md#centralqueryagentresource) | method | private | `agentResource(array $g, array $alertReasons, \DateTimeImmutable $now, ?array $pending = null, ?array $lastAction = null, ?int $missionId = null): array` | 202 | ✓ |
| [`CentralQuery::withAlertReasons`](codigo/application/central.md#centralquerywithalertreasons) | method | private static | `withAlertReasons(array $attention, array $alertReasons): array` | 253 | ✓ |
| [`CentralQuery::place`](codigo/application/central.md) | method | private static | `place(?string $street, ?string $ref): ?string` | 265 | ✓ |
| [`CentralQuery::matches`](codigo/application/central.md) | method | private static | `matches(array $r, string $text): bool` | 277 | ✓ |
| [`CentralQuery::fold`](codigo/application/central.md) | method | private static | `fold(string $text): string` | 284 | ✓ |
| [`CentralQuery::regionSummary`](codigo/application/central.md) | method | private | `regionSummary(array $resources, array $alerts): array` | 297 | ✓ |
| [`CentralQuery::maintenanceLists`](codigo/application/central.md) | method | private | `maintenanceLists(array $orders): array` | 330 | ✓ |

### CepAddress.php

Fonte: [`src/Application/CepAddress.php`](../../src/Application/CepAddress.php) · Referência: [endereco](codigo/application/endereco.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`CepAddress`](codigo/application/endereco.md#cepaddress) | class | undefined | `` | 8 | ✓ |
| [`CepAddress::__construct`](codigo/application/endereco.md) | method | public | `__construct( public string $cep, public string $street, public string $district, public string $city, public string $uf, )` | 10 | ✓ |
| [`CepAddress::isInMarilia`](codigo/application/endereco.md) | method | public | `isInMarilia(): bool` | 19 | ✓ |

### CepLookup.php

Fonte: [`src/Application/CepLookup.php`](../../src/Application/CepLookup.php) · Referência: [endereco](codigo/application/endereco.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`CepLookup`](codigo/application/endereco.md#ceplookup-interface) | interface | undefined | `` | 11 | ✓ |
| [`CepLookup::find`](codigo/application/endereco.md) | method | public | `find(string $cep): ?CepAddress` | 18 | ✓ |

### DuplicateEvent.php

Fonte: [`src/Application/DuplicateEvent.php`](../../src/Application/DuplicateEvent.php) · Referência: [erros](codigo/erros.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`DuplicateEvent`](codigo/erros.md) | class | undefined | `` | 8 | ✓ |

### EquipmentService.php

Fonte: [`src/Application/EquipmentService.php`](../../src/Application/EquipmentService.php) · Referência: [equipamentos](codigo/application/equipamentos.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`EquipmentService`](codigo/application/equipamentos.md#equipmentservice) | class | undefined | `` | 21 | ✓ |
| [`EquipmentService::__construct`](codigo/application/equipamentos.md) | method | public | `__construct( private readonly Database $db, private readonly EquipmentRepository $equipment, private readonly ReferenceRepository $references, private readonly MaintenanceRepository $orders, private readonly History $history, private readonly AlertService $alerts, private readonly Clock $clock, private readonly AddressPoint $point, )` | 29 | ✓ |
| [`EquipmentService::create`](codigo/application/equipamentos.md#equipmentservicecreate) | method | public | `create(array $data, ?int $actorId, string $source = 'MANUAL'): int` | 41 | ✓ |
| [`EquipmentService::update`](codigo/application/equipamentos.md#equipmentserviceupdate) | method | public | `update(int $id, array $data, int $actorId): void` | 68 | ✓ |
| [`EquipmentService::pendingChanges`](codigo/application/equipamentos.md#equipmentservicependingchanges) | method | public | `pendingChanges(int $id, array $data): array` | 99 | ✓ |
| [`EquipmentService::changeStatus`](codigo/application/equipamentos.md#equipmentservicechangestatus) | method | public | `changeStatus(int $id, string $target, string $note, int $actorId): void` | 112 | ✓ |
| [`EquipmentService::applyStatus`](codigo/application/equipamentos.md#equipmentserviceapplystatus) | method | public | `applyStatus(array $current, EquipmentStatus $target, string $note, ?int $actorId, string $source): void` | 137 | ✓ |
| [`EquipmentService::archive`](codigo/application/equipamentos.md#equipmentservicearchive) | method | public | `archive(int $id, string $reason, int $actorId, bool $cancelScheduled = false): array` | 178 | ✓ |
| [`EquipmentService::validate`](codigo/application/equipamentos.md#equipmentservicevalidate) | method | private | `validate(Input $input, EquipmentStatus $status, ?array $current = null): array` | 220 | ✓ |
| [`EquipmentService::persist`](codigo/application/equipamentos.md) | method | private | `persist(callable $write): mixed` | 279 | ✓ |

### EventIngestor.php

Fonte: [`src/Application/EventIngestor.php`](../../src/Application/EventIngestor.php) · Referência: [ingestao](codigo/application/ingestao.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`EventIngestor`](codigo/application/ingestao.md) | class | undefined | `` | 25 | ✓ |
| [`EventIngestor::__construct`](codigo/application/ingestao.md) | method | public | `__construct( private readonly Database $db, private readonly EquipmentRepository $equipment, private readonly AgentRepository $agents, private readonly EventRepository $events, private readonly MetaRepository $meta, private readonly Clock $clock, )` | 31 | ✓ |
| [`EventIngestor::equipmentStatus`](codigo/application/ingestao.md#eventingestorequipmentstatus) | method | public | `equipmentStatus(string $uid, int $equipmentId, EquipmentStatus $reported, \DateTimeImmutable $occurredAt, string $detail, string $source = 'SIMULADOR', ?int $actor = null): string` | 45 | ✓ |
| [`EventIngestor::position`](codigo/application/ingestao.md#eventingestorposition) | method | public | `position(string $uid, string $kind, int $resourceId, string $lat, string $lng, \DateTimeImmutable $occurredAt, bool $keepHistory, string $source = 'SIMULADOR', ?int $actor = null, string $detail = ''): string` | 96 | ✓ |
| [`EventIngestor::duplicate`](codigo/application/ingestao.md) | method | private | `duplicate(): string` | 128 | ✓ |

### Filters.php

Fonte: [`src/Application/Filters.php`](../../src/Application/Filters.php) · Referência: [validacao-e-filtros](codigo/application/validacao-e-filtros.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`Filters`](codigo/application/validacao-e-filtros.md#filters) | class | undefined | `` | 20 | ✓ |
| [`Filters::__construct`](codigo/application/validacao-e-filtros.md) | method | public | `__construct( public array $categories = [], public array $statuses = [], public array $regions = [], public array $agencies = [], public string $text = '', public bool $attention = false, )` | 30 | ✓ |
| [`Filters::fromQuery`](codigo/application/validacao-e-filtros.md) | method | public static | `fromQuery(array $query): self` | 40 | ✓ |
| [`Filters::equipmentTypes`](codigo/application/validacao-e-filtros.md) | method | public | `equipmentTypes(): array` | 59 | ✓ |
| [`Filters::agentRoles`](codigo/application/validacao-e-filtros.md) | method | public | `agentRoles(): array` | 65 | ✓ |
| [`Filters::equipmentStatuses`](codigo/application/validacao-e-filtros.md) | method | public | `equipmentStatuses(): array` | 71 | ✓ |
| [`Filters::agentStatuses`](codigo/application/validacao-e-filtros.md) | method | public | `agentStatuses(): array` | 77 | ✓ |
| [`Filters::includesEquipment`](codigo/application/validacao-e-filtros.md) | method | public | `includesEquipment(): bool` | 82 | ✓ |
| [`Filters::includesAgents`](codigo/application/validacao-e-filtros.md) | method | public | `includesAgents(): bool` | 88 | ✓ |
| [`Filters::isEmpty`](codigo/application/validacao-e-filtros.md) | method | public | `isEmpty(): bool` | 94 | ✓ |
| [`Filters::toArray`](codigo/application/validacao-e-filtros.md) | method | public | `toArray(): array` | 100 | ✓ |
| [`Filters::pick`](codigo/application/validacao-e-filtros.md) | method | private static | `pick(mixed $raw, array $allowed): array` | 110 | ✓ |
| [`Filters::ids`](codigo/application/validacao-e-filtros.md) | method | private static | `ids(mixed $raw): array` | 119 | ✓ |

### FineFilters.php

Fonte: [`src/Application/FineFilters.php`](../../src/Application/FineFilters.php) · Referência: [validacao-e-filtros](codigo/application/validacao-e-filtros.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`FineFilters`](codigo/application/validacao-e-filtros.md#finefilters) | class | undefined | `` | 15 | ✓ |
| [`FineFilters::__construct`](codigo/application/validacao-e-filtros.md) | method | private | `__construct( public readonly string $q = '', public readonly ?string $source = null, public readonly ?string $stage = null, public readonly ?int $agency = null, public readonly ?int $radar = null, public readonly ?int $agent = null, public readonly ?string $document = null, public readonly ?string $payment = null, public readonly ?string $from = null, public readonly ?string $until = null, public readonly string $record = 'ativo', public readonly string $sort = 'ocorrencia', /** Só as que estão "A conferir" (o mesmo critério do resumo; o lembrete conta a partir do dia escolhido). */ public readonly bool $attention = false, )` | 27 | ✓ |
| [`FineFilters::fromQuery`](codigo/application/validacao-e-filtros.md) | method | public static | `fromQuery(array $query): self` | 45 | ✓ |
| [`FineFilters::states`](codigo/application/validacao-e-filtros.md) | method | public | `states(): array` | 96 | ✓ |
| [`FineFilters::utcRange`](codigo/application/validacao-e-filtros.md) | method | public | `utcRange(): array` | 106 | ✓ |
| [`FineFilters::moreCount`](codigo/application/validacao-e-filtros.md) | method | public | `moreCount(): int` | 115 | ✓ |
| [`FineFilters::isEmpty`](codigo/application/validacao-e-filtros.md) | method | public | `isEmpty(): bool` | 121 | ✓ |
| [`FineFilters::toArray`](codigo/application/validacao-e-filtros.md) | method | public | `toArray(): array` | 127 | ✓ |

### FineQuery.php

Fonte: [`src/Application/FineQuery.php`](../../src/Application/FineQuery.php) · Referência: [multas](codigo/application/multas.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`FineQuery`](codigo/application/multas.md#finequery) | class | undefined | `` | 17 | ✓ |
| [`FineQuery::__construct`](codigo/application/multas.md) | method | public | `__construct( private readonly Database $db, private readonly FineRepository $fines, private readonly EquipmentRepository $equipment, private readonly AgentRepository $agents, private readonly Clock $clock, )` | 22 | ✓ |
| [`FineQuery::detail`](codigo/application/multas.md) | method | public | `detail(int $id, int $eventsPage): ?array` | 53 | ✓ |

### FineService.php

Fonte: [`src/Application/FineService.php`](../../src/Application/FineService.php) · Referência: [multas](codigo/application/multas.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`FineService`](codigo/application/multas.md#fineservice) | class | undefined | `` | 26 | ✓ |
| [`FineService::__construct`](codigo/application/multas.md) | method | public | `__construct( private readonly Database $db, private readonly FineRepository $fines, private readonly ReferenceRepository $references, private readonly EquipmentRepository $equipment, private readonly AgentRepository $agents, private readonly Clock $clock, )` | 55 | ✓ |
| [`FineService::newCommandUid`](codigo/application/multas.md) | method | public static | `newCommandUid(): string` | 65 | ✓ |
| [`FineService::formValues`](codigo/application/multas.md) | method | public static | `formValues(array $fine): array` | 76 | ✓ |
| [`FineService::create`](codigo/application/multas.md#fineservicecreate) | method | public | `create(array $data, ?int $actor, string $source = 'MANUAL'): int` | 92 | ✓ |
| [`FineService::update`](codigo/application/multas.md#fineserviceupdate) | method | public | `update(int $id, array $data, ?int $actor, string $source = 'MANUAL'): void` | 120 | ✓ |
| [`FineService::register`](codigo/application/multas.md#fineserviceregister) | method | public | `register(int $id, array $data, ?int $actor, string $source = 'MANUAL'): void` | 192 | ✓ |
| [`FineService::addCheck`](codigo/application/multas.md#fineserviceaddcheck) | method | public | `addCheck(int $id, array $data, ?int $actor, string $source = 'MANUAL'): void` | 228 | ✓ |
| [`FineService::updateProgress`](codigo/application/multas.md#fineserviceupdateprogress) | method | public | `updateProgress(int $id, array $data, ?int $actor, string $source = 'MANUAL'): array` | 326 | ✓ |
| [`FineService::cancel`](codigo/application/multas.md#fineservicecancel) | method | public | `cancel(int $id, array $data, ?int $actor, string $source = 'MANUAL'): void` | 430 | ✓ |
| [`FineService::pendingChanges`](codigo/application/multas.md) | method | public | `pendingChanges(int $id, array $data): array` | 460 | ✓ |
| [`FineService::validate`](codigo/application/multas.md#fineservicevalidate) | method | private | `validate(Input $in): array` | 479 | ✓ |
| [`FineService::lockSources`](codigo/application/multas.md#fineservicelocksources) | method | private | `lockSources(array $f): array` | 600 | ✓ |
| [`FineService::divergence`](codigo/application/multas.md#fineservicedivergence) | method | private | `divergence(array $f, ?array $equipment, ?array $agent): array` | 629 | ✓ |
| [`FineService::snapshot`](codigo/application/multas.md#fineservicesnapshot) | method | private | `snapshot(array $f, ?array $equipment, ?array $agent): array` | 661 | ✓ |
| [`FineService::projection`](codigo/application/multas.md) | method | private | `projection(int $id, int $subjectVersion): array` | 686 | ✓ |
| [`FineService::laterStatus`](codigo/application/multas.md) | method | private | `laterStatus(int $id, int $subjectVersion): ?string` | 698 | ✓ |
| [`FineService::assertFactsNotBefore`](codigo/application/multas.md#fineserviceassertfactsnotbefore) | method | private static | `assertFactsNotBefore(array $f, string $occurredAt): void` | 711 | ✓ |
| [`FineService::lockActive`](codigo/application/multas.md#fineservicelockactive) | method | private | `lockActive(int $id, int $version): array` | 724 | ✓ |
| [`FineService::once`](codigo/application/multas.md#fineserviceonce) | method | private | `once(string $uid, string $hash, ?int $actor, callable $work): mixed` | 743 | ✓ |
| [`FineService::event`](codigo/application/multas.md) | method | private | `event(int $fineId, string $uid, string $hash, string $type, string $source, ?int $actor, string $summary, ?string $reason, array $payload): void` | 772 | ✓ |
| [`FineService::persist`](codigo/application/multas.md) | method | private | `persist(callable $write): mixed` | 783 | ✓ |
| [`FineService::uid`](codigo/application/multas.md) | method | private static | `uid(Input $input): string` | 794 | ✓ |
| [`FineService::fingerprint`](codigo/application/multas.md) | method | private static | `fingerprint(string $operation, int $target, int $version, ?int $actor, array $values): string` | 811 | ✓ |
| [`FineService::assertAuthor`](codigo/application/multas.md#fineserviceassertauthor) | method | private static | `assertAuthor(?int $actor, string $source): void` | 825 | ✓ |
| [`FineService::now`](codigo/application/multas.md) | method | private | `now(): string` | 833 | ✓ |
| [`FineService::fieldList`](codigo/application/multas.md) | method | private static | `fieldList(array $labels): string` | 843 | ✓ |
| [`FineService::br`](codigo/application/multas.md) | method | private static | `br(string $date): string` | 848 | ✓ |

### Geocoder.php

Fonte: [`src/Application/Geocoder.php`](../../src/Application/Geocoder.php) · Referência: [endereco](codigo/application/endereco.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`Geocoder`](codigo/application/endereco.md#geocoder-interface) | interface | undefined | `` | 11 | ✓ |
| [`Geocoder::locate`](codigo/application/endereco.md) | method | public | `locate(string $street): ?array` | 18 | ✓ |

### History.php

Fonte: [`src/Application/History.php`](../../src/Application/History.php) · Referência: [equipamentos](codigo/application/equipamentos.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`History`](codigo/application/equipamentos.md#history) | class | undefined | `` | 12 | ✓ |
| [`History::__construct`](codigo/application/equipamentos.md) | method | public | `__construct(private readonly EventRepository $events, private readonly Clock $clock)` | 14 | ✓ |
| [`History::record`](codigo/application/equipamentos.md) | method | public | `record(string $kind, int $resourceId, string $type, ?int $actor, string $summary, array $payload = [], string $source = 'MANUAL'): void` | 17 | ✓ |
| [`History::diff`](codigo/application/equipamentos.md) | method | public static | `diff(array $before, array $after): array` | 42 | ✓ |

### Input.php

Fonte: [`src/Application/Input.php`](../../src/Application/Input.php) · Referência: [validacao-e-filtros](codigo/application/validacao-e-filtros.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`Input`](codigo/application/validacao-e-filtros.md#input) | class | undefined | `` | 13 | ✓ |
| [`Input::__construct`](codigo/application/validacao-e-filtros.md) | method | public | `__construct(private readonly array $data)` | 22 | ✓ |
| [`Input::raw`](codigo/application/validacao-e-filtros.md) | method | public | `raw(string $key): mixed` | 24 | ✓ |
| [`Input::text`](codigo/application/validacao-e-filtros.md) | method | public | `text(string $key, int $max, bool $required = true): ?string` | 29 | ✓ |
| [`Input::int`](codigo/application/validacao-e-filtros.md) | method | public | `int(string $key, int $min, int $max, bool $required = true): ?int` | 51 | ✓ |
| [`Input::choice`](codigo/application/validacao-e-filtros.md) | method | public | `choice(string $key, array $allowed, bool $required = true): ?string` | 68 | ✓ |
| [`Input::coordinate`](codigo/application/validacao-e-filtros.md) | method | public | `coordinate(string $key, string $axis, bool $required = true): ?string` | 81 | ✓ |
| [`Input::cep`](codigo/application/validacao-e-filtros.md) | method | public | `cep(string $key, bool $required = false): ?string` | 105 | ✓ |
| [`Input::date`](codigo/application/validacao-e-filtros.md) | method | public | `date(string $key, bool $required = true): ?string` | 122 | ✓ |
| [`Input::localDateTime`](codigo/application/validacao-e-filtros.md) | method | public | `localDateTime(string $key, bool $required = true): ?string` | 139 | ✓ |
| [`Input::cents`](codigo/application/validacao-e-filtros.md) | method | public | `cents(string $key, int $min, int $max, bool $required = false): ?int` | 160 | ✓ |
| [`Input::flag`](codigo/application/validacao-e-filtros.md) | method | public | `flag(string $key): bool` | 182 | ✓ |
| [`Input::fail`](codigo/application/validacao-e-filtros.md) | method | public | `fail(string $key, string $message): void` | 187 | ✓ |
| [`Input::merge`](codigo/application/validacao-e-filtros.md) | method | public | `merge(array $errors): void` | 193 | ✓ |
| [`Input::errors`](codigo/application/validacao-e-filtros.md) | method | public | `errors(): array` | 199 | ✓ |
| [`Input::assertValid`](codigo/application/validacao-e-filtros.md) | method | public | `assertValid(): void` | 204 | ✓ |

### MaintenanceService.php

Fonte: [`src/Application/MaintenanceService.php`](../../src/Application/MaintenanceService.php) · Referência: [manutencao](codigo/application/manutencao.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`MaintenanceService`](codigo/application/manutencao.md#maintenanceservice) | class | undefined | `` | 24 | ✓ |
| [`MaintenanceService::__construct`](codigo/application/manutencao.md) | method | public | `__construct( private readonly Database $db, private readonly MaintenanceRepository $orders, private readonly EquipmentRepository $equipment, private readonly EquipmentService $equipmentService, private readonly History $history, private readonly AlertService $alerts, private readonly Clock $clock, )` | 26 | ✓ |
| [`MaintenanceService::open`](codigo/application/manutencao.md#maintenanceserviceopen) | method | public | `open(int $equipmentId, array $data, ?int $actorId, string $source = 'MANUAL'): int` | 37 | ✓ |
| [`MaintenanceService::start`](codigo/application/manutencao.md#maintenanceservicestart) | method | public | `start(int $orderId, ?int $actorId, string $source = 'MANUAL'): void` | 67 | ✓ |
| [`MaintenanceService::complete`](codigo/application/manutencao.md#maintenanceservicecomplete) | method | public | `complete(int $orderId, array $data, ?int $actorId, string $source = 'MANUAL'): ?int` | 101 | ✓ |
| [`MaintenanceService::cancel`](codigo/application/manutencao.md#maintenanceservicecancel) | method | public | `cancel(int $orderId, string $reason, int $actorId): void` | 135 | ✓ |
| [`MaintenanceService::lockOrder`](codigo/application/manutencao.md) | method | private | `lockOrder(int $orderId): array` | 158 | ✓ |
| [`MaintenanceService::alreadyRunning`](codigo/application/manutencao.md) | method | private static | `alreadyRunning(?int $orderId): RuleViolation` | 167 | ✓ |

### NotFound.php

Fonte: [`src/Application/NotFound.php`](../../src/Application/NotFound.php) · Referência: [erros](codigo/erros.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`NotFound`](codigo/erros.md) | class | undefined | `` | 8 | ✓ |

### PlanningService.php

Fonte: [`src/Application/PlanningService.php`](../../src/Application/PlanningService.php) · Referência: [planejamento](codigo/application/planejamento.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`PlanningService`](codigo/application/planejamento.md) | class | undefined | `` | 32 | ✓ |
| [`PlanningService::__construct`](codigo/application/planejamento.md) | method | public | `__construct( private readonly Database $db, private readonly UrbanRepository $urban, private readonly MissionRepository $missions, private readonly AgentRepository $agents, private readonly ReferenceRepository $references, private readonly Clock $clock, )` | 41 | ✓ |
| [`PlanningService::week`](codigo/application/planejamento.md#planningserviceweek) | method | public | `week(?string $method): ?array` | 55 | ✓ |
| [`PlanningService::candidates`](codigo/application/planejamento.md#planningservicecandidates) | method | public | `candidates(array $sector): array` | 82 | ✓ |
| [`PlanningService::confirm`](codigo/application/planejamento.md#planningserviceconfirm) | method | public | `confirm(array $data, int $actorId): int` | 114 | ✓ |
| [`PlanningService::close`](codigo/application/planejamento.md#planningserviceclose) | method | public | `close(int $missionId, string $state, string $reason, int $actorId): void` | 200 | ✓ |
| [`PlanningService::missions`](codigo/application/planejamento.md#planningservicemissions) | method | public | `missions(): array` | 224 | ✓ |
| [`PlanningService::replay`](codigo/application/planejamento.md#planningservicereplay) | method | public | `replay(?string $week, ?string $method, bool $reveal): ?array` | 247 | ✓ |
| [`PlanningService::replayTimeline`](codigo/application/planejamento.md#planningservicereplaytimeline) | method | private | `replayTimeline(int $runId, string $method, int $last): array` | 312 | ✓ |
| [`PlanningService::replayScore`](codigo/application/planejamento.md#planningservicereplayscore) | method | private static | `replayScore(array $timeline): array` | 348 | ✓ |
| [`PlanningService::ordered`](codigo/application/planejamento.md#planningserviceordered) | method | private static | `ordered(array $sectors, string $method): array` | 370 | ✓ |
| [`PlanningService::candidate`](codigo/application/planejamento.md#planningservicecandidate) | method | private static | `candidate(array $agent, array $sector, DateTimeImmutable $now): array` | 413 | ✓ |
| [`PlanningService::places`](codigo/application/planejamento.md) | method | private | `places(array $cell): array` | 437 | ✓ |
| [`PlanningService::placeLabel`](codigo/application/planejamento.md) | method | public static | `placeLabel(array $places): string` | 449 | ✓ |
| [`PlanningService::method`](codigo/application/planejamento.md) | method | private static | `method(?string $method, array $run): string` | 455 | ✓ |
| [`PlanningService::period`](codigo/application/planejamento.md#planningserviceperiod) | method | private static | `period(array $run, string $today): string` | 465 | ✓ |
| [`PlanningService::fmt`](codigo/application/planejamento.md) | method | private static | `fmt(float $value): string` | 474 | ✓ |
| [`PlanningService::brDate`](codigo/application/planejamento.md) | method | private static | `brDate(string $ymd): string` | 479 | ✓ |
| [`PlanningService::addDays`](codigo/application/planejamento.md) | method | private static | `addDays(string $date, int $days): string` | 484 | ✓ |

### ProviderUnavailable.php

Fonte: [`src/Application/ProviderUnavailable.php`](../../src/Application/ProviderUnavailable.php) · Referência: [erros](codigo/erros.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`ProviderUnavailable`](codigo/erros.md) | class | undefined | `` | 8 | ✓ |

### UrbanImporter.php

Fonte: [`src/Application/UrbanImporter.php`](../../src/Application/UrbanImporter.php) · Referência: [importacao-urbana](codigo/application/importacao-urbana.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`UrbanImporter`](codigo/application/importacao-urbana.md) | class | undefined | `` | 25 | ✓ |
| [`UrbanImporter::__construct`](codigo/application/importacao-urbana.md) | method | public | `__construct(private readonly Database $db, private readonly UrbanRepository $urban, private readonly Clock $clock)` | 42 | ✓ |
| [`UrbanImporter::import`](codigo/application/importacao-urbana.md#urbanimporterimport) | method | public | `import(string $runDir, string $sectorsFile, string $manifestFile, ?string $gridFile = null): array` | 50 | ✓ |
| [`UrbanImporter::addCells`](codigo/application/importacao-urbana.md) | method | private | `addCells(int $runId, array $cells): int` | 85 | ✓ |
| [`UrbanImporter::cells`](codigo/application/importacao-urbana.md#urbanimportercells) | method | public static | `cells(string $path, array $bundle): array` | 112 | ✓ |
| [`UrbanImporter::bounds`](codigo/application/importacao-urbana.md) | method | public static | `bounds(array $sectors): array` | 159 | ✓ |
| [`UrbanImporter::cellRows`](codigo/application/importacao-urbana.md#urbanimportercellrows) | method | public static | `cellRows(array $c13, array $c52, array $bounds): array` | 173 | ✓ |
| [`UrbanImporter::read`](codigo/application/importacao-urbana.md#urbanimporterread) | method | public static | `read(string $runDir, string $sectorsFile, string $manifestFile): array` | 193 | ✓ |
| [`UrbanImporter::decode`](codigo/application/importacao-urbana.md) | method | private static | `decode(string $bytes, string $name): array` | 241 | ✓ |
| [`UrbanImporter::manifest`](codigo/application/importacao-urbana.md#urbanimportermanifest) | method | private static | `manifest(array $m): array` | 268 | ✓ |
| [`UrbanImporter::sectors`](codigo/application/importacao-urbana.md#urbanimportersectors) | method | private static | `sectors(array $g, array $bounds): array` | 310 | ✓ |
| [`UrbanImporter::forecast`](codigo/application/importacao-urbana.md#urbanimporterforecast) | method | private static | `forecast(array $d, array $manifest): array` | 375 | ✓ |
| [`UrbanImporter::evaluation`](codigo/application/importacao-urbana.md#urbanimporterevaluation) | method | private static | `evaluation(array $d, array $run): array` | 454 | ✓ |
| [`UrbanImporter::replay`](codigo/application/importacao-urbana.md#urbanimporterreplay) | method | private static | `replay(array $d, array $run, array $evaluation): array` | 504 | ✓ |
| [`UrbanImporter::model`](codigo/application/importacao-urbana.md) | method | private static | `model(array $d, array $run, array $evaluation): void` | 560 | ✓ |
| [`UrbanImporter::status`](codigo/application/importacao-urbana.md) | method | private static | `status(array $d, array $run): void` | 574 | ✓ |
| [`UrbanImporter::modelCoefficients`](codigo/application/importacao-urbana.md) | method | private static | `modelCoefficients(array $m, string $p, float $alpha): array` | 587 | ✓ |
| [`UrbanImporter::evidence`](codigo/application/importacao-urbana.md) | method | private static | `evidence(array $e, string $p, string $before): array` | 619 | ✓ |
| [`UrbanImporter::fail`](codigo/application/importacao-urbana.md) | method | private static | `fail(string $path, string $message): never` | 641 | ✓ |
| [`UrbanImporter::same`](codigo/application/importacao-urbana.md) | method | private static | `same(array $d, string $key, mixed $expected, string $p): void` | 647 | ✓ |
| [`UrbanImporter::object`](codigo/application/importacao-urbana.md) | method | private static | `object(array $d, string $key, string $p): array` | 658 | ✓ |
| [`UrbanImporter::items`](codigo/application/importacao-urbana.md) | method | private static | `items(array $d, string $key, string $p, int $min, int $max): array` | 672 | ✓ |
| [`UrbanImporter::text`](codigo/application/importacao-urbana.md) | method | private static | `text(array $d, string $key, int $max, string $p): string` | 682 | ✓ |
| [`UrbanImporter::strings`](codigo/application/importacao-urbana.md) | method | private static | `strings(array $d, string $key, string $p): void` | 693 | ✓ |
| [`UrbanImporter::choice`](codigo/application/importacao-urbana.md) | method | private static | `choice(array $d, string $key, array $allowed, string $p): string` | 704 | ✓ |
| [`UrbanImporter::date`](codigo/application/importacao-urbana.md) | method | private static | `date(array $d, string $key, string $p): string` | 715 | ✓ |
| [`UrbanImporter::hash`](codigo/application/importacao-urbana.md) | method | private static | `hash(array $d, string $key, string $p): string` | 727 | ✓ |
| [`UrbanImporter::int`](codigo/application/importacao-urbana.md) | method | private static | `int(array $d, string $key, string $p, int $min = 0, int $max = PHP_INT_MAX): int` | 738 | ✓ |
| [`UrbanImporter::number`](codigo/application/importacao-urbana.md) | method | private static | `number(array $d, string $key, string $p, bool $signed = false): float` | 753 | ✓ |
| [`UrbanImporter::isNumber`](codigo/application/importacao-urbana.md) | method | private static | `isNumber(mixed $v): bool` | 763 | ✓ |
| [`UrbanImporter::close`](codigo/application/importacao-urbana.md) | method | private static | `close(float $a, float $b): bool` | 768 | ✓ |
| [`UrbanImporter::addDays`](codigo/application/importacao-urbana.md) | method | private static | `addDays(string $date, int $days): string` | 773 | ✓ |
| [`UrbanImporter::days`](codigo/application/importacao-urbana.md) | method | private static | `days(string $from, string $to): int` | 778 | ✓ |

### UrbanUpdater.php

Fonte: [`src/Application/UrbanUpdater.php`](../../src/Application/UrbanUpdater.php) · Referência: [atualizacao-urbana](codigo/application/atualizacao-urbana.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`UrbanUpdater`](codigo/application/atualizacao-urbana.md) | class | undefined | `` | 29 | ✓ |
| [`UrbanUpdater::__construct`](codigo/application/atualizacao-urbana.md) | method | public | `__construct(private readonly Database $db, private readonly UrbanRepository $urban, private readonly Clock $clock, private readonly MetaRepository $meta)` | 40 | ✓ |
| [`UrbanUpdater::update`](codigo/application/atualizacao-urbana.md#urbanupdaterupdate) | method | public | `update(string $url, string $workDir, ?string $localZip = null, bool $forceDownload = false, ?string $week = null): array` | 48 | ✓ |
| [`UrbanUpdater::download`](codigo/application/atualizacao-urbana.md#urbanupdaterdownload) | method | public | `download(string $url, string $workDir): string` | 75 | ✓ |
| [`UrbanUpdater::ingestDownloaded`](codigo/application/atualizacao-urbana.md#urbanupdateringestdownloaded) | method | public | `ingestDownloaded(string $zipPath, string $source): string` | 110 | ✓ |
| [`UrbanUpdater::ingest`](codigo/application/atualizacao-urbana.md#urbanupdateringest) | method | public | `ingest(string $zipPath, string $source): string` | 119 | ✓ |
| [`UrbanUpdater::forecast`](codigo/application/atualizacao-urbana.md#urbanupdaterforecast) | method | public | `forecast(?string $asOf = null): array` | 233 | ✓ |
| [`UrbanUpdater::addDays`](codigo/application/atualizacao-urbana.md) | method | private static | `addDays(string $date, int $days): string` | 308 | ✓ |
| [`UrbanUpdater::days`](codigo/application/atualizacao-urbana.md) | method | private static | `days(string $from, string $to): int` | 313 | ✓ |

## src/Cli

### Console.php

Fonte: [`src/Cli/Console.php`](../../src/Cli/Console.php) · Referência: [console](codigo/cli/console.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`Console`](codigo/cli/console.md) | class | undefined | `` | 15 | ✓ |
| [`Console::__construct`](codigo/cli/console.md) | method | public | `__construct(private readonly Services $services)` | 19 | ✓ |
| [`Console::run`](codigo/cli/console.md#consolerun) | method | public | `run(array $args): int` | 22 | ✓ |
| [`Console::migrate`](codigo/cli/console.md) | method | private | `migrate(): int` | 54 | ✓ |
| [`Console::seed`](codigo/cli/console.md) | method | private | `seed(array $options): int` | 66 | ✓ |
| [`Console::seedFines`](codigo/cli/console.md) | method | private | `seedFines(): int` | 101 | ✓ |
| [`Console::createUser`](codigo/cli/console.md) | method | private | `createUser(array $options): int` | 134 | ✓ |
| [`Console::simTick`](codigo/cli/console.md) | method | private | `simTick(): int` | 146 | ✓ |
| [`Console::exclusiveTick`](codigo/cli/console.md#consoleexclusivetick) | method | private | `exclusiveTick(): ?array` | 160 | ✓ |
| [`Console::simRun`](codigo/cli/console.md) | method | private | `simRun(array $options): int` | 179 | ✓ |
| [`Console::analysis`](codigo/cli/console.md) | method | private | `analysis(): int` | 201 | ✓ |
| [`Console::urbanImport`](codigo/cli/console.md) | method | private | `urbanImport(array $options): int` | 210 | ✓ |
| [`Console::urbanUpdate`](codigo/cli/console.md) | method | private | `urbanUpdate(array $options): int` | 228 | ✓ |
| [`Console::help`](codigo/cli/console.md) | method | private | `help(): int` | 244 | ✓ |
| [`Console::options`](codigo/cli/console.md) | method | private static | `options(array $args): array` | 284 | ✓ |

## src

### Config.php

Fonte: [`src/Config.php`](../../src/Config.php) · Referência: [configuracao-e-servicos](codigo/raiz/configuracao-e-servicos.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`Config`](codigo/raiz/configuracao-e-servicos.md#config) | class | undefined | `` | 11 | ✓ |
| [`Config::__construct`](codigo/raiz/configuracao-e-servicos.md) | method | public | `__construct(private array $values)` | 17 | ✓ |
| [`Config::load`](codigo/raiz/configuracao-e-servicos.md) | method | public static | `load(string $projectRoot): self` | 19 | ✓ |
| [`Config::parseDotEnv`](codigo/raiz/configuracao-e-servicos.md) | method | public static | `parseDotEnv(string $contents): array` | 30 | ✓ |
| [`Config::with`](codigo/raiz/configuracao-e-servicos.md) | method | public | `with(array $overrides): self` | 51 | ✓ |
| [`Config::without`](codigo/raiz/configuracao-e-servicos.md) | method | public | `without(array $keys): self` | 57 | ✓ |
| [`Config::get`](codigo/raiz/configuracao-e-servicos.md) | method | public | `get(string $key, string $default = ''): string` | 62 | ✓ |
| [`Config::bool`](codigo/raiz/configuracao-e-servicos.md) | method | public | `bool(string $key, bool $default = false): bool` | 77 | ✓ |
| [`Config::int`](codigo/raiz/configuracao-e-servicos.md) | method | public | `int(string $key, int $default): int` | 86 | ✓ |
| [`Config::environment`](codigo/raiz/configuracao-e-servicos.md) | method | public | `environment(): string` | 93 | ✓ |
| [`Config::isProduction`](codigo/raiz/configuracao-e-servicos.md) | method | public | `isProduction(): bool` | 98 | ✓ |
| [`Config::basePath`](codigo/raiz/configuracao-e-servicos.md) | method | public | `basePath(): string` | 104 | ✓ |
| [`Config::cepLookupUrl`](codigo/raiz/configuracao-e-servicos.md) | method | public | `cepLookupUrl(): string` | 115 | ✓ |
| [`Config::geocoderUrl`](codigo/raiz/configuracao-e-servicos.md) | method | public | `geocoderUrl(): string` | 121 | ✓ |
| [`Config::httpsUrl`](codigo/raiz/configuracao-e-servicos.md) | method | private | `httpsUrl(string $key, string $default): string` | 126 | ✓ |

## src/Demo

### DemoScenario.php

Fonte: [`src/Demo/DemoScenario.php`](../../src/Demo/DemoScenario.php) · Referência: [cenario-e-carga](codigo/demo/cenario-e-carga.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`DemoScenario`](codigo/demo/cenario-e-carga.md#demoscenario) | class | undefined | `` | 17 | ✓ |
| [`DemoScenario::profile`](codigo/demo/cenario-e-carga.md) | method | public static | `profile(?string $value): string` | 49 | ✓ |
| [`DemoScenario::agencies`](codigo/demo/cenario-e-carga.md) | method | public static | `agencies(): array` | 60 | ✓ |
| [`DemoScenario::regions`](codigo/demo/cenario-e-carga.md) | method | public static | `regions(): array` | 76 | ✓ |
| [`DemoScenario::equipment`](codigo/demo/cenario-e-carga.md) | method | public static | `equipment(string $profile = self::COMPACT): array` | 111 | ✓ |
| [`DemoScenario::allEquipment`](codigo/demo/cenario-e-carga.md) | method | private static | `allEquipment(): array` | 119 | ✓ |
| [`DemoScenario::bases`](codigo/demo/cenario-e-carga.md) | method | public static | `bases(): array` | 215 | ✓ |
| [`DemoScenario::agents`](codigo/demo/cenario-e-carga.md) | method | public static | `agents(string $profile = self::COMPACT): array` | 232 | ✓ |
| [`DemoScenario::allAgents`](codigo/demo/cenario-e-carga.md) | method | private static | `allAgents(): array` | 240 | ✓ |
| [`DemoScenario::fragility`](codigo/demo/cenario-e-carga.md) | method | public static | `fragility(?string $serial, string $code): float` | 268 | ✓ |

### DemoSeeder.php

Fonte: [`src/Demo/DemoSeeder.php`](../../src/Demo/DemoSeeder.php) · Referência: [cenario-e-carga](codigo/demo/cenario-e-carga.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`DemoSeeder`](codigo/demo/cenario-e-carga.md#demoseeder) | class | undefined | `` | 25 | ✓ |
| [`DemoSeeder::__construct`](codigo/demo/cenario-e-carga.md) | method | public | `__construct(private readonly Services $services, private readonly int $seed, private readonly string $profile = DemoScenario::COMPACT)` | 39 | ✓ |
| [`DemoSeeder::run`](codigo/demo/cenario-e-carga.md#demoseederrun) | method | public | `run(bool $wipeFirst): array` | 42 | ✓ |
| [`DemoSeeder::wipe`](codigo/demo/cenario-e-carga.md#demoseederwipe) | method | private | `wipe(): void` | 97 | ✓ |
| [`DemoSeeder::seedEquipment`](codigo/demo/cenario-e-carga.md) | method | private | `seedEquipment(array $item, array $agencyIds, array $regionIds): array` | 113 | ✓ |
| [`DemoSeeder::history`](codigo/demo/cenario-e-carga.md#demoseederhistory) | method | private | `history(array $item, int $days, int $stopDaysAgo): array` | 255 | ✓ |
| [`DemoSeeder::seedAgent`](codigo/demo/cenario-e-carga.md) | method | private | `seedAgent(array $agent, array $agencyIds, array $regionIds): int` | 350 | ✓ |
| [`DemoSeeder::statusEvent`](codigo/demo/cenario-e-carga.md) | method | private | `statusEvent(string $from, string $to, DateTimeImmutable $at, string $reason): array` | 381 | ✓ |
| [`DemoSeeder::near`](codigo/demo/cenario-e-carga.md) | method | private | `near(float $lat, float $lng, float $spread): array` | 389 | ✓ |
| [`DemoSeeder::failureReason`](codigo/demo/cenario-e-carga.md) | method | private | `failureReason(string $type): string` | 397 | ✓ |

### FineScenario.php

Fonte: [`src/Demo/FineScenario.php`](../../src/Demo/FineScenario.php) · Referência: [multas-de-demonstracao](codigo/demo/multas-de-demonstracao.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`FineScenario`](codigo/demo/multas-de-demonstracao.md) | class | undefined | `` | 17 | ✓ |
| [`FineScenario::uid`](codigo/demo/multas-de-demonstracao.md) | method | private static | `uid(string $key): string` | 52 | ✓ |
| [`FineScenario::volumeRelato`](codigo/demo/multas-de-demonstracao.md) | method | private static | `volumeRelato(int $i, bool $radar, ?string $kind): string` | 58 | ✓ |
| [`FineScenario::upgrade`](codigo/demo/multas-de-demonstracao.md#finescenarioupgrade) | method | public static | `upgrade(Services $s): int` | 70 | ✓ |
| [`FineScenario::upgradeFromV1`](codigo/demo/multas-de-demonstracao.md#finescenarioupgradefromv1) | method | private static | `upgradeFromV1(Services $s): int` | 80 | ✓ |
| [`FineScenario::upgradeToV3`](codigo/demo/multas-de-demonstracao.md#finescenarioupgradetov3) | method | private static | `upgradeToV3(Services $s): int` | 115 | ✓ |
| [`FineScenario::seed`](codigo/demo/multas-de-demonstracao.md#finescenarioseed) | method | public static | `seed(Services $s, string $profile): int` | 163 | ✓ |

### Simulator.php

Fonte: [`src/Demo/Simulator.php`](../../src/Demo/Simulator.php) · Referência: [simulador](codigo/demo/simulador.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`Simulator`](codigo/demo/simulador.md) | class | undefined | `` | 29 | ✓ |
| [`Simulator::__construct`](codigo/demo/simulador.md) | method | public | `__construct(private readonly Services $services, private readonly int $seed)` | 35 | ✓ |
| [`Simulator::tick`](codigo/demo/simulador.md#simulatortick) | method | public | `tick(): array` | 38 | ✓ |
| [`Simulator::step`](codigo/demo/simulador.md) | method | private | `step(Randomizer $rng, mixed $lat, mixed $lng, float $baseLat, float $baseLng): array` | 138 | ✓ |

## src/Domain

### AgentRole.php

Fonte: [`src/Domain/AgentRole.php`](../../src/Domain/AgentRole.php) · Referência: [situacoes-e-atualidade](codigo/domain/situacoes-e-atualidade.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`AgentRole`](codigo/domain/situacoes-e-atualidade.md#agentrole) | enum | undefined | `` | 7 | ✓ |
| [`AgentRole::label`](codigo/domain/situacoes-e-atualidade.md) | method | public | `label(): string` | 13 | ✓ |
| [`AgentRole::category`](codigo/domain/situacoes-e-atualidade.md) | method | public | `category(): string` | 23 | ✓ |
| [`AgentRole::fromCategory`](codigo/domain/situacoes-e-atualidade.md) | method | public static | `fromCategory(string $category): ?self` | 28 | ✓ |

### AgentStatus.php

Fonte: [`src/Domain/AgentStatus.php`](../../src/Domain/AgentStatus.php) · Referência: [situacoes-e-atualidade](codigo/domain/situacoes-e-atualidade.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`AgentStatus`](codigo/domain/situacoes-e-atualidade.md#agentstatus) | enum | undefined | `` | 8 | ✓ |
| [`AgentStatus::label`](codigo/domain/situacoes-e-atualidade.md) | method | public | `label(): string` | 15 | ✓ |
| [`AgentStatus::isActive`](codigo/domain/situacoes-e-atualidade.md) | method | public | `isActive(): bool` | 25 | ✓ |
| [`AgentStatus::isOnShift`](codigo/domain/situacoes-e-atualidade.md) | method | public | `isOnShift(): bool` | 31 | ✓ |
| [`AgentStatus::glyph`](codigo/domain/situacoes-e-atualidade.md) | method | public | `glyph(): string` | 36 | ✓ |

### AlertFollowUp.php

Fonte: [`src/Domain/AlertFollowUp.php`](../../src/Domain/AlertFollowUp.php) · Referência: [indicadores-e-alertas](codigo/domain/indicadores-e-alertas.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`AlertFollowUp`](codigo/domain/indicadores-e-alertas.md#alertfollowup) | class | undefined | `` | 14 | ✓ |
| [`AlertFollowUp::nextAction`](codigo/domain/indicadores-e-alertas.md#alertfollowupnextaction) | method | public static | `nextAction(array $agent, ?array $alert, ?array $last, DateTimeImmutable $now): array` | 41 | ✓ |
| [`AlertFollowUp::lastAction`](codigo/domain/indicadores-e-alertas.md) | method | public static | `lastAction(array $followUps): ?array` | 77 | ✓ |
| [`AlertFollowUp::say`](codigo/domain/indicadores-e-alertas.md) | method | private static | `say(string $tone, string $what, string $detail): array` | 85 | ✓ |

### AlertRules.php

Fonte: [`src/Domain/AlertRules.php`](../../src/Domain/AlertRules.php) · Referência: [indicadores-e-alertas](codigo/domain/indicadores-e-alertas.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`AlertRules`](codigo/domain/indicadores-e-alertas.md#alertrules) | class | undefined | `` | 15 | ✓ |
| [`AlertRules::conditions`](codigo/domain/indicadores-e-alertas.md#alertrulesconditions) | method | public static | `conditions(array $equipment, array $agents, array $openOrders, DateTimeImmutable $now): array` | 23 | ✓ |
| [`AlertRules::resolutionReason`](codigo/domain/indicadores-e-alertas.md#alertrulesresolutionreason) | method | public static | `resolutionReason(string $kind, string $reason, ?array $agent): string` | 78 | ✓ |

### AttentionModel.php

Fonte: [`src/Domain/AttentionModel.php`](../../src/Domain/AttentionModel.php) · Referência: [atencao-preventiva](codigo/domain/atencao-preventiva.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`AttentionModel`](codigo/domain/atencao-preventiva.md) | class | undefined | `` | 25 | ✓ |
| [`AttentionModel::assess`](codigo/domain/atencao-preventiva.md#attentionmodelassess) | method | public static | `assess(array $f, int $highThreshold): array` | 40 | ✓ |
| [`AttentionModel::metrics`](codigo/domain/atencao-preventiva.md#attentionmodelmetrics) | method | public static | `metrics(array $pairs): array` | 106 | ✓ |

### Clock.php

Fonte: [`src/Domain/Clock.php`](../../src/Domain/Clock.php) · Referência: [tempo](codigo/domain/tempo.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`Clock`](codigo/domain/tempo.md#clock) | class | undefined | `` | 13 | ✓ |
| [`Clock::__construct`](codigo/domain/tempo.md) | method | public | `__construct(?DateTimeImmutable $fixed = null)` | 17 | ✓ |
| [`Clock::now`](codigo/domain/tempo.md) | method | public | `now(): DateTimeImmutable` | 22 | ✓ |
| [`Clock::set`](codigo/domain/tempo.md) | method | public | `set(DateTimeImmutable $instant): void` | 27 | ✓ |
| [`Clock::advance`](codigo/domain/tempo.md) | method | public | `advance(string $interval): void` | 32 | ✓ |

### EquipmentCatalog.php

Fonte: [`src/Domain/EquipmentCatalog.php`](../../src/Domain/EquipmentCatalog.php) · Referência: [catalogo-de-equipamentos](codigo/domain/catalogo-de-equipamentos.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`EquipmentCatalog`](codigo/domain/catalogo-de-equipamentos.md#equipmentcatalog) | class | undefined | `` | 11 | ✓ |
| [`EquipmentCatalog::all`](codigo/domain/catalogo-de-equipamentos.md) | method | public static | `all(): array` | 17 | ✓ |
| [`EquipmentCatalog::get`](codigo/domain/catalogo-de-equipamentos.md) | method | public static | `get(string $code): ?EquipmentType` | 22 | ✓ |
| [`EquipmentCatalog::build`](codigo/domain/catalogo-de-equipamentos.md) | method | private static | `build(): array` | 28 | ✓ |

### EquipmentStatus.php

Fonte: [`src/Domain/EquipmentStatus.php`](../../src/Domain/EquipmentStatus.php) · Referência: [situacoes-e-atualidade](codigo/domain/situacoes-e-atualidade.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`EquipmentStatus`](codigo/domain/situacoes-e-atualidade.md#equipmentstatus) | enum | undefined | `` | 11 | ✓ |
| [`EquipmentStatus::label`](codigo/domain/situacoes-e-atualidade.md) | method | public | `label(): string` | 20 | ✓ |
| [`EquipmentStatus::isInService`](codigo/domain/situacoes-e-atualidade.md) | method | public | `isInService(): bool` | 33 | ✓ |
| [`EquipmentStatus::isUnavailable`](codigo/domain/situacoes-e-atualidade.md) | method | public | `isUnavailable(): bool` | 39 | ✓ |
| [`EquipmentStatus::isAdministrative`](codigo/domain/situacoes-e-atualidade.md) | method | public | `isAdministrative(): bool` | 48 | ✓ |
| [`EquipmentStatus::expectsSignal`](codigo/domain/situacoes-e-atualidade.md) | method | public | `expectsSignal(): bool` | 54 | ✓ |
| [`EquipmentStatus::allowedTransitions`](codigo/domain/situacoes-e-atualidade.md) | method | public | `allowedTransitions(): array` | 60 | ✓ |
| [`EquipmentStatus::canTransitionTo`](codigo/domain/situacoes-e-atualidade.md) | method | public | `canTransitionTo(self $target): bool` | 72 | ✓ |
| [`EquipmentStatus::maintenanceStartBlocker`](codigo/domain/situacoes-e-atualidade.md#equipmentstatusmaintenancestartblocker) | method | public | `maintenanceStartBlocker(): ?string` | 82 | ✓ |
| [`EquipmentStatus::glyph`](codigo/domain/situacoes-e-atualidade.md) | method | public | `glyph(): string` | 93 | ✓ |

### EquipmentType.php

Fonte: [`src/Domain/EquipmentType.php`](../../src/Domain/EquipmentType.php) · Referência: [catalogo-de-equipamentos](codigo/domain/catalogo-de-equipamentos.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`EquipmentType`](codigo/domain/catalogo-de-equipamentos.md#equipmenttype) | class | undefined | `` | 18 | ✓ |
| [`EquipmentType::__construct`](codigo/domain/catalogo-de-equipamentos.md) | method | public | `__construct( public string $code, public string $label, public string $prefix, public bool $mobile, public string $description, public array $fields, )` | 21 | ✓ |
| [`EquipmentType::shortLabel`](codigo/domain/catalogo-de-equipamentos.md) | method | public | `shortLabel(): string` | 31 | ✓ |
| [`EquipmentType::validateSpecs`](codigo/domain/catalogo-de-equipamentos.md#equipmenttypevalidatespecs) | method | public | `validateSpecs(array $input): array` | 43 | ✓ |
| [`EquipmentType::describe`](codigo/domain/catalogo-de-equipamentos.md) | method | public | `describe(array $specs): array` | 108 | ✓ |
| [`EquipmentType::isRequired`](codigo/domain/catalogo-de-equipamentos.md) | method | private | `isRequired(array $field, array $specs): bool` | 133 | ✓ |

### FineDocumentStatus.php

Fonte: [`src/Domain/FineDocumentStatus.php`](../../src/Domain/FineDocumentStatus.php) · Referência: [multas](codigo/domain/multas.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`FineDocumentStatus`](codigo/domain/multas.md#finedocumentstatus) | class | undefined | `` | 11 | ✓ |
| [`FineDocumentStatus::reviewOn`](codigo/domain/multas.md#finedocumentstatusreviewon) | method | public static | `reviewOn(?string $choice, ?string $date, string $today): ?string` | 38 | ✓ |
| [`FineDocumentStatus::select`](codigo/domain/multas.md#finedocumentstatusselect) | method | public static | `select(array $checks, int $subjectVersion, string $scope): ?array` | 61 | ✓ |
| [`FineDocumentStatus::reminder`](codigo/domain/multas.md#finedocumentstatusreminder) | method | public static | `reminder(array $checks, int $subjectVersion): ?array` | 84 | ✓ |

### FineRules.php

Fonte: [`src/Domain/FineRules.php`](../../src/Domain/FineRules.php) · Referência: [multas](codigo/domain/multas.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`FineRules`](codigo/domain/multas.md#finerules) | class | undefined | `` | 11 | ✓ |
| [`FineRules::consideredSpeed`](codigo/domain/multas.md) | method | public static | `consideredSpeed(int $measured): int` | 79 | ✓ |
| [`FineRules::code`](codigo/domain/multas.md) | method | public static | `code(int $id): string` | 84 | ✓ |
| [`FineRules::idFromCode`](codigo/domain/multas.md) | method | public static | `idFromCode(string $text): ?int` | 90 | ✓ |
| [`FineRules::plate`](codigo/domain/multas.md) | method | public static | `plate(string $text): string` | 101 | ✓ |
| [`FineRules::isPlate`](codigo/domain/multas.md) | method | public static | `isPlate(string $plate): bool` | 107 | ✓ |
| [`FineRules::aitKey`](codigo/domain/multas.md) | method | public static | `aitKey(?string $ait): ?string` | 113 | ✓ |
| [`FineRules::missingForRegistration`](codigo/domain/multas.md#finerulesmissingforregistration) | method | public static | `missingForRegistration(array $f): array` | 124 | ✓ |
| [`FineRules::registrationConflicts`](codigo/domain/multas.md#finerulesregistrationconflicts) | method | public static | `registrationConflicts(array $f): array` | 170 | ✓ |
| [`FineRules::attention`](codigo/domain/multas.md#finerulesattention) | method | public static | `attention(array $f, ?string $today = null): array` | 193 | ✓ |
| [`FineRules::progressWarnings`](codigo/domain/multas.md#finerulesprogresswarnings) | method | public static | `progressWarnings(array $p): array` | 241 | ✓ |
| [`FineRules::brDate`](codigo/domain/multas.md) | method | private static | `brDate(string $date): string` | 257 | ✓ |

### Freshness.php

Fonte: [`src/Domain/Freshness.php`](../../src/Domain/Freshness.php) · Referência: [situacoes-e-atualidade](codigo/domain/situacoes-e-atualidade.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`Freshness`](codigo/domain/situacoes-e-atualidade.md#freshness) | enum | undefined | `` | 13 | ✓ |
| [`Freshness::of`](codigo/domain/situacoes-e-atualidade.md#freshnessof) | method | public static | `of( ?DateTimeImmutable $observedAt, DateTimeImmutable $now, bool $applicable = true, int $currentSeconds = self::SIGNAL_CURRENT, int $staleSeconds = self::SIGNAL_STALE, ): self` | 27 | ✓ |
| [`Freshness::label`](codigo/domain/situacoes-e-atualidade.md) | method | public | `label(): string` | 49 | ✓ |

### Indicators.php

Fonte: [`src/Domain/Indicators.php`](../../src/Domain/Indicators.php) · Referência: [indicadores-e-alertas](codigo/domain/indicadores-e-alertas.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`Indicators`](codigo/domain/indicadores-e-alertas.md#indicators) | class | undefined | `` | 18 | ✓ |
| [`Indicators::compute`](codigo/domain/indicadores-e-alertas.md#indicatorscompute) | method | public static | `compute(array $equipment, array $agents): array` | 25 | ✓ |

### MaintenanceUrgency.php

Fonte: [`src/Domain/MaintenanceUrgency.php`](../../src/Domain/MaintenanceUrgency.php) · Referência: [situacoes-e-atualidade](codigo/domain/situacoes-e-atualidade.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`MaintenanceUrgency`](codigo/domain/situacoes-e-atualidade.md#maintenanceurgency) | enum | undefined | `` | 11 | ✓ |
| [`MaintenanceUrgency::classify`](codigo/domain/situacoes-e-atualidade.md#maintenanceurgencyclassify) | method | public static | `classify(string $priority, ?string $dueDate, string $todayLocal, int $windowDays = self::DEFAULT_WINDOW_DAYS): self` | 20 | ✓ |
| [`MaintenanceUrgency::label`](codigo/domain/situacoes-e-atualidade.md) | method | public | `label(): string` | 38 | ✓ |
| [`MaintenanceUrgency::isUrgent`](codigo/domain/situacoes-e-atualidade.md) | method | public | `isUrgent(): bool` | 48 | ✓ |
| [`MaintenanceUrgency::rank`](codigo/domain/situacoes-e-atualidade.md) | method | public | `rank(): int` | 54 | ✓ |

### NearestRegion.php

Fonte: [`src/Domain/NearestRegion.php`](../../src/Domain/NearestRegion.php) · Referência: [setores-e-regioes](codigo/domain/setores-e-regioes.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`NearestRegion`](codigo/domain/setores-e-regioes.md#nearestregion) | class | undefined | `` | 12 | ✓ |
| [`NearestRegion::sorted`](codigo/domain/setores-e-regioes.md) | method | public static | `sorted(float $lat, float $lng, array $regions): array` | 20 | ✓ |
| [`NearestRegion::of`](codigo/domain/setores-e-regioes.md) | method | public static | `of(float $lat, float $lng, array $regions): ?array` | 32 | ✓ |

### RuleViolation.php

Fonte: [`src/Domain/RuleViolation.php`](../../src/Domain/RuleViolation.php) · Referência: [erros](codigo/erros.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`RuleViolation`](codigo/erros.md) | class | undefined | `` | 8 | ✓ |

### SectorForecast.php

Fonte: [`src/Domain/SectorForecast.php`](../../src/Domain/SectorForecast.php) · Referência: [setores-e-regioes](codigo/domain/setores-e-regioes.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`SectorForecast`](codigo/domain/setores-e-regioes.md#sectorforecast) | class | undefined | `` | 13 | ✓ |
| [`SectorForecast::rank`](codigo/domain/setores-e-regioes.md) | method | public static | `rank(array $values): array` | 28 | ✓ |
| [`SectorForecast::replayMetrics`](codigo/domain/setores-e-regioes.md#sectorforecastreplaymetrics) | method | public static | `replayMetrics(array $rows, string $method): array` | 49 | ✓ |
| [`SectorForecast::distanceToBoxKm`](codigo/domain/setores-e-regioes.md) | method | public static | `distanceToBoxKm(float $lat, float $lng, array $box): float` | 87 | ✓ |
| [`SectorForecast::cellBox`](codigo/domain/setores-e-regioes.md) | method | public static | `cellBox(array $bounds, int $cell): array` | 104 | ✓ |
| [`SectorForecast::cellSector`](codigo/domain/setores-e-regioes.md) | method | public static | `cellSector(int $cell): string` | 114 | ✓ |
| [`SectorForecast::rankCells`](codigo/domain/setores-e-regioes.md) | method | public static | `rankCells(array $values): array` | 125 | ✓ |
| [`SectorForecast::cellOf`](codigo/domain/setores-e-regioes.md) | method | public static | `cellOf(float $lat, float $lng, array $bounds): ?int` | 144 | ✓ |
| [`SectorForecast::cutoff`](codigo/domain/setores-e-regioes.md) | method | public static | `cutoff(string $asOf): string` | 156 | ✓ |
| [`SectorForecast::poissonFeatures`](codigo/domain/setores-e-regioes.md) | method | public static | `poissonFeatures(string $sectorId, int $count13, int $count52, string $asOf): array` | 169 | ✓ |
| [`SectorForecast::poisson`](codigo/domain/setores-e-regioes.md) | method | public static | `poisson(array $model, array $x): float` | 186 | ✓ |

### Time.php

Fonte: [`src/Domain/Time.php`](../../src/Domain/Time.php) · Referência: [tempo](codigo/domain/tempo.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`Time`](codigo/domain/tempo.md#time) | class | undefined | `` | 14 | ✓ |
| [`Time::utc`](codigo/domain/tempo.md) | method | public static | `utc(): DateTimeZone` | 18 | ✓ |
| [`Time::local`](codigo/domain/tempo.md) | method | public static | `local(): DateTimeZone` | 23 | ✓ |
| [`Time::toDb`](codigo/domain/tempo.md) | method | public static | `toDb(DateTimeImmutable $instant): string` | 28 | ✓ |
| [`Time::fromDb`](codigo/domain/tempo.md) | method | public static | `fromDb(mixed $value): ?DateTimeImmutable` | 33 | ✓ |
| [`Time::localDate`](codigo/domain/tempo.md) | method | public static | `localDate(DateTimeImmutable $instant): string` | 44 | ✓ |
| [`Time::short`](codigo/domain/tempo.md) | method | public static | `short(DateTimeImmutable $instant): string` | 50 | ✓ |
| [`Time::elapsed`](codigo/domain/tempo.md) | method | public static | `elapsed(DateTimeImmutable $from, DateTimeImmutable $now): string` | 56 | ✓ |
| [`Time::iso`](codigo/domain/tempo.md) | method | public static | `iso(?DateTimeImmutable $instant): ?string` | 69 | ✓ |

### ValidationFailed.php

Fonte: [`src/Domain/ValidationFailed.php`](../../src/Domain/ValidationFailed.php) · Referência: [erros](codigo/erros.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`ValidationFailed`](codigo/erros.md) | class | undefined | `` | 8 | ✓ |
| [`ValidationFailed::__construct`](codigo/erros.md) | method | public | `__construct(public readonly array $errors, string $message = 'Revise os campos destacados.')` | 11 | ✓ |

## src/Http/Controller

### AddressController.php

Fonte: [`src/Http/Controller/AddressController.php`](../../src/Http/Controller/AddressController.php) · Referência: [controladores](codigo/http/controladores.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`AddressController`](codigo/http/controladores.md#addresscontroller) | class | undefined | `` | 18 | ✓ |
| [`AddressController::cep`](codigo/http/controladores.md) | method | public | `cep(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface` | 21 | ✓ |
| [`AddressController::locate`](codigo/http/controladores.md) | method | public | `locate(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface` | 45 | ✓ |
| [`AddressController::answer`](codigo/http/controladores.md) | method | private | `answer(ResponseInterface $response, int $status, string $cep, string $situation, string $message, string $street = '', string $district = ''): ResponseInterface` | 67 | ✓ |

### AgentController.php

Fonte: [`src/Http/Controller/AgentController.php`](../../src/Http/Controller/AgentController.php) · Referência: [controladores](codigo/http/controladores.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`AgentController`](codigo/http/controladores.md#agentcontroller) | class | undefined | `` | 23 | ✓ |
| [`AgentController::index`](codigo/http/controladores.md) | method | public | `index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface` | 27 | ✓ |
| [`AgentController::show`](codigo/http/controladores.md) | method | public | `show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface` | 50 | ✓ |
| [`AgentController::acknowledgeAlert`](codigo/http/controladores.md) | method | public | `acknowledgeAlert(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface` | 56 | ✓ |
| [`AgentController::assignAlert`](codigo/http/controladores.md) | method | public | `assignAlert(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface` | 66 | ✓ |
| [`AgentController::recordFollowUp`](codigo/http/controladores.md) | method | public | `recordFollowUp(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface` | 78 | ✓ |
| [`AgentController::simulatePosition`](codigo/http/controladores.md) | method | public | `simulatePosition(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface` | 93 | ✓ |
| [`AgentController::createForm`](codigo/http/controladores.md) | method | public | `createForm(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface` | 118 | ✓ |
| [`AgentController::store`](codigo/http/controladores.md) | method | public | `store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface` | 123 | ✓ |
| [`AgentController::editForm`](codigo/http/controladores.md) | method | public | `editForm(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface` | 139 | ✓ |
| [`AgentController::update`](codigo/http/controladores.md) | method | public | `update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface` | 151 | ✓ |
| [`AgentController::changeStatus`](codigo/http/controladores.md) | method | public | `changeStatus(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface` | 177 | ✓ |
| [`AgentController::archive`](codigo/http/controladores.md) | method | public | `archive(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface` | 197 | ✓ |
| [`AgentController::alertAction`](codigo/http/controladores.md) | method | private | `alertAction(ServerRequestInterface $request, ResponseInterface $response, array $args, string $form, callable $run): ResponseInterface` | 219 | ✓ |
| [`AgentController::page`](codigo/http/controladores.md) | method | private | `page(ServerRequestInterface $request, ResponseInterface $response, int $id, int $status = 200, array $form = []): ResponseInterface` | 256 | ✓ |
| [`AgentController::form`](codigo/http/controladores.md) | method | private | `form(ServerRequestInterface $request, ResponseInterface $response, array $values, array $errors, ?array $existing, int $status = 200): ResponseInterface` | 298 | ✓ |
| [`AgentController::readableChange`](codigo/http/controladores.md) | method | private static | `readableChange(array $event): array` | 315 | ✓ |

### AuthController.php

Fonte: [`src/Http/Controller/AuthController.php`](../../src/Http/Controller/AuthController.php) · Referência: [controladores](codigo/http/controladores.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`AuthController`](codigo/http/controladores.md#authcontroller) | class | undefined | `` | 15 | ✓ |
| [`AuthController::loginForm`](codigo/http/controladores.md) | method | public | `loginForm(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface` | 17 | ✓ |
| [`AuthController::login`](codigo/http/controladores.md) | method | public | `login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface` | 26 | ✓ |
| [`AuthController::logout`](codigo/http/controladores.md) | method | public | `logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface` | 48 | ✓ |
| [`AuthController::accounts`](codigo/http/controladores.md) | method | public | `accounts(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface` | 55 | ✓ |
| [`AuthController::createAccount`](codigo/http/controladores.md) | method | public | `createAccount(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface` | 60 | ✓ |
| [`AuthController::toggleAccount`](codigo/http/controladores.md) | method | public | `toggleAccount(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface` | 76 | ✓ |
| [`AuthController::resetPassword`](codigo/http/controladores.md) | method | public | `resetPassword(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface` | 89 | ✓ |
| [`AuthController::safeBack`](codigo/http/controladores.md) | method | private | `safeBack(mixed $back): string` | 107 | ✓ |
| [`AuthController::clientIp`](codigo/http/controladores.md) | method | private | `clientIp(ServerRequestInterface $request): string` | 118 | ✓ |

### CentralController.php

Fonte: [`src/Http/Controller/CentralController.php`](../../src/Http/Controller/CentralController.php) · Referência: [controladores](codigo/http/controladores.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`CentralController`](codigo/http/controladores.md#centralcontroller) | class | undefined | `` | 16 | ✓ |
| [`CentralController::page`](codigo/http/controladores.md) | method | public | `page(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface` | 18 | ✓ |
| [`CentralController::snapshot`](codigo/http/controladores.md) | method | public | `snapshot(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface` | 30 | ✓ |
| [`CentralController::filterOptions`](codigo/http/controladores.md) | method | public static | `filterOptions(array $regions, array $agencies): array` | 40 | ✓ |

### Controller.php

Fonte: [`src/Http/Controller/Controller.php`](../../src/Http/Controller/Controller.php) · Referência: [controladores](codigo/http/controladores.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`Controller`](codigo/http/controladores.md#controller-base-abstrata) | class | undefined | `` | 15 | ✓ |
| [`Controller::__construct`](codigo/http/controladores.md) | method | public | `__construct(protected readonly Services $services, protected readonly View $view)` | 17 | ✓ |
| [`Controller::render`](codigo/http/controladores.md) | method | protected | `render(ServerRequestInterface $request, ResponseInterface $response, string $template, array $data = [], int $status = 200): ResponseInterface` | 20 | ✓ |
| [`Controller::redirect`](codigo/http/controladores.md) | method | protected | `redirect(ResponseInterface $response, string $path, int $status = 303): ResponseInterface` | 26 | ✓ |
| [`Controller::json`](codigo/http/controladores.md) | method | protected | `json(ResponseInterface $response, array $data, int $status = 200): ResponseInterface` | 32 | ✓ |
| [`Controller::session`](codigo/http/controladores.md) | method | protected | `session(ServerRequestInterface $request): Session` | 39 | ✓ |
| [`Controller::body`](codigo/http/controladores.md) | method | protected | `body(ServerRequestInterface $request): array` | 46 | ✓ |
| [`Controller::userId`](codigo/http/controladores.md) | method | protected | `userId(ServerRequestInterface $request): int` | 53 | ✓ |
| [`Controller::id`](codigo/http/controladores.md) | method | protected static | `id(array $args, string $key = 'id'): int` | 64 | ✓ |
| [`Controller::pageNumber`](codigo/http/controladores.md) | method | protected static | `pageNumber(array $query): int` | 75 | ✓ |

### EquipmentController.php

Fonte: [`src/Http/Controller/EquipmentController.php`](../../src/Http/Controller/EquipmentController.php) · Referência: [controladores](codigo/http/controladores.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`EquipmentController`](codigo/http/controladores.md#equipmentcontroller) | class | undefined | `` | 21 | ✓ |
| [`EquipmentController::index`](codigo/http/controladores.md) | method | public | `index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface` | 25 | ✓ |
| [`EquipmentController::show`](codigo/http/controladores.md) | method | public | `show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface` | 48 | ✓ |
| [`EquipmentController::createForm`](codigo/http/controladores.md) | method | public | `createForm(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface` | 72 | ✓ |
| [`EquipmentController::store`](codigo/http/controladores.md) | method | public | `store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface` | 79 | ✓ |
| [`EquipmentController::editForm`](codigo/http/controladores.md) | method | public | `editForm(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface` | 95 | ✓ |
| [`EquipmentController::update`](codigo/http/controladores.md) | method | public | `update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface` | 107 | ✓ |
| [`EquipmentController::changeStatus`](codigo/http/controladores.md) | method | public | `changeStatus(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface` | 127 | ✓ |
| [`EquipmentController::archive`](codigo/http/controladores.md) | method | public | `archive(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface` | 144 | ✓ |
| [`EquipmentController::simulateFailure`](codigo/http/controladores.md) | method | public | `simulateFailure(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface` | 166 | ✓ |
| [`EquipmentController::conflict`](codigo/http/controladores.md) | method | private | `conflict(ServerRequestInterface $request, ResponseInterface $response, int $id, array $data): ResponseInterface` | 191 | ✓ |
| [`EquipmentController::form`](codigo/http/controladores.md) | method | private | `form(ServerRequestInterface $request, ResponseInterface $response, array $values, array $errors, ?array $existing, int $status = 200, ?array $conflict = null): ResponseInterface` | 218 | ✓ |

### FineController.php

Fonte: [`src/Http/Controller/FineController.php`](../../src/Http/Controller/FineController.php) · Referência: [controladores](codigo/http/controladores.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`FineController`](codigo/http/controladores.md#finecontroller) | class | undefined | `` | 24 | ✓ |
| [`FineController::index`](codigo/http/controladores.md) | method | public | `index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface` | 26 | ✓ |
| [`FineController::show`](codigo/http/controladores.md) | method | public | `show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface` | 40 | ✓ |
| [`FineController::createForm`](codigo/http/controladores.md) | method | public | `createForm(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface` | 45 | ✓ |
| [`FineController::store`](codigo/http/controladores.md) | method | public | `store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface` | 61 | ✓ |
| [`FineController::editForm`](codigo/http/controladores.md) | method | public | `editForm(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface` | 77 | ✓ |
| [`FineController::update`](codigo/http/controladores.md) | method | public | `update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface` | 88 | ✓ |
| [`FineController::register`](codigo/http/controladores.md) | method | public | `register(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface` | 120 | ✓ |
| [`FineController::addCheck`](codigo/http/controladores.md) | method | public | `addCheck(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface` | 130 | ✓ |
| [`FineController::progress`](codigo/http/controladores.md) | method | public | `progress(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface` | 140 | ✓ |
| [`FineController::cancel`](codigo/http/controladores.md) | method | public | `cancel(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface` | 146 | ✓ |
| [`FineController::action`](codigo/http/controladores.md) | method | private | `action(ServerRequestInterface $request, ResponseInterface $response, array $args, string $form, callable $run, string $success): ResponseInterface` | 162 | ✓ |
| [`FineController::page`](codigo/http/controladores.md) | method | private | `page(ServerRequestInterface $request, ResponseInterface $response, int $id, int $status = 200, array $form = []): ResponseInterface` | 186 | ✓ |
| [`FineController::form`](codigo/http/controladores.md) | method | private | `form(ServerRequestInterface $request, ResponseInterface $response, array $values, array $errors, ?array $existing, int $status = 200, ?array $changes = null): ResponseInterface` | 209 | ✓ |
| [`FineController::vocabulary`](codigo/http/controladores.md) | method | private static | `vocabulary(): array` | 220 | ✓ |

### OperationsController.php

Fonte: [`src/Http/Controller/OperationsController.php`](../../src/Http/Controller/OperationsController.php) · Referência: [controladores](codigo/http/controladores.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`OperationsController`](codigo/http/controladores.md#operationscontroller) | class | undefined | `` | 18 | ✓ |
| [`OperationsController::maintenance`](codigo/http/controladores.md) | method | public | `maintenance(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface` | 20 | ✓ |
| [`OperationsController::open`](codigo/http/controladores.md) | method | public | `open(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface` | 44 | ✓ |
| [`OperationsController::start`](codigo/http/controladores.md) | method | public | `start(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface` | 58 | ✓ |
| [`OperationsController::complete`](codigo/http/controladores.md) | method | public | `complete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface` | 72 | ✓ |
| [`OperationsController::cancel`](codigo/http/controladores.md) | method | public | `cancel(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface` | 86 | ✓ |
| [`OperationsController::acknowledge`](codigo/http/controladores.md) | method | public | `acknowledge(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface` | 100 | ✓ |
| [`OperationsController::analysis`](codigo/http/controladores.md) | method | public | `analysis(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface` | 118 | ✓ |
| [`OperationsController::recalculate`](codigo/http/controladores.md) | method | public | `recalculate(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface` | 140 | ✓ |
| [`OperationsController::back`](codigo/http/controladores.md) | method | private | `back(ServerRequestInterface $request): string` | 149 | ✓ |
| [`OperationsController::attempt`](codigo/http/controladores.md) | method | private | `attempt(ServerRequestInterface $request, ResponseInterface $response, string $back, string $success, callable $action): ResponseInterface` | 156 | ✓ |

### PageController.php

Fonte: [`src/Http/Controller/PageController.php`](../../src/Http/Controller/PageController.php) · Referência: [controladores](codigo/http/controladores.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`PageController`](codigo/http/controladores.md#pagecontroller) | class | undefined | `` | 12 | ✓ |
| [`PageController::about`](codigo/http/controladores.md) | method | public | `about(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface` | 14 | ✓ |
| [`PageController::health`](codigo/http/controladores.md) | method | public | `health(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface` | 19 | ✓ |

### PlanningController.php

Fonte: [`src/Http/Controller/PlanningController.php`](../../src/Http/Controller/PlanningController.php) · Referência: [controladores](codigo/http/controladores.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`PlanningController`](codigo/http/controladores.md#planningcontroller) | class | undefined | `` | 18 | ✓ |
| [`PlanningController::page`](codigo/http/controladores.md) | method | public | `page(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface` | 24 | ✓ |
| [`PlanningController::show`](codigo/http/controladores.md) | method | private | `show(ServerRequestInterface $request, ResponseInterface $response, array $query, array $form = ['values' => [], 'errors' => []], int $status = 200): ResponseInterface` | 33 | ✓ |
| [`PlanningController::replay`](codigo/http/controladores.md) | method | public | `replay(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface` | 81 | ✓ |
| [`PlanningController::confirm`](codigo/http/controladores.md) | method | public | `confirm(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface` | 91 | ✓ |
| [`PlanningController::complete`](codigo/http/controladores.md) | method | public | `complete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface` | 111 | ✓ |
| [`PlanningController::cancel`](codigo/http/controladores.md) | method | public | `cancel(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface` | 117 | ✓ |
| [`PlanningController::close`](codigo/http/controladores.md) | method | private | `close(ServerRequestInterface $request, ResponseInterface $response, int $id, string $state, string $done): ResponseInterface` | 122 | ✓ |
| [`PlanningController::param`](codigo/http/controladores.md) | method | private static | `param(array $query, string $key): ?string` | 137 | ✓ |
| [`PlanningController::intParam`](codigo/http/controladores.md) | method | private static | `intParam(array $query, string $key, int $max): ?int` | 143 | ✓ |
| [`PlanningController::scalar`](codigo/http/controladores.md) | method | private static | `scalar(array $body, string $key): ?string` | 151 | ✓ |

## src/Http

### HttpError.php

Fonte: [`src/Http/HttpError.php`](../../src/Http/HttpError.php) · Referência: [erros](codigo/erros.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`HttpError`](codigo/erros.md) | class | undefined | `` | 8 | ✓ |
| [`HttpError::__construct`](codigo/erros.md) | method | public | `__construct(public readonly int $status, string $publicMessage)` | 10 | ✓ |

## src/Http/Middleware

### CsrfGuard.php

Fonte: [`src/Http/Middleware/CsrfGuard.php`](../../src/Http/Middleware/CsrfGuard.php) · Referência: [middlewares](codigo/http/middlewares.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`CsrfGuard`](codigo/http/middlewares.md#csrfguard) | class | undefined | `` | 19 | ✓ |
| [`CsrfGuard::__construct`](codigo/http/middlewares.md) | method | public | `__construct(private readonly string $origin = '')` | 22 | ✓ |
| [`CsrfGuard::process`](codigo/http/middlewares.md) | method | public | `process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface` | 24 | ✓ |
| [`CsrfGuard::expectedOrigin`](codigo/http/middlewares.md) | method | private static | `expectedOrigin(ServerRequestInterface $request): string` | 45 | ✓ |

### RequireRole.php

Fonte: [`src/Http/Middleware/RequireRole.php`](../../src/Http/Middleware/RequireRole.php) · Referência: [middlewares](codigo/http/middlewares.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`RequireRole`](codigo/http/middlewares.md#requirerole) | class | undefined | `` | 20 | ✓ |
| [`RequireRole::__construct`](codigo/http/middlewares.md) | method | public | `__construct(private readonly string $role, private readonly string $basePath)` | 22 | ✓ |
| [`RequireRole::process`](codigo/http/middlewares.md) | method | public | `process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface` | 24 | ✓ |

### SecurityHeaders.php

Fonte: [`src/Http/Middleware/SecurityHeaders.php`](../../src/Http/Middleware/SecurityHeaders.php) · Referência: [middlewares](codigo/http/middlewares.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`SecurityHeaders`](codigo/http/middlewares.md#securityheaders) | class | undefined | `` | 19 | ✓ |
| [`SecurityHeaders::__construct`](codigo/http/middlewares.md) | method | public | `__construct(private readonly string $tileUrl)` | 21 | ✓ |
| [`SecurityHeaders::process`](codigo/http/middlewares.md) | method | public | `process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface` | 23 | ✓ |
| [`SecurityHeaders::tileOrigin`](codigo/http/middlewares.md) | method | public static | `tileOrigin(string $url): ?string` | 57 | ✓ |

### SessionContext.php

Fonte: [`src/Http/Middleware/SessionContext.php`](../../src/Http/Middleware/SessionContext.php) · Referência: [middlewares](codigo/http/middlewares.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`SessionContext`](codigo/http/middlewares.md#sessioncontext) | class | undefined | `` | 18 | ✓ |
| [`SessionContext::__construct`](codigo/http/middlewares.md) | method | public | `__construct(private readonly Session $session, private readonly AuthService $auth)` | 20 | ✓ |
| [`SessionContext::process`](codigo/http/middlewares.md) | method | public | `process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface` | 22 | ✓ |

## src/Http

### Session.php

Fonte: [`src/Http/Session.php`](../../src/Http/Session.php) · Referência: [aplicacao-web](codigo/http/aplicacao-web.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`Session`](codigo/http/aplicacao-web.md#session) | class | undefined | `` | 13 | ✓ |
| [`Session::__construct`](codigo/http/aplicacao-web.md) | method | public | `__construct( private readonly string $name, private readonly string $cookiePath, private readonly bool $secure, )` | 22 | ✓ |
| [`Session::resumeIfPresent`](codigo/http/aplicacao-web.md) | method | public | `resumeIfPresent(array $cookies): void` | 29 | ✓ |
| [`Session::start`](codigo/http/aplicacao-web.md) | method | public | `start(): void` | 36 | ✓ |
| [`Session::isStarted`](codigo/http/aplicacao-web.md) | method | public | `isStarted(): bool` | 50 | ✓ |
| [`Session::get`](codigo/http/aplicacao-web.md) | method | public | `get(string $key, mixed $default = null): mixed` | 55 | ✓ |
| [`Session::set`](codigo/http/aplicacao-web.md) | method | public | `set(string $key, mixed $value): void` | 60 | ✓ |
| [`Session::remove`](codigo/http/aplicacao-web.md) | method | public | `remove(string $key): void` | 66 | ✓ |
| [`Session::regenerate`](codigo/http/aplicacao-web.md) | method | public | `regenerate(): void` | 72 | ✓ |
| [`Session::destroy`](codigo/http/aplicacao-web.md) | method | public | `destroy(): void` | 78 | ✓ |
| [`Session::csrfToken`](codigo/http/aplicacao-web.md) | method | public | `csrfToken(): string` | 89 | ✓ |
| [`Session::validCsrf`](codigo/http/aplicacao-web.md) | method | public | `validCsrf(mixed $token): bool` | 99 | ✓ |
| [`Session::flash`](codigo/http/aplicacao-web.md) | method | public | `flash(string $type, string $message): void` | 104 | ✓ |
| [`Session::takeFlash`](codigo/http/aplicacao-web.md) | method | public | `takeFlash(): array` | 110 | ✓ |

### View.php

Fonte: [`src/Http/View.php`](../../src/Http/View.php) · Referência: [aplicacao-web](codigo/http/aplicacao-web.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`View`](codigo/http/aplicacao-web.md#view) | class | undefined | `` | 20 | ✓ |
| [`View::__construct`](codigo/http/aplicacao-web.md) | method | public | `__construct(private readonly Config $config, string $templates, private readonly string $publicDir, ?string $cache)` | 24 | ✓ |
| [`View::render`](codigo/http/aplicacao-web.md) | method | public | `render(ServerRequestInterface $request, ResponseInterface $response, string $template, array $data = [], int $status = 200): ResponseInterface` | 67 | ✓ |

### ViteAssets.php

Fonte: [`src/Http/ViteAssets.php`](../../src/Http/ViteAssets.php) · Referência: [aplicacao-web](codigo/http/aplicacao-web.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`ViteAssets`](codigo/http/aplicacao-web.md#viteassets) | class | undefined | `` | 12 | ✓ |
| [`ViteAssets::__construct`](codigo/http/aplicacao-web.md) | method | public | `__construct(private readonly string $buildDir, private readonly string $publicUrl)` | 20 | ✓ |
| [`ViteAssets::styles`](codigo/http/aplicacao-web.md) | method | public | `styles(): string` | 22 | ✓ |
| [`ViteAssets::script`](codigo/http/aplicacao-web.md) | method | public | `script(): string` | 32 | ✓ |
| [`ViteAssets::entry`](codigo/http/aplicacao-web.md) | method | private | `entry(string $name): array` | 38 | ✓ |
| [`ViteAssets::load`](codigo/http/aplicacao-web.md) | method | private | `load(): array` | 50 | ✓ |
| [`ViteAssets::link`](codigo/http/aplicacao-web.md) | method | private | `link(mixed $file): string` | 62 | ✓ |
| [`ViteAssets::url`](codigo/http/aplicacao-web.md) | method | private | `url(mixed $file): string` | 67 | ✓ |

### WebApp.php

Fonte: [`src/Http/WebApp.php`](../../src/Http/WebApp.php) · Referência: [aplicacao-web](codigo/http/aplicacao-web.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`WebApp`](codigo/http/aplicacao-web.md#webapp) | class | undefined | `` | 39 | ✓ |
| [`WebApp::create`](codigo/http/aplicacao-web.md#webappcreate) | method | public static | `create(Services $services, ?Session $session = null): App` | 42 | ✓ |
| [`WebApp::renderError`](codigo/http/aplicacao-web.md#webapprendererror) | method | public static | `renderError(ServerRequestInterface $request, \Throwable $e, View $view, bool $production): ResponseInterface` | 148 | ✓ |

## src/Infrastructure

### ConcurrencyConflict.php

Fonte: [`src/Infrastructure/ConcurrencyConflict.php`](../../src/Infrastructure/ConcurrencyConflict.php) · Referência: [erros](codigo/erros.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`ConcurrencyConflict`](codigo/erros.md) | class | undefined | `` | 8 | ✓ |

### ConstraintViolation.php

Fonte: [`src/Infrastructure/ConstraintViolation.php`](../../src/Infrastructure/ConstraintViolation.php) · Referência: [erros](codigo/erros.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`ConstraintViolation`](codigo/erros.md) | class | undefined | `` | 8 | ✓ |
| [`ConstraintViolation::__construct`](codigo/erros.md) | method | public | `__construct(public readonly string $constraint, ?\Throwable $previous = null)` | 10 | ✓ |

### Database.php

Fonte: [`src/Infrastructure/Database.php`](../../src/Infrastructure/Database.php) · Referência: [banco-e-migracoes](codigo/infrastructure/banco-e-migracoes.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`Database`](codigo/infrastructure/banco-e-migracoes.md#database) | class | undefined | `` | 15 | ✓ |
| [`Database::__construct`](codigo/infrastructure/banco-e-migracoes.md) | method | public | `__construct( private readonly string $dsn, private readonly string $user, #[\SensitiveParameter] private readonly string $password, private readonly int $statementTimeoutSeconds = 5, )` | 19 | ✓ |
| [`Database::pdo`](codigo/infrastructure/banco-e-migracoes.md) | method | public | `pdo(): PDO` | 27 | ✓ |
| [`Database::disconnect`](codigo/infrastructure/banco-e-migracoes.md) | method | public | `disconnect(): void` | 46 | ✓ |
| [`Database::transaction`](codigo/infrastructure/banco-e-migracoes.md#databasetransaction) | method | public | `transaction(callable $work, bool $snapshot = false): mixed` | 59 | ✓ |
| [`Database::all`](codigo/infrastructure/banco-e-migracoes.md) | method | public | `all(string $sql, array $params = []): array` | 91 | ✓ |
| [`Database::one`](codigo/infrastructure/banco-e-migracoes.md) | method | public | `one(string $sql, array $params = []): ?array` | 101 | ✓ |
| [`Database::value`](codigo/infrastructure/banco-e-migracoes.md) | method | public | `value(string $sql, array $params = []): mixed` | 109 | ✓ |
| [`Database::insert`](codigo/infrastructure/banco-e-migracoes.md) | method | public | `insert(string $sql, array $params): int` | 121 | ✓ |
| [`Database::execute`](codigo/infrastructure/banco-e-migracoes.md) | method | public | `execute(string $sql, array $params = []): int` | 130 | ✓ |
| [`Database::run`](codigo/infrastructure/banco-e-migracoes.md) | method | private | `run(string $sql, array $params): \PDOStatement` | 136 | ✓ |
| [`Database::placeholders`](codigo/infrastructure/banco-e-migracoes.md) | method | public static | `placeholders(int $count): string` | 157 | ✓ |
| [`Database::translate`](codigo/infrastructure/banco-e-migracoes.md#databasetranslate) | method | public static | `translate(PDOException $e): \RuntimeException` | 162 | ✓ |

### DatabaseUnavailable.php

Fonte: [`src/Infrastructure/DatabaseUnavailable.php`](../../src/Infrastructure/DatabaseUnavailable.php) · Referência: [erros](codigo/erros.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`DatabaseUnavailable`](codigo/erros.md) | class | undefined | `` | 8 | ✓ |

### Migrator.php

Fonte: [`src/Infrastructure/Migrator.php`](../../src/Infrastructure/Migrator.php) · Referência: [banco-e-migracoes](codigo/infrastructure/banco-e-migracoes.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`Migrator`](codigo/infrastructure/banco-e-migracoes.md#migrator) | class | undefined | `` | 12 | ✓ |
| [`Migrator::__construct`](codigo/infrastructure/banco-e-migracoes.md) | method | public | `__construct(private readonly Database $db, private readonly string $directory)` | 23 | ✓ |
| [`Migrator::migrate`](codigo/infrastructure/banco-e-migracoes.md) | method | public | `migrate(): array` | 26 | ✓ |
| [`Migrator::grantApplicationAccess`](codigo/infrastructure/banco-e-migracoes.md) | method | public | `grantApplicationAccess(string $appUser): array` | 49 | ✓ |
| [`Migrator::dropEverything`](codigo/infrastructure/banco-e-migracoes.md) | method | public | `dropEverything(): void` | 82 | ✓ |
| [`Migrator::statements`](codigo/infrastructure/banco-e-migracoes.md) | method | public static | `statements(string $sql): array` | 101 | ✓ |
| [`Migrator::pending`](codigo/infrastructure/banco-e-migracoes.md) | method | private | `pending(array $applied): array` | 113 | ✓ |
| [`Migrator::ensureLedger`](codigo/infrastructure/banco-e-migracoes.md) | method | private | `ensureLedger(): void` | 128 | ✓ |

### NominatimGeocoder.php

Fonte: [`src/Infrastructure/NominatimGeocoder.php`](../../src/Infrastructure/NominatimGeocoder.php) · Referência: [provedores-externos](codigo/infrastructure/provedores-externos.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`NominatimGeocoder`](codigo/infrastructure/provedores-externos.md#nominatimgeocoder) | class | undefined | `` | 17 | ✓ |
| [`NominatimGeocoder::__construct`](codigo/infrastructure/provedores-externos.md) | method | public | `__construct(private readonly string $baseUrl, ?\Closure $transport = null)` | 30 | ✓ |
| [`NominatimGeocoder::locate`](codigo/infrastructure/provedores-externos.md) | method | public | `locate(string $street): ?array` | 35 | ✓ |
| [`NominatimGeocoder::curl`](codigo/infrastructure/provedores-externos.md) | method | private static | `curl(string $url, int $timeout): array` | 78 | ✓ |
| [`NominatimGeocoder::pace`](codigo/infrastructure/provedores-externos.md) | method | private static | `pace(): void` | 92 | ✓ |

## src/Infrastructure/Repository

### AgentRepository.php

Fonte: [`src/Infrastructure/Repository/AgentRepository.php`](../../src/Infrastructure/Repository/AgentRepository.php) · Referência: [repositorios-inventario](codigo/infrastructure/repositorios-inventario.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`AgentRepository`](codigo/infrastructure/repositorios-inventario.md#agentrepository) | class | undefined | `` | 11 | ✓ |
| [`AgentRepository::__construct`](codigo/infrastructure/repositorios-inventario.md) | method | public | `__construct(private readonly Database $db)` | 28 | ✓ |
| [`AgentRepository::find`](codigo/infrastructure/repositorios-inventario.md) | method | public | `find(int $id): ?array` | 31 | ✓ |
| [`AgentRepository::lock`](codigo/infrastructure/repositorios-inventario.md) | method | public | `lock(int $id): ?array` | 37 | ✓ |
| [`AgentRepository::nextCode`](codigo/infrastructure/repositorios-inventario.md) | method | public | `nextCode(string $prefix): string` | 43 | ✓ |
| [`AgentRepository::insert`](codigo/infrastructure/repositorios-inventario.md) | method | public | `insert(array $values): int` | 51 | ✓ |
| [`AgentRepository::updateVersioned`](codigo/infrastructure/repositorios-inventario.md) | method | public | `updateVersioned(int $id, int $expectedVersion, array $values): bool` | 62 | ✓ |
| [`AgentRepository::updateState`](codigo/infrastructure/repositorios-inventario.md) | method | public | `updateState(int $id, array $values): void` | 79 | ✓ |
| [`AgentRepository::movePosition`](codigo/infrastructure/repositorios-inventario.md) | method | public | `movePosition(int $id, string $lat, string $lng, string $observedAt, string $receivedAt): bool` | 88 | ✓ |
| [`AgentRepository::search`](codigo/infrastructure/repositorios-inventario.md) | method | public | `search(Filters $filters, string $text, bool $archived, string $sort, int $page, int $perPage): array` | 97 | ✓ |
| [`AgentRepository::forCentral`](codigo/infrastructure/repositorios-inventario.md) | method | public | `forCentral(Filters $filters): array` | 115 | ✓ |
| [`AgentRepository::allActive`](codigo/infrastructure/repositorios-inventario.md) | method | public | `allActive(): array` | 126 | ✓ |
| [`AgentRepository::where`](codigo/infrastructure/repositorios-inventario.md) | method | private static | `where(Filters $filters, bool $archived): array` | 132 | ✓ |
| [`AgentRepository::onlyWritable`](codigo/infrastructure/repositorios-inventario.md) | method | private static | `onlyWritable(array $values): array` | 150 | ✓ |
| [`AgentRepository::hydrate`](codigo/infrastructure/repositorios-inventario.md) | method | private static | `hydrate(?array $row): ?array` | 165 | ✓ |

### AlertRepository.php

Fonte: [`src/Infrastructure/Repository/AlertRepository.php`](../../src/Infrastructure/Repository/AlertRepository.php) · Referência: [repositorios-operacao](codigo/infrastructure/repositorios-operacao.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`AlertRepository`](codigo/infrastructure/repositorios-operacao.md#alertrepository) | class | undefined | `` | 11 | ✓ |
| [`AlertRepository::__construct`](codigo/infrastructure/repositorios-operacao.md) | method | public | `__construct(private readonly Database $db)` | 23 | ✓ |
| [`AlertRepository::find`](codigo/infrastructure/repositorios-operacao.md) | method | public | `find(int $id): ?array` | 26 | ✓ |
| [`AlertRepository::lock`](codigo/infrastructure/repositorios-operacao.md) | method | public | `lock(int $id): ?array` | 32 | ✓ |
| [`AlertRepository::unresolved`](codigo/infrastructure/repositorios-operacao.md) | method | public | `unresolved(): array` | 42 | ✓ |
| [`AlertRepository::forResource`](codigo/infrastructure/repositorios-operacao.md) | method | public | `forResource(string $kind, int $id, int $limit = 20): array` | 56 | ✓ |
| [`AlertRepository::unresolvedFor`](codigo/infrastructure/repositorios-operacao.md) | method | public | `unresolvedFor(string $kind, int $id): array` | 65 | ✓ |
| [`AlertRepository::resolvedFor`](codigo/infrastructure/repositorios-operacao.md) | method | public | `resolvedFor(string $kind, int $id, int $limit = 20): array` | 71 | ✓ |
| [`AlertRepository::countResolvedFor`](codigo/infrastructure/repositorios-operacao.md) | method | public | `countResolvedFor(string $kind, int $id): int` | 76 | ✓ |
| [`AlertRepository::openKeys`](codigo/infrastructure/repositorios-operacao.md) | method | public | `openKeys(): array` | 82 | ✓ |
| [`AlertRepository::open`](codigo/infrastructure/repositorios-operacao.md) | method | public | `open(string $kind, int $resourceId, string $reason, string $severity, string $message, string $at): bool` | 93 | ✓ |
| [`AlertRepository::resolve`](codigo/infrastructure/repositorios-operacao.md) | method | public | `resolve(int $id, string $at, string $reason): void` | 110 | ✓ |
| [`AlertRepository::acknowledge`](codigo/infrastructure/repositorios-operacao.md) | method | public | `acknowledge(int $id, int $userId, string $at, ?string $note): bool` | 115 | ✓ |
| [`AlertRepository::advance`](codigo/infrastructure/repositorios-operacao.md) | method | public | `advance(int $id, ?int $assigneeId, ?string $nextCheckAt): void` | 124 | ✓ |
| [`AlertRepository::addFollowUp`](codigo/infrastructure/repositorios-operacao.md) | method | public | `addFollowUp(array $f): int` | 130 | ✓ |
| [`AlertRepository::followUps`](codigo/infrastructure/repositorios-operacao.md) | method | public | `followUps(array $alertIds): array` | 144 | ✓ |
| [`AlertRepository::trim`](codigo/infrastructure/repositorios-operacao.md) | method | private static | `trim(?array $row): ?array` | 168 | ✓ |

### AnalysisRepository.php

Fonte: [`src/Infrastructure/Repository/AnalysisRepository.php`](../../src/Infrastructure/Repository/AnalysisRepository.php) · Referência: [repositorio-analise](codigo/infrastructure/repositorio-analise.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`AnalysisRepository`](codigo/infrastructure/repositorio-analise.md#analysisrepository) | class | undefined | `` | 10 | ✓ |
| [`AnalysisRepository::__construct`](codigo/infrastructure/repositorio-analise.md) | method | public | `__construct(private readonly Database $db)` | 12 | ✓ |
| [`AnalysisRepository::insertRun`](codigo/infrastructure/repositorio-analise.md) | method | public | `insertRun(array $run): int` | 17 | ✓ |
| [`AnalysisRepository::insertResult`](codigo/infrastructure/repositorio-analise.md) | method | public | `insertResult(int $runId, array $r): void` | 28 | ✓ |
| [`AnalysisRepository::latestRun`](codigo/infrastructure/repositorio-analise.md) | method | public | `latestRun(): ?array` | 38 | ✓ |
| [`AnalysisRepository::results`](codigo/infrastructure/repositorio-analise.md) | method | public | `results(int $runId): array` | 51 | ✓ |
| [`AnalysisRepository::latestForEquipment`](codigo/infrastructure/repositorio-analise.md) | method | public | `latestForEquipment(int $equipmentId): ?array` | 72 | ✓ |

### EquipmentRepository.php

Fonte: [`src/Infrastructure/Repository/EquipmentRepository.php`](../../src/Infrastructure/Repository/EquipmentRepository.php) · Referência: [repositorios-inventario](codigo/infrastructure/repositorios-inventario.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`EquipmentRepository`](codigo/infrastructure/repositorios-inventario.md#equipmentrepository) | class | undefined | `` | 11 | ✓ |
| [`EquipmentRepository::__construct`](codigo/infrastructure/repositorios-inventario.md) | method | public | `__construct(private readonly Database $db)` | 35 | ✓ |
| [`EquipmentRepository::find`](codigo/infrastructure/repositorios-inventario.md) | method | public | `find(int $id): ?array` | 38 | ✓ |
| [`EquipmentRepository::lock`](codigo/infrastructure/repositorios-inventario.md) | method | public | `lock(int $id): ?array` | 49 | ✓ |
| [`EquipmentRepository::nextCode`](codigo/infrastructure/repositorios-inventario.md) | method | public | `nextCode(string $prefix): string` | 55 | ✓ |
| [`EquipmentRepository::insert`](codigo/infrastructure/repositorios-inventario.md) | method | public | `insert(array $values): int` | 63 | ✓ |
| [`EquipmentRepository::updateVersioned`](codigo/infrastructure/repositorios-inventario.md) | method | public | `updateVersioned(int $id, int $expectedVersion, array $values): bool` | 79 | ✓ |
| [`EquipmentRepository::updateState`](codigo/infrastructure/repositorios-inventario.md) | method | public | `updateState(int $id, array $values): void` | 98 | ✓ |
| [`EquipmentRepository::touchSignal`](codigo/infrastructure/repositorios-inventario.md) | method | public | `touchSignal(int $id, string $seenAt): void` | 107 | ✓ |
| [`EquipmentRepository::search`](codigo/infrastructure/repositorios-inventario.md) | method | public | `search(Filters $filters, string $text, bool $archived, string $sort, int $page, int $perPage): array` | 117 | ✓ |
| [`EquipmentRepository::forCentral`](codigo/infrastructure/repositorios-inventario.md) | method | public | `forCentral(Filters $filters): array` | 139 | ✓ |
| [`EquipmentRepository::allActive`](codigo/infrastructure/repositorios-inventario.md) | method | public | `allActive(): array` | 150 | ✓ |
| [`EquipmentRepository::allIncludingArchived`](codigo/infrastructure/repositorios-inventario.md) | method | public | `allIncludingArchived(): array` | 160 | ✓ |
| [`EquipmentRepository::where`](codigo/infrastructure/repositorios-inventario.md) | method | private static | `where(Filters $filters, bool $archived): array` | 166 | ✓ |
| [`EquipmentRepository::onlyWritable`](codigo/infrastructure/repositorios-inventario.md) | method | private static | `onlyWritable(array $values): array` | 184 | ✓ |
| [`EquipmentRepository::hydrate`](codigo/infrastructure/repositorios-inventario.md) | method | private static | `hydrate(?array $row): ?array` | 202 | ✓ |

### EventRepository.php

Fonte: [`src/Infrastructure/Repository/EventRepository.php`](../../src/Infrastructure/Repository/EventRepository.php) · Referência: [repositorios-operacao](codigo/infrastructure/repositorios-operacao.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`EventRepository`](codigo/infrastructure/repositorios-operacao.md#eventrepository) | class | undefined | `` | 14 | ✓ |
| [`EventRepository::__construct`](codigo/infrastructure/repositorios-operacao.md) | method | public | `__construct(private readonly Database $db)` | 16 | ✓ |
| [`EventRepository::append`](codigo/infrastructure/repositorios-operacao.md) | method | public | `append(array $e): ?int` | 23 | ✓ |
| [`EventRepository::exists`](codigo/infrastructure/repositorios-operacao.md) | method | public | `exists(string $uid): bool` | 40 | ✓ |
| [`EventRepository::forResource`](codigo/infrastructure/repositorios-operacao.md) | method | public | `forResource(string $kind, int $id, int $limit = 40): array` | 46 | ✓ |
| [`EventRepository::recent`](codigo/infrastructure/repositorios-operacao.md) | method | public | `recent(int $limit = 15): array` | 56 | ✓ |
| [`EventRepository::watermark`](codigo/infrastructure/repositorios-operacao.md) | method | public | `watermark(): array` | 66 | ✓ |
| [`EventRepository::equipmentStatusHistory`](codigo/infrastructure/repositorios-operacao.md) | method | public | `equipmentStatusHistory(string $until): array` | 79 | ✓ |
| [`EventRepository::equipmentIntervalChanges`](codigo/infrastructure/repositorios-operacao.md) | method | public | `equipmentIntervalChanges(string $until): array` | 94 | ✓ |
| [`EventRepository::decode`](codigo/infrastructure/repositorios-operacao.md) | method | private static | `decode(array $row): array` | 108 | ✓ |

### FineRepository.php

Fonte: [`src/Infrastructure/Repository/FineRepository.php`](../../src/Infrastructure/Repository/FineRepository.php) · Referência: [repositorios-multas](codigo/infrastructure/repositorios-multas.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`FineRepository`](codigo/infrastructure/repositorios-multas.md#finerepository) | class | undefined | `` | 15 | ✓ |
| [`FineRepository::__construct`](codigo/infrastructure/repositorios-multas.md) | method | public | `__construct(private readonly Database $db)` | 45 | ✓ |
| [`FineRepository::find`](codigo/infrastructure/repositorios-multas.md) | method | public | `find(int $id): ?array` | 51 | ✓ |
| [`FineRepository::lock`](codigo/infrastructure/repositorios-multas.md) | method | public | `lock(int $id): ?array` | 61 | ✓ |
| [`FineRepository::insert`](codigo/infrastructure/repositorios-multas.md) | method | public | `insert(array $values): int` | 67 | ✓ |
| [`FineRepository::updateVersioned`](codigo/infrastructure/repositorios-multas.md) | method | public | `updateVersioned(int $id, int $expectedVersion, array $values): bool` | 82 | ✓ |
| [`FineRepository::search`](codigo/infrastructure/repositorios-multas.md#finerepositorysearch) | method | public | `search(FineFilters $filters, string $today, int $page, int $perPage): array` | 99 | ✓ |
| [`FineRepository::appendEvent`](codigo/infrastructure/repositorios-multas.md) | method | public | `appendEvent(array $event): void` | 122 | ✓ |
| [`FineRepository::eventByUid`](codigo/infrastructure/repositorios-multas.md) | method | public | `eventByUid(string $uid): ?array` | 132 | ✓ |
| [`FineRepository::events`](codigo/infrastructure/repositorios-multas.md) | method | public | `events(int $fineId, int $page, int $perPage): array` | 144 | ✓ |
| [`FineRepository::insertCheck`](codigo/infrastructure/repositorios-multas.md) | method | public | `insertCheck(array $check): int` | 164 | ✓ |
| [`FineRepository::checks`](codigo/infrastructure/repositorios-multas.md) | method | public | `checks(int $fineId): array` | 175 | ✓ |
| [`FineRepository::sourceOptions`](codigo/infrastructure/repositorios-multas.md) | method | public | `sourceOptions(): array` | 193 | ✓ |
| [`FineRepository::where`](codigo/infrastructure/repositorios-multas.md) | method | private static | `where(FineFilters $f): array` | 215 | ✓ |
| [`FineRepository::onlyWritable`](codigo/infrastructure/repositorios-multas.md) | method | private static | `onlyWritable(array $values): array` | 257 | ✓ |
| [`FineRepository::hydrate`](codigo/infrastructure/repositorios-multas.md) | method | private static | `hydrate(?array $row): ?array` | 277 | ✓ |

### MaintenanceRepository.php

Fonte: [`src/Infrastructure/Repository/MaintenanceRepository.php`](../../src/Infrastructure/Repository/MaintenanceRepository.php) · Referência: [repositorios-operacao](codigo/infrastructure/repositorios-operacao.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`MaintenanceRepository`](codigo/infrastructure/repositorios-operacao.md#maintenancerepository) | class | undefined | `` | 10 | ✓ |
| [`MaintenanceRepository::__construct`](codigo/infrastructure/repositorios-operacao.md) | method | public | `__construct(private readonly Database $db)` | 20 | ✓ |
| [`MaintenanceRepository::find`](codigo/infrastructure/repositorios-operacao.md) | method | public | `find(int $id): ?array` | 23 | ✓ |
| [`MaintenanceRepository::lock`](codigo/infrastructure/repositorios-operacao.md) | method | public | `lock(int $id): ?array` | 29 | ✓ |
| [`MaintenanceRepository::insert`](codigo/infrastructure/repositorios-operacao.md) | method | public | `insert(array $o): int` | 37 | ✓ |
| [`MaintenanceRepository::markStarted`](codigo/infrastructure/repositorios-operacao.md) | method | public | `markStarted(int $id, string $at): void` | 45 | ✓ |
| [`MaintenanceRepository::markClosed`](codigo/infrastructure/repositorios-operacao.md) | method | public | `markClosed(int $id, string $state, string $at, ?int $by, string $resolution, ?string $resultStatus): void` | 50 | ✓ |
| [`MaintenanceRepository::open`](codigo/infrastructure/repositorios-operacao.md) | method | public | `open(?array $equipmentIds = null): array` | 64 | ✓ |
| [`MaintenanceRepository::forEquipment`](codigo/infrastructure/repositorios-operacao.md) | method | public | `forEquipment(int $equipmentId, int $closedLimit = 30): array` | 85 | ✓ |
| [`MaintenanceRepository::closedCountFor`](codigo/infrastructure/repositorios-operacao.md) | method | public | `closedCountFor(int $equipmentId): int` | 93 | ✓ |
| [`MaintenanceRepository::recentlyClosed`](codigo/infrastructure/repositorios-operacao.md) | method | public | `recentlyClosed(int $limit = 20): array` | 99 | ✓ |
| [`MaintenanceRepository::equipmentOf`](codigo/infrastructure/repositorios-operacao.md) | method | public | `equipmentOf(int $orderId): ?int` | 104 | ✓ |
| [`MaintenanceRepository::openFor`](codigo/infrastructure/repositorios-operacao.md) | method | public | `openFor(int $equipmentId): array` | 116 | ✓ |
| [`MaintenanceRepository::hasOpenOfKind`](codigo/infrastructure/repositorios-operacao.md) | method | public | `hasOpenOfKind(int $equipmentId, string $kind): bool` | 124 | ✓ |
| [`MaintenanceRepository::all`](codigo/infrastructure/repositorios-operacao.md) | method | public | `all(): array` | 134 | ✓ |
| [`MaintenanceRepository::trim`](codigo/infrastructure/repositorios-operacao.md) | method | private static | `trim(?array $row): ?array` | 143 | ✓ |

### MetaRepository.php

Fonte: [`src/Infrastructure/Repository/MetaRepository.php`](../../src/Infrastructure/Repository/MetaRepository.php) · Referência: [repositorios-operacao](codigo/infrastructure/repositorios-operacao.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`MetaRepository`](codigo/infrastructure/repositorios-operacao.md#metarepository) | class | undefined | `` | 13 | ✓ |
| [`MetaRepository::__construct`](codigo/infrastructure/repositorios-operacao.md) | method | public | `__construct(private readonly Database $db)` | 17 | ✓ |
| [`MetaRepository::text`](codigo/infrastructure/repositorios-operacao.md) | method | public | `text(string $name): ?string` | 19 | ✓ |
| [`MetaRepository::number`](codigo/infrastructure/repositorios-operacao.md) | method | public | `number(string $name): int` | 26 | ✓ |
| [`MetaRepository::set`](codigo/infrastructure/repositorios-operacao.md) | method | public | `set(string $name, ?string $text, ?int $number, string $at): void` | 31 | ✓ |
| [`MetaRepository::increment`](codigo/infrastructure/repositorios-operacao.md) | method | public | `increment(string $name, int $by, string $at): void` | 40 | ✓ |
| [`MetaRepository::all`](codigo/infrastructure/repositorios-operacao.md) | method | public | `all(): array` | 48 | ✓ |

### MissionRepository.php

Fonte: [`src/Infrastructure/Repository/MissionRepository.php`](../../src/Infrastructure/Repository/MissionRepository.php) · Referência: [repositorios-planejamento](codigo/infrastructure/repositorios-planejamento.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`MissionRepository`](codigo/infrastructure/repositorios-planejamento.md#missionrepository) | class | undefined | `` | 13 | ✓ |
| [`MissionRepository::__construct`](codigo/infrastructure/repositorios-planejamento.md) | method | public | `__construct(private readonly Database $db)` | 20 | ✓ |
| [`MissionRepository::insert`](codigo/infrastructure/repositorios-planejamento.md) | method | public | `insert(array $m): int` | 23 | ✓ |
| [`MissionRepository::lock`](codigo/infrastructure/repositorios-planejamento.md) | method | public | `lock(int $id): ?array` | 35 | ✓ |
| [`MissionRepository::find`](codigo/infrastructure/repositorios-planejamento.md) | method | public | `find(int $id): ?array` | 41 | ✓ |
| [`MissionRepository::close`](codigo/infrastructure/repositorios-planejamento.md) | method | public | `close(int $id, string $state, string $at, int $by, string $reason): bool` | 47 | ✓ |
| [`MissionRepository::activeForAgent`](codigo/infrastructure/repositorios-planejamento.md) | method | public | `activeForAgent(int $agentId): ?int` | 56 | ✓ |
| [`MissionRepository::activeByAgent`](codigo/infrastructure/repositorios-planejamento.md) | method | public | `activeByAgent(): array` | 64 | ✓ |
| [`MissionRepository::recent`](codigo/infrastructure/repositorios-planejamento.md) | method | public | `recent(int $closedLimit = 20): array` | 77 | ✓ |
| [`MissionRepository::log`](codigo/infrastructure/repositorios-planejamento.md) | method | public | `log(int $missionId, ?string $from, string $to, int $actor, string $at, ?string $note): void` | 85 | ✓ |
| [`MissionRepository::logs`](codigo/infrastructure/repositorios-planejamento.md) | method | public | `logs(array $missionIds): array` | 99 | ✓ |
| [`MissionRepository::hydrate`](codigo/infrastructure/repositorios-planejamento.md) | method | private static | `hydrate(?array $row): ?array` | 121 | ✓ |

### ReferenceRepository.php

Fonte: [`src/Infrastructure/Repository/ReferenceRepository.php`](../../src/Infrastructure/Repository/ReferenceRepository.php) · Referência: [repositorios-inventario](codigo/infrastructure/repositorios-inventario.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`ReferenceRepository`](codigo/infrastructure/repositorios-inventario.md#referencerepository) | class | undefined | `` | 10 | ✓ |
| [`ReferenceRepository::__construct`](codigo/infrastructure/repositorios-inventario.md) | method | public | `__construct(private readonly Database $db)` | 12 | ✓ |
| [`ReferenceRepository::agencies`](codigo/infrastructure/repositorios-inventario.md) | method | public | `agencies(): array` | 15 | ✓ |
| [`ReferenceRepository::regions`](codigo/infrastructure/repositorios-inventario.md) | method | public | `regions(): array` | 21 | ✓ |
| [`ReferenceRepository::agency`](codigo/infrastructure/repositorios-inventario.md) | method | public | `agency(int $id): ?array` | 27 | ✓ |
| [`ReferenceRepository::agencyExists`](codigo/infrastructure/repositorios-inventario.md) | method | public | `agencyExists(int $id): bool` | 34 | ✓ |
| [`ReferenceRepository::regionExists`](codigo/infrastructure/repositorios-inventario.md) | method | public | `regionExists(int $id): bool` | 39 | ✓ |
| [`ReferenceRepository::insertAgency`](codigo/infrastructure/repositorios-inventario.md) | method | public | `insertAgency(string $code, string $name, string $kind): int` | 44 | ✓ |
| [`ReferenceRepository::insertRegion`](codigo/infrastructure/repositorios-inventario.md) | method | public | `insertRegion(string $name, string $zone, string $lat, string $lng): int` | 49 | ✓ |
| [`ReferenceRepository::trim`](codigo/infrastructure/repositorios-inventario.md) | method | private static | `trim(array $row): array` | 58 | ✓ |

### UrbanRepository.php

Fonte: [`src/Infrastructure/Repository/UrbanRepository.php`](../../src/Infrastructure/Repository/UrbanRepository.php) · Referência: [repositorios-planejamento](codigo/infrastructure/repositorios-planejamento.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`UrbanRepository`](codigo/infrastructure/repositorios-planejamento.md#urbanrepository) | class | undefined | `` | 14 | ✓ |
| [`UrbanRepository::__construct`](codigo/infrastructure/repositorios-planejamento.md) | method | public | `__construct(private readonly Database $db)` | 19 | ✓ |
| [`UrbanRepository::findByBundle`](codigo/infrastructure/repositorios-planejamento.md) | method | public | `findByBundle(string $sha256): ?int` | 21 | ✓ |
| [`UrbanRepository::insertRun`](codigo/infrastructure/repositorios-planejamento.md) | method | public | `insertRun(array $bundle, string $importedAt): int` | 33 | ✓ |
| [`UrbanRepository::insertCells`](codigo/infrastructure/repositorios-planejamento.md) | method | public | `insertCells(int $runId, array $cells): void` | 71 | ✓ |
| [`UrbanRepository::cellCount`](codigo/infrastructure/repositorios-planejamento.md) | method | public | `cellCount(int $runId): int` | 82 | ✓ |
| [`UrbanRepository::topCells`](codigo/infrastructure/repositorios-planejamento.md) | method | public | `topCells(int $runId, int $limit): array` | 92 | ✓ |
| [`UrbanRepository::allCells`](codigo/infrastructure/repositorios-planejamento.md) | method | public | `allCells(int $runId): array` | 111 | ✓ |
| [`UrbanRepository::cell`](codigo/infrastructure/repositorios-planejamento.md) | method | public | `cell(int $runId, int $cellNo): ?array` | 117 | ✓ |
| [`UrbanRepository::latestRun`](codigo/infrastructure/repositorios-planejamento.md) | method | public | `latestRun(): ?array` | 127 | ✓ |
| [`UrbanRepository::sectors`](codigo/infrastructure/repositorios-planejamento.md) | method | public | `sectors(int $runId): array` | 137 | ✓ |
| [`UrbanRepository::referenceRun`](codigo/infrastructure/repositorios-planejamento.md) | method | public | `referenceRun(): ?array` | 160 | ✓ |
| [`UrbanRepository::findSnapshot`](codigo/infrastructure/repositorios-planejamento.md) | method | public | `findSnapshot(string $sha256): ?int` | 171 | ✓ |
| [`UrbanRepository::latestSnapshot`](codigo/infrastructure/repositorios-planejamento.md) | method | public | `latestSnapshot(): ?array` | 179 | ✓ |
| [`UrbanRepository::insertSnapshot`](codigo/infrastructure/repositorios-planejamento.md) | method | public | `insertSnapshot(array $snapshot, array $daily): int` | 198 | ✓ |
| [`UrbanRepository::cellCounts`](codigo/infrastructure/repositorios-planejamento.md) | method | public | `cellCounts(int $snapshotId, string $from, string $through): array` | 219 | ✓ |
| [`UrbanRepository::monthlyTotals`](codigo/infrastructure/repositorios-planejamento.md) | method | public | `monthlyTotals(int $snapshotId): array` | 234 | ✓ |
| [`UrbanRepository::replayWeeks`](codigo/infrastructure/repositorios-planejamento.md) | method | public | `replayWeeks(int $runId): array` | 246 | ✓ |
| [`UrbanRepository::replay`](codigo/infrastructure/repositorios-planejamento.md) | method | public | `replay(int $runId, ?string $week = null): array` | 256 | ✓ |
| [`UrbanRepository::dec`](codigo/infrastructure/repositorios-planejamento.md) | method | private static | `dec(float $value): string` | 268 | ✓ |
| [`UrbanRepository::coordinate`](codigo/infrastructure/repositorios-planejamento.md) | method | private static | `coordinate(float $value): string` | 273 | ✓ |
| [`UrbanRepository::numbers`](codigo/infrastructure/repositorios-planejamento.md) | method | private static | `numbers(array $row, array $columns): array` | 283 | ✓ |
| [`UrbanRepository::hydrateRun`](codigo/infrastructure/repositorios-planejamento.md) | method | private static | `hydrateRun(?array $row): ?array` | 302 | ✓ |

### UserRepository.php

Fonte: [`src/Infrastructure/Repository/UserRepository.php`](../../src/Infrastructure/Repository/UserRepository.php) · Referência: [repositorio-contas](codigo/infrastructure/repositorio-contas.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`UserRepository`](codigo/infrastructure/repositorio-contas.md#userrepository) | class | undefined | `` | 10 | ✓ |
| [`UserRepository::__construct`](codigo/infrastructure/repositorio-contas.md) | method | public | `__construct(private readonly Database $db)` | 12 | ✓ |
| [`UserRepository::findByUsername`](codigo/infrastructure/repositorio-contas.md) | method | public | `findByUsername(string $username): ?array` | 15 | ✓ |
| [`UserRepository::find`](codigo/infrastructure/repositorio-contas.md) | method | public | `find(int $id): ?array` | 21 | ✓ |
| [`UserRepository::all`](codigo/infrastructure/repositorio-contas.md) | method | public | `all(): array` | 27 | ✓ |
| [`UserRepository::assignable`](codigo/infrastructure/repositorio-contas.md) | method | public | `assignable(): array` | 39 | ✓ |
| [`UserRepository::insert`](codigo/infrastructure/repositorio-contas.md) | method | public | `insert(string $username, string $displayName, string $hash, string $role, string $createdAt): int` | 44 | ✓ |
| [`UserRepository::updateHash`](codigo/infrastructure/repositorio-contas.md) | method | public | `updateHash(int $id, string $hash): void` | 52 | ✓ |
| [`UserRepository::resetPassword`](codigo/infrastructure/repositorio-contas.md) | method | public | `resetPassword(int $id, string $hash, int $by, string $at): void` | 58 | ✓ |
| [`UserRepository::touchLogin`](codigo/infrastructure/repositorio-contas.md) | method | public | `touchLogin(int $id, string $at): void` | 63 | ✓ |
| [`UserRepository::setActive`](codigo/infrastructure/repositorio-contas.md) | method | public | `setActive(int $id, bool $active): void` | 68 | ✓ |
| [`UserRepository::lockActiveAdmins`](codigo/infrastructure/repositorio-contas.md) | method | public | `lockActiveAdmins(): int` | 74 | ✓ |
| [`UserRepository::reserveAttempt`](codigo/infrastructure/repositorio-contas.md) | method | public | `reserveAttempt(string $keyHash, string $at): int` | 85 | ✓ |
| [`UserRepository::settleAttempt`](codigo/infrastructure/repositorio-contas.md) | method | public | `settleAttempt(int $reservationId, string $at): void` | 94 | ✓ |
| [`UserRepository::failuresSince`](codigo/infrastructure/repositorio-contas.md) | method | public | `failuresSince(string $keyHash, string $since): int` | 100 | ✓ |
| [`UserRepository::trim`](codigo/infrastructure/repositorio-contas.md) | method | private static | `trim(?array $row): ?array` | 113 | ✓ |

## src/Infrastructure

### ViaCepClient.php

Fonte: [`src/Infrastructure/ViaCepClient.php`](../../src/Infrastructure/ViaCepClient.php) · Referência: [provedores-externos](codigo/infrastructure/provedores-externos.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`ViaCepClient`](codigo/infrastructure/provedores-externos.md#viacepclient) | class | undefined | `` | 19 | ✓ |
| [`ViaCepClient::__construct`](codigo/infrastructure/provedores-externos.md) | method | public | `__construct(private readonly string $baseUrl, ?\Closure $transport = null)` | 32 | ✓ |
| [`ViaCepClient::find`](codigo/infrastructure/provedores-externos.md) | method | public | `find(string $cep): ?CepAddress` | 37 | ✓ |
| [`ViaCepClient::curl`](codigo/infrastructure/provedores-externos.md) | method | private static | `curl(string $url, int $timeout): array` | 67 | ✓ |

## src

### Services.php

Fonte: [`src/Services.php`](../../src/Services.php) · Referência: [configuracao-e-servicos](codigo/raiz/configuracao-e-servicos.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`Services`](codigo/raiz/configuracao-e-servicos.md#services) | class | undefined | `` | 50 | ✓ |
| [`Services::__construct`](codigo/raiz/configuracao-e-servicos.md) | method | public | `__construct( public readonly Config $config, public readonly string $root, private ?Database $database = null, private ?Clock $clock = null, private ?CepLookup $cepLookup = null, private ?Geocoder $geocoder = null, )` | 55 | ✓ |
| [`Services::asMigrationIdentity`](codigo/raiz/configuracao-e-servicos.md) | method | public | `asMigrationIdentity(): self` | 65 | ✓ |
| [`Services::withClock`](codigo/raiz/configuracao-e-servicos.md) | method | public | `withClock(Clock $clock): self` | 70 | ✓ |
| [`Services::withCepLookup`](codigo/raiz/configuracao-e-servicos.md) | method | public | `withCepLookup(CepLookup $lookup): self` | 76 | ✓ |
| [`Services::withGeocoder`](codigo/raiz/configuracao-e-servicos.md) | method | public | `withGeocoder(Geocoder $geocoder): self` | 82 | ✓ |
| [`Services::cepLookup`](codigo/raiz/configuracao-e-servicos.md) | method | public | `cepLookup(): CepLookup` | 88 | ✓ |
| [`Services::geocoder`](codigo/raiz/configuracao-e-servicos.md) | method | public | `geocoder(): Geocoder` | 94 | ✓ |
| [`Services::addressPoint`](codigo/raiz/configuracao-e-servicos.md) | method | public | `addressPoint(): AddressPoint` | 99 | ✓ |
| [`Services::addressLocator`](codigo/raiz/configuracao-e-servicos.md) | method | public | `addressLocator(): AddressLocator` | 104 | ✓ |
| [`Services::clock`](codigo/raiz/configuracao-e-servicos.md) | method | public | `clock(): Clock` | 109 | ✓ |
| [`Services::database`](codigo/raiz/configuracao-e-servicos.md) | method | public | `database(): Database` | 114 | ✓ |
| [`Services::migrationDatabase`](codigo/raiz/configuracao-e-servicos.md) | method | public | `migrationDatabase(): Database` | 124 | ✓ |
| [`Services::migrator`](codigo/raiz/configuracao-e-servicos.md) | method | public | `migrator(): Migrator` | 134 | ✓ |
| [`Services::equipmentRepository`](codigo/raiz/configuracao-e-servicos.md) | method | public | `equipmentRepository(): EquipmentRepository` | 139 | ✓ |
| [`Services::agentRepository`](codigo/raiz/configuracao-e-servicos.md) | method | public | `agentRepository(): AgentRepository` | 144 | ✓ |
| [`Services::maintenanceRepository`](codigo/raiz/configuracao-e-servicos.md) | method | public | `maintenanceRepository(): MaintenanceRepository` | 149 | ✓ |
| [`Services::eventRepository`](codigo/raiz/configuracao-e-servicos.md) | method | public | `eventRepository(): EventRepository` | 154 | ✓ |
| [`Services::alertRepository`](codigo/raiz/configuracao-e-servicos.md) | method | public | `alertRepository(): AlertRepository` | 159 | ✓ |
| [`Services::referenceRepository`](codigo/raiz/configuracao-e-servicos.md) | method | public | `referenceRepository(): ReferenceRepository` | 164 | ✓ |
| [`Services::userRepository`](codigo/raiz/configuracao-e-servicos.md) | method | public | `userRepository(): UserRepository` | 169 | ✓ |
| [`Services::metaRepository`](codigo/raiz/configuracao-e-servicos.md) | method | public | `metaRepository(): MetaRepository` | 174 | ✓ |
| [`Services::analysisRepository`](codigo/raiz/configuracao-e-servicos.md) | method | public | `analysisRepository(): AnalysisRepository` | 179 | ✓ |
| [`Services::urbanRepository`](codigo/raiz/configuracao-e-servicos.md) | method | public | `urbanRepository(): UrbanRepository` | 184 | ✓ |
| [`Services::missionRepository`](codigo/raiz/configuracao-e-servicos.md) | method | public | `missionRepository(): MissionRepository` | 189 | ✓ |
| [`Services::fineRepository`](codigo/raiz/configuracao-e-servicos.md) | method | public | `fineRepository(): FineRepository` | 194 | ✓ |
| [`Services::fines`](codigo/raiz/configuracao-e-servicos.md) | method | public | `fines(): FineService` | 199 | ✓ |
| [`Services::fineQuery`](codigo/raiz/configuracao-e-servicos.md) | method | public | `fineQuery(): FineQuery` | 211 | ✓ |
| [`Services::history`](codigo/raiz/configuracao-e-servicos.md) | method | public | `history(): History` | 222 | ✓ |
| [`Services::alerts`](codigo/raiz/configuracao-e-servicos.md) | method | public | `alerts(): AlertService` | 227 | ✓ |
| [`Services::equipment`](codigo/raiz/configuracao-e-servicos.md) | method | public | `equipment(): EquipmentService` | 240 | ✓ |
| [`Services::agents`](codigo/raiz/configuracao-e-servicos.md) | method | public | `agents(): AgentService` | 254 | ✓ |
| [`Services::maintenance`](codigo/raiz/configuracao-e-servicos.md) | method | public | `maintenance(): MaintenanceService` | 267 | ✓ |
| [`Services::ingestor`](codigo/raiz/configuracao-e-servicos.md) | method | public | `ingestor(): EventIngestor` | 280 | ✓ |
| [`Services::central`](codigo/raiz/configuracao-e-servicos.md) | method | public | `central(): CentralQuery` | 292 | ✓ |
| [`Services::auth`](codigo/raiz/configuracao-e-servicos.md) | method | public | `auth(): AuthService` | 309 | ✓ |
| [`Services::analysis`](codigo/raiz/configuracao-e-servicos.md) | method | public | `analysis(): AnalysisService` | 314 | ✓ |
| [`Services::urbanImporter`](codigo/raiz/configuracao-e-servicos.md) | method | public | `urbanImporter(): UrbanImporter` | 326 | ✓ |
| [`Services::urbanUpdater`](codigo/raiz/configuracao-e-servicos.md) | method | public | `urbanUpdater(): UrbanUpdater` | 331 | ✓ |
| [`Services::planning`](codigo/raiz/configuracao-e-servicos.md) | method | public | `planning(): PlanningService` | 336 | ✓ |
| [`Services::seeder`](codigo/raiz/configuracao-e-servicos.md) | method | public | `seeder(): DemoSeeder` | 348 | ✓ |
| [`Services::simulator`](codigo/raiz/configuracao-e-servicos.md) | method | public | `simulator(): Simulator` | 353 | ✓ |
| [`Services::shared`](codigo/raiz/configuracao-e-servicos.md) | method | private | `shared(string $key, callable $factory): object` | 363 | ✓ |

## templates

### _ajuda.twig

Fonte: [`templates/_ajuda.twig`](../../templates/_ajuda.twig) · Referência: [macros-e-telas](codigo/templates/macros-e-telas.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`_ajuda.ex`](codigo/templates/macros-e-telas.md) | macro | macro | `ex(items)` | 5 | ✓ |
| [`_ajuda.address_ref`](codigo/templates/macros-e-telas.md) | macro | macro | `address_ref()` | 9 | ✓ |
| [`_ajuda.cep`](codigo/templates/macros-e-telas.md) | macro | macro | `cep()` | 14 | ✓ |
| [`_ajuda.street`](codigo/templates/macros-e-telas.md) | macro | macro | `street()` | 19 | ✓ |
| [`_ajuda.region`](codigo/templates/macros-e-telas.md) | macro | macro | `region()` | 24 | ✓ |
| [`_ajuda.map_point`](codigo/templates/macros-e-telas.md) | macro | macro | `map_point(what = 'o local')` | 29 | ✓ |
| [`_ajuda.location_kind`](codigo/templates/macros-e-telas.md) | macro | macro | `location_kind()` | 34 | ✓ |
| [`_ajuda.equipment_type`](codigo/templates/macros-e-telas.md) | macro | macro | `equipment_type(types)` | 46 | ✓ |
| [`_ajuda.equipment_status`](codigo/templates/macros-e-telas.md) | macro | macro | `equipment_status()` | 52 | ✓ |
| [`_ajuda.agency`](codigo/templates/macros-e-telas.md) | macro | macro | `agency()` | 65 | ✓ |
| [`_ajuda.serial`](codigo/templates/macros-e-telas.md) | macro | macro | `serial()` | 70 | ✓ |
| [`_ajuda.asset_tag`](codigo/templates/macros-e-telas.md) | macro | macro | `asset_tag()` | 75 | ✓ |
| [`_ajuda.maint_interval`](codigo/templates/macros-e-telas.md) | macro | macro | `maint_interval()` | 80 | ✓ |
| [`_ajuda.installed_on`](codigo/templates/macros-e-telas.md) | macro | macro | `installed_on()` | 86 | ✓ |
| [`_ajuda.spec`](codigo/templates/macros-e-telas.md) | macro | macro | `spec(f)` | 93 | ✓ |
| [`_ajuda.status_change`](codigo/templates/macros-e-telas.md) | macro | macro | `status_change(status)` | 102 | ✓ |
| [`_ajuda.reason`](codigo/templates/macros-e-telas.md) | macro | macro | `reason()` | 115 | ✓ |
| [`_ajuda.maint_kind`](codigo/templates/macros-e-telas.md) | macro | macro | `maint_kind()` | 120 | ✓ |
| [`_ajuda.maint_priority`](codigo/templates/macros-e-telas.md) | macro | macro | `maint_priority()` | 127 | ✓ |
| [`_ajuda.maint_due`](codigo/templates/macros-e-telas.md) | macro | macro | `maint_due()` | 134 | ✓ |
| [`_ajuda.maint_result`](codigo/templates/macros-e-telas.md) | macro | macro | `maint_result()` | 139 | ✓ |
| [`_ajuda.stolen_decision`](codigo/templates/macros-e-telas.md) | macro | macro | `stolen_decision(mobile)` | 148 | ✓ |
| [`_ajuda.agent_role`](codigo/templates/macros-e-telas.md) | macro | macro | `agent_role()` | 159 | ✓ |
| [`_ajuda.agent_status`](codigo/templates/macros-e-telas.md) | macro | macro | `agent_status()` | 166 | ✓ |
| [`_ajuda.agent_status_change`](codigo/templates/macros-e-telas.md) | macro | macro | `agent_status_change()` | 172 | ✓ |
| [`_ajuda.ack_note`](codigo/templates/macros-e-telas.md) | macro | macro | `ack_note()` | 177 | ✓ |
| [`_ajuda.assignee`](codigo/templates/macros-e-telas.md) | macro | macro | `assignee()` | 181 | ✓ |
| [`_ajuda.next_check`](codigo/templates/macros-e-telas.md) | macro | macro | `next_check()` | 185 | ✓ |
| [`_ajuda.followup_note`](codigo/templates/macros-e-telas.md) | macro | macro | `followup_note()` | 189 | ✓ |
| [`_ajuda.agent_reason`](codigo/templates/macros-e-telas.md) | macro | macro | `agent_reason()` | 194 | ✓ |
| [`_ajuda.call_sign`](codigo/templates/macros-e-telas.md) | macro | macro | `call_sign()` | 199 | ✓ |
| [`_ajuda.base_name`](codigo/templates/macros-e-telas.md) | macro | macro | `base_name()` | 204 | ✓ |
| [`_ajuda.fine_record`](codigo/templates/macros-e-telas.md) | macro | macro | `fine_record()` | 212 | ✓ |
| [`_ajuda.fine_source`](codigo/templates/macros-e-telas.md) | macro | macro | `fine_source()` | 220 | ✓ |
| [`_ajuda.occurred_at`](codigo/templates/macros-e-telas.md) | macro | macro | `occurred_at(minFact = null)` | 229 | ✓ |
| [`_ajuda.infraction_kind`](codigo/templates/macros-e-telas.md) | macro | macro | `infraction_kind()` | 234 | ✓ |
| [`_ajuda.fine_agency`](codigo/templates/macros-e-telas.md) | macro | macro | `fine_agency()` | 239 | ✓ |
| [`_ajuda.legal_reference`](codigo/templates/macros-e-telas.md) | macro | macro | `legal_reference()` | 244 | ✓ |
| [`_ajuda.ait`](codigo/templates/macros-e-telas.md) | macro | macro | `ait()` | 249 | ✓ |
| [`_ajuda.plate`](codigo/templates/macros-e-telas.md) | macro | macro | `plate()` | 255 | ✓ |
| [`_ajuda.renavam`](codigo/templates/macros-e-telas.md) | macro | macro | `renavam()` | 261 | ✓ |
| [`_ajuda.missing_reason`](codigo/templates/macros-e-telas.md) | macro | macro | `missing_reason(what, examples)` | 268 | ✓ |
| [`_ajuda.vehicle_species`](codigo/templates/macros-e-telas.md) | macro | macro | `vehicle_species()` | 273 | ✓ |
| [`_ajuda.fine_description`](codigo/templates/macros-e-telas.md) | macro | macro | `fine_description()` | 278 | ✓ |
| [`_ajuda.fine_notes`](codigo/templates/macros-e-telas.md) | macro | macro | `fine_notes()` | 283 | ✓ |
| [`_ajuda.fine_radar`](codigo/templates/macros-e-telas.md) | macro | macro | `fine_radar()` | 287 | ✓ |
| [`_ajuda.fine_operator`](codigo/templates/macros-e-telas.md) | macro | macro | `fine_operator()` | 291 | ✓ |
| [`_ajuda.fine_agent`](codigo/templates/macros-e-telas.md) | macro | macro | `fine_agent()` | 295 | ✓ |
| [`_ajuda.evidence_reference`](codigo/templates/macros-e-telas.md) | macro | macro | `evidence_reference()` | 300 | ✓ |
| [`_ajuda.source_note`](codigo/templates/macros-e-telas.md) | macro | macro | `source_note()` | 304 | ✓ |
| [`_ajuda.speeds`](codigo/templates/macros-e-telas.md) | macro | macro | `speeds()` | 308 | ✓ |
| [`_ajuda.fine_stage`](codigo/templates/macros-e-telas.md) | macro | macro | `fine_stage()` | 318 | ✓ |
| [`_ajuda.fine_agent_filter`](codigo/templates/macros-e-telas.md) | macro | macro | `fine_agent_filter()` | 326 | ✓ |
| [`_ajuda.check_effective`](codigo/templates/macros-e-telas.md) | macro | macro | `check_effective()` | 332 | ✓ |
| [`_ajuda.source_reference`](codigo/templates/macros-e-telas.md) | macro | macro | `source_reference()` | 336 | ✓ |
| [`_ajuda.pending_items`](codigo/templates/macros-e-telas.md) | macro | macro | `pending_items()` | 341 | ✓ |
| [`_ajuda.check_reason`](codigo/templates/macros-e-telas.md) | macro | macro | `check_reason()` | 345 | ✓ |
| [`_ajuda.doc_review`](codigo/templates/macros-e-telas.md) | macro | macro | `doc_review()` | 349 | ✓ |
| [`_ajuda.review_on`](codigo/templates/macros-e-telas.md) | macro | macro | `review_on()` | 354 | ✓ |
| [`_ajuda.progress_reason`](codigo/templates/macros-e-telas.md) | macro | macro | `progress_reason()` | 358 | ✓ |
| [`_ajuda.fine_documents`](codigo/templates/macros-e-telas.md) | macro | macro | `fine_documents()` | 362 | ✓ |
| [`_ajuda.attention`](codigo/templates/macros-e-telas.md) | macro | macro | `attention()` | 368 | ✓ |
| [`_ajuda.kpi_operational`](codigo/templates/macros-e-telas.md) | macro | macro | `kpi_operational()` | 373 | ✓ |
| [`_ajuda.score`](codigo/templates/macros-e-telas.md) | macro | macro | `score()` | 378 | ✓ |
| [`_ajuda.precision`](codigo/templates/macros-e-telas.md) | macro | macro | `precision()` | 383 | ✓ |
| [`_ajuda.recall`](codigo/templates/macros-e-telas.md) | macro | macro | `recall()` | 387 | ✓ |
| [`_ajuda.f1`](codigo/templates/macros-e-telas.md) | macro | macro | `f1()` | 391 | ✓ |
| [`_ajuda.expected`](codigo/templates/macros-e-telas.md) | macro | macro | `expected()` | 395 | ✓ |
| [`_ajuda.method`](codigo/templates/macros-e-telas.md) | macro | macro | `method()` | 400 | ✓ |
| [`_ajuda.grid`](codigo/templates/macros-e-telas.md) | macro | macro | `grid()` | 406 | ✓ |
| [`_ajuda.data_through`](codigo/templates/macros-e-telas.md) | macro | macro | `data_through()` | 411 | ✓ |
| [`_ajuda.hit_rate`](codigo/templates/macros-e-telas.md) | macro | macro | `hit_rate()` | 415 | ✓ |
| [`_ajuda.mae`](codigo/templates/macros-e-telas.md) | macro | macro | `mae()` | 419 | ✓ |
| [`_ajuda.justification`](codigo/templates/macros-e-telas.md) | macro | macro | `justification()` | 423 | ✓ |
| [`_ajuda.support_point`](codigo/templates/macros-e-telas.md) | macro | macro | `support_point()` | 427 | ✓ |
| [`_ajuda.username`](codigo/templates/macros-e-telas.md) | macro | macro | `username()` | 434 | ✓ |
| [`_ajuda.account_role`](codigo/templates/macros-e-telas.md) | macro | macro | `account_role()` | 439 | ✓ |

### _autoria.twig

Fonte: [`templates/_autoria.twig`](../../templates/_autoria.twig) · Referência: [macros-e-telas](codigo/templates/macros-e-telas.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`_autoria.caixa`](codigo/templates/macros-e-telas.md) | macro | macro | `caixa(cls, olhos = false)` | 5 | ✓ |
| [`_autoria.mascote`](codigo/templates/macros-e-telas.md) | macro | macro | `mascote()` | 9 | ✓ |
| [`_autoria.codex`](codigo/templates/macros-e-telas.md) | macro | macro | `codex()` | 21 | ✓ |
| [`_autoria.defs`](codigo/templates/macros-e-telas.md) | macro | macro | `defs()` | 33 | ✓ |

### _ui.twig

Fonte: [`templates/_ui.twig`](../../templates/_ui.twig) · Referência: [macros-e-telas](codigo/templates/macros-e-telas.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`_ui.icon`](codigo/templates/macros-e-telas.md) | macro | macro | `icon(id, label = null, cls = 'icon')` | 3 | ✓ |
| [`_ui.status`](codigo/templates/macros-e-telas.md) | macro | macro | `status(code, label, glyph = null)` | 7 | ✓ |
| [`_ui.equipment_status`](codigo/templates/macros-e-telas.md) | macro | macro | `equipment_status(code)` | 12 | ✓ |
| [`_ui.agent_status`](codigo/templates/macros-e-telas.md) | macro | macro | `agent_status(code)` | 17 | ✓ |
| [`_ui.freshness`](codigo/templates/macros-e-telas.md) | macro | macro | `freshness(code, at, what = 'Sinal')` | 23 | ✓ |
| [`_ui.order_advice`](codigo/templates/macros-e-telas.md) | macro | macro | `order_advice(o, href, today, attention, windowEnd)` | 33 | ✓ |
| [`_ui.origin_real`](codigo/templates/macros-e-telas.md) | macro | macro | `origin_real()` | 38 | ✓ |
| [`_ui.origin_demo`](codigo/templates/macros-e-telas.md) | macro | macro | `origin_demo()` | 39 | ✓ |
| [`_ui.help`](codigo/templates/macros-e-telas.md) | macro | macro | `help(id, title, body)` | 44 | ✓ |
| [`_ui.field_error`](codigo/templates/macros-e-telas.md) | macro | macro | `field_error(errors, name)` | 48 | ✓ |
| [`_ui.input`](codigo/templates/macros-e-telas.md) | macro | macro | `input(name, label, values, errors, opts = {})` | 55 | ✓ |
| [`_ui.select`](codigo/templates/macros-e-telas.md) | macro | macro | `select(name, label, options, selected, errors, opts = {})` | 78 | ✓ |
| [`_ui.error_summary`](codigo/templates/macros-e-telas.md) | macro | macro | `error_summary(errors, labels, specPrefix = 's-')` | 94 | ✓ |
| [`_ui.pagination`](codigo/templates/macros-e-telas.md) | macro | macro | `pagination(page, pages, base_query, path)` | 104 | ✓ |
| [`_ui.csrf`](codigo/templates/macros-e-telas.md) | macro | macro | `csrf(token)` | 114 | ✓ |

### central.twig

Fonte: [`templates/central.twig`](../../templates/central.twig) · Referência: [macros-e-telas](codigo/templates/macros-e-telas.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`central.kpis`](codigo/templates/macros-e-telas.md) | macro | macro | `kpis(k, snapshot, cls)` | 15 | ✓ |

## templates/fines

### _fine.twig

Fonte: [`templates/fines/_fine.twig`](../../templates/fines/_fine.twig) · Referência: [macros-e-telas](codigo/templates/macros-e-telas.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`fines/_fine.state`](codigo/templates/macros-e-telas.md) | macro | macro | `state(code)` | 4 | ✓ |
| [`fines/_fine.doc`](codigo/templates/macros-e-telas.md) | macro | macro | `doc(code, review_on = null, today = null)` | 10 | ✓ |
| [`fines/_fine.money`](codigo/templates/macros-e-telas.md) | macro | macro | `money(cents)` | 19 | ✓ |
| [`fines/_fine.money_input`](codigo/templates/macros-e-telas.md) | macro | macro | `money_input(cents)` | 21 | ✓ |
| [`fines/_fine.diff_value`](codigo/templates/macros-e-telas.md) | macro | macro | `diff_value(v)` | 24 | ✓ |

## templates/planning

### index.twig

Fonte: [`templates/planning/index.twig`](../../templates/planning/index.twig) · Referência: [macros-e-telas](codigo/templates/macros-e-telas.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`planning/index.place`](codigo/templates/macros-e-telas.md) | macro | macro | `place(c)` | 8 | ✓ |

## tests/Http

### AgentsHttpTest.php

Fonte: [`tests/Http/AgentsHttpTest.php`](../../tests/Http/AgentsHttpTest.php) · Referência: [casos](codigo/testes/casos.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`AgentsHttpTest`](codigo/testes/casos.md#agentshttptest--agentes-e-endereço-pela-web-controladoreshttpcontroladoresmd) | class | undefined | `` | 18 | ✓ |
| [`AgentsHttpTest::setUp`](codigo/testes/casos.md) | method | protected | `setUp(): void` | 25 | ✓ |
| [`AgentsHttpTest::request`](codigo/testes/casos.md) | method | private | `request(string $method, string $path, array $body = []): ResponseInterface` | 38 | ✓ |
| [`AgentsHttpTest::loginAs`](codigo/testes/casos.md) | method | private | `loginAs(string $role): void` | 51 | ✓ |
| [`AgentsHttpTest::json`](codigo/testes/casos.md) | method | private static | `json(ResponseInterface $response): array` | 58 | ✓ |
| [`AgentsHttpTest::testCepLookupNeedsAnAccountAndNeverReachesTheProviderWithoutOne`](codigo/testes/casos.md) | method | public | `testCepLookupNeedsAnAccountAndNeverReachesTheProviderWithoutOne(): void` | 65 | ✓ |
| [`AgentsHttpTest::testInvalidCepIsRefusedBeforeAnyCallToTheProvider`](codigo/testes/casos.md) | method | public | `testInvalidCepIsRefusedBeforeAnyCallToTheProvider(): void` | 75 | ✓ |
| [`AgentsHttpTest::testCepLookupAnswersWithStreetOrAFriendlyReasonToTypeIt`](codigo/testes/casos.md) | method | public | `testCepLookupAnswersWithStreetOrAFriendlyReasonToTypeIt(): void` | 86 | ✓ |
| [`AgentsHttpTest::testLocatingByAddressGivesAPointWithoutTypingCoordinates`](codigo/testes/casos.md) | method | public | `testLocatingByAddressGivesAPointWithoutTypingCoordinates(): void` | 117 | ✓ |
| [`AgentsHttpTest::testWithoutTheMapTheBaseGoesToTheAddressOnSave`](codigo/testes/casos.md) | method | public | `testWithoutTheMapTheBaseGoesToTheAddressOnSave(): void` | 144 | ✓ |
| [`AgentsHttpTest::testNewAgentFormLeadsWithTheMapAndAsksOnlyForPointAndCep`](codigo/testes/casos.md) | method | public | `testNewAgentFormLeadsWithTheMapAndAsksOnlyForPointAndCep(): void` | 164 | ✓ |
| [`AgentsHttpTest::testRegisteringThroughTheFormNeedsOnlyNameAndPoint`](codigo/testes/casos.md) | method | public | `testRegisteringThroughTheFormNeedsOnlyNameAndPoint(): void` | 192 | ✓ |
| [`AgentsHttpTest::testLegacyAgentOpensWithItsStreetAndSavesWithClearHistory`](codigo/testes/casos.md) | method | public | `testLegacyAgentOpensWithItsStreetAndSavesWithClearHistory(): void` | 207 | ✓ |
| [`AgentsHttpTest::testFollowUpOnTheAgentPageKeepsWhatWasTypedOnEveryRefusal`](codigo/testes/casos.md) | method | public | `testFollowUpOnTheAgentPageKeepsWhatWasTypedOnEveryRefusal(): void` | 244 | ✓ |

### FinesHttpTest.php

Fonte: [`tests/Http/FinesHttpTest.php`](../../tests/Http/FinesHttpTest.php) · Referência: [casos](codigo/testes/casos.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`FinesHttpTest`](codigo/testes/casos.md#fineshttptest--multas-pela-web) | class | undefined | `` | 16 | ✓ |
| [`FinesHttpTest::setUp`](codigo/testes/casos.md) | method | protected | `setUp(): void` | 22 | ✓ |
| [`FinesHttpTest::request`](codigo/testes/casos.md) | method | private | `request(string $method, string $path, array $body = [], array $headers = [], string $base = self::BASE): ResponseInterface` | 33 | ✓ |
| [`FinesHttpTest::loginAs`](codigo/testes/casos.md) | method | private | `loginAs(string $role, string $username = 'pessoa1'): int` | 50 | ✓ |
| [`FinesHttpTest::formKeys`](codigo/testes/casos.md) | method | private static | `formKeys(string $html, string $action): array` | 60 | ✓ |
| [`FinesHttpTest::complete`](codigo/testes/casos.md) | method | private | `complete(array $override = []): array` | 72 | ✓ |
| [`FinesHttpTest::fines`](codigo/testes/casos.md) | method | private | `fines(): int` | 82 | ✓ |
| [`FinesHttpTest::testEveryFineRouteRequiresAnAccountAndDeniedWritesStoreNothing`](codigo/testes/casos.md) | method | public | `testEveryFineRouteRequiresAnAccountAndDeniedWritesStoreNothing(): void` | 87 | ✓ |
| [`FinesHttpTest::testDeactivatedAccountLosesAccessOnTheNextRequest`](codigo/testes/casos.md) | method | public | `testDeactivatedAccountLosesAccessOnTheNextRequest(): void` | 110 | ✓ |
| [`FinesHttpTest::testWholeFlowWorksWithPlainFormsForOperatorAndAdmin`](codigo/testes/casos.md) | method | public | `testWholeFlowWorksWithPlainFormsForOperatorAndAdmin(): void` | 119 | ✓ |
| [`FinesHttpTest::testErrorsKeepTypingAndStaleFormsShowTheCurrentVersion`](codigo/testes/casos.md) | method | public | `testErrorsKeepTypingAndStaleFormsShowTheCurrentVersion(): void` | 192 | ✓ |
| [`FinesHttpTest::testUntrustedTextIsEscapedAndPagesHaveNoInlineCode`](codigo/testes/casos.md) | method | public | `testUntrustedTextIsEscapedAndPagesHaveNoInlineCode(): void` | 216 | ✓ |
| [`FinesHttpTest::testGetNeverWritesMissingIdIs404AndBackLinkStaysInside`](codigo/testes/casos.md) | method | public | `testGetNeverWritesMissingIdIs404AndBackLinkStaysInside(): void` | 236 | ✓ |
| [`FinesHttpTest::shownFor`](codigo/testes/casos.md) | method | private static | `shownFor(\Dom\Element $field, array $state): bool` | 267 | ✓ |
| [`FinesHttpTest::dom`](codigo/testes/casos.md) | method | private static | `dom(ResponseInterface\|string $html): \Dom\HTMLDocument` | 283 | ✓ |
| [`FinesHttpTest::errorFields`](codigo/testes/casos.md) | method | private static | `errorFields(\Dom\HTMLDocument $doc): array` | 289 | ✓ |
| [`FinesHttpTest::registeredFine`](codigo/testes/casos.md) | method | private | `registeredFine(int $user, array $override = []): int` | 295 | ✓ |
| [`FinesHttpTest::testFieldsShownOnlyInSomeSituationsAppearExactlyWhenTheServerDemandsThem`](codigo/testes/casos.md) | method | public | `testFieldsShownOnlyInSomeSituationsAppearExactlyWhenTheServerDemandsThem(): void` | 303 | ✓ |
| [`FinesHttpTest::testRegistrationFormHidesOnlyOptionalOrAlternativeFieldsAndOpensThemWhenTheyMatter`](codigo/testes/casos.md) | method | public | `testRegistrationFormHidesOnlyOptionalOrAlternativeFieldsAndOpensThemWhenTheyMatter(): void` | 357 | ✓ |
| [`FinesHttpTest::testModuleWorksWithAnEmptyBasePath`](codigo/testes/casos.md) | method | public | `testModuleWorksWithAnEmptyBasePath(): void` | 403 | ✓ |

### LoginQuotaTest.php

Fonte: [`tests/Http/LoginQuotaTest.php`](../../tests/Http/LoginQuotaTest.php) · Referência: [casos](codigo/testes/casos.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`LoginQuotaTest`](codigo/testes/casos.md#loginquotatest--cota-atômica-de-login-autenticaçãoapplicationautenticacaomd) | class | undefined | `` | 18 | ✓ |
| [`LoginQuotaTest::testVerificationInProgressCountsAgainstTheQuota`](codigo/testes/casos.md) | method | public | `testVerificationInProgressCountsAgainstTheQuota(): void` | 22 | ✓ |
| [`LoginQuotaTest::testSuccessfulLoginSettlesItsOwnReservation`](codigo/testes/casos.md) | method | public | `testSuccessfulLoginSettlesItsOwnReservation(): void` | 34 | ✓ |
| [`LoginQuotaTest::testRepeatedSuccessfulLoginsAndOlderFailureRowsCoexist`](codigo/testes/casos.md) | method | public | `testRepeatedSuccessfulLoginsAndOlderFailureRowsCoexist(): void` | 49 | ✓ |
| [`LoginQuotaTest::accounts`](codigo/testes/casos.md) | method | public static | `accounts(): array` | 63 | ✓ |
| [`LoginQuotaTest::testConcurrentAttemptsNeverExceedTheUserLimit`](codigo/testes/casos.md) | method | public | `testConcurrentAttemptsNeverExceedTheUserLimit(bool $exists): void` | 69 | ✓ |
| [`LoginQuotaTest::testConcurrentHttpLoginsFromDistinctSessionsWithValidCsrfRespectTheLimit`](codigo/testes/casos.md) | method | public | `testConcurrentHttpLoginsFromDistinctSessionsWithValidCsrfRespectTheLimit(): void` | 84 | ✓ |
| [`LoginQuotaTest::priorFailures`](codigo/testes/casos.md) | method | private | `priorFailures(string $username, int $count): void` | 97 | ✓ |
| [`LoginQuotaTest::key`](codigo/testes/casos.md) | method | private static | `key(string $username): string` | 104 | ✓ |

### PlanningHttpTest.php

Fonte: [`tests/Http/PlanningHttpTest.php`](../../tests/Http/PlanningHttpTest.php) · Referência: [casos](codigo/testes/casos.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`PlanningHttpTest`](codigo/testes/casos.md#planninghttptest--planejar-reforço-pela-web) | class | undefined | `` | 16 | ✓ |
| [`PlanningHttpTest::setUp`](codigo/testes/casos.md) | method | protected | `setUp(): void` | 21 | ✓ |
| [`PlanningHttpTest::testWithoutAnImportedRunThePageSaysSoAndShowsNoNumbers`](codigo/testes/casos.md) | method | public | `testWithoutAnImportedRunThePageSaysSoAndShowsNoNumbers(): void` | 27 | ✓ |
| [`PlanningHttpTest::testPageShowsPeriodReferenceMethodOriginsAndOnlyTheJsonDataInline`](codigo/testes/casos.md) | method | public | `testPageShowsPeriodReferenceMethodOriginsAndOnlyTheJsonDataInline(): void` | 38 | ✓ |
| [`PlanningHttpTest::testHeatMapCarriesTheWholeGridWhileTheListKeepsOnlyThePriorities`](codigo/testes/casos.md) | method | public | `testHeatMapCarriesTheWholeGridWhileTheListKeepsOnlyThePriorities(): void` | 80 | ✓ |
| [`PlanningHttpTest::testConfirmingAMissionNeedsCsrfAnAccountAndAnEligibleAgent`](codigo/testes/casos.md) | method | public | `testConfirmingAMissionNeedsCsrfAnAccountAndAnEligibleAgent(): void` | 111 | ✓ |
| [`PlanningHttpTest::testAManualChoiceWithoutJustificationShowsTheErrorAtTheFieldAndKeepsWhatWasTyped`](codigo/testes/casos.md) | method | public | `testAManualChoiceWithoutJustificationShowsTheErrorAtTheFieldAndKeepsWhatWasTyped(): void` | 148 | ✓ |
| [`PlanningHttpTest::testReplayKeepsObservedCountsHiddenUntilRevealed`](codigo/testes/casos.md) | method | public | `testReplayKeepsObservedCountsHiddenUntilRevealed(): void` | 182 | ✓ |
| [`PlanningHttpTest::import`](codigo/testes/casos.md) | method | private | `import(): int` | 205 | ✓ |
| [`PlanningHttpTest::request`](codigo/testes/casos.md) | method | private | `request(string $method, string $path, array $body = []): ResponseInterface` | 211 | ✓ |
| [`PlanningHttpTest::loginAs`](codigo/testes/casos.md) | method | private | `loginAs(string $role): int` | 229 | ✓ |

### ReviewFlowsHttpTest.php

Fonte: [`tests/Http/ReviewFlowsHttpTest.php`](../../tests/Http/ReviewFlowsHttpTest.php) · Referência: [casos](codigo/testes/casos.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`ReviewFlowsHttpTest`](codigo/testes/casos.md#reviewflowshttptest--fluxos-revisados-em-2410) | class | undefined | `` | 20 | ✓ |
| [`ReviewFlowsHttpTest::setUp`](codigo/testes/casos.md) | method | protected | `setUp(): void` | 26 | ✓ |
| [`ReviewFlowsHttpTest::request`](codigo/testes/casos.md) | method | private | `request(string $method, string $path, array $body = []): ResponseInterface` | 33 | ✓ |
| [`ReviewFlowsHttpTest::login`](codigo/testes/casos.md) | method | private | `login(string $username, string $password): int` | 51 | ✓ |
| [`ReviewFlowsHttpTest::loginAs`](codigo/testes/casos.md) | method | private | `loginAs(string $role): int` | 56 | ✓ |
| [`ReviewFlowsHttpTest::page`](codigo/testes/casos.md) | method | private | `page(string $path): string` | 64 | ✓ |
| [`ReviewFlowsHttpTest::openOrder`](codigo/testes/casos.md) | method | private | `openOrder(int $equipmentId, int $actor): int` | 72 | ✓ |
| [`ReviewFlowsHttpTest::testStolenEquipmentAsksForReplacementOrWriteOffInTheNextAction`](codigo/testes/casos.md) | method | public | `testStolenEquipmentAsksForReplacementOrWriteOffInTheNextAction(): void` | 78 | ✓ |
| [`ReviewFlowsHttpTest::testFailureNextActionUsesTheScheduledCorrectiveOrder`](codigo/testes/casos.md) | method | public | `testFailureNextActionUsesTheScheduledCorrectiveOrder(): void` | 90 | ✓ |
| [`ReviewFlowsHttpTest::testMaintenanceStartIsOfferedOnlyWhenTheEquipmentCanEnterMaintenance`](codigo/testes/casos.md) | method | public | `testMaintenanceStartIsOfferedOnlyWhenTheEquipmentCanEnterMaintenance(): void` | 116 | ✓ |
| [`ReviewFlowsHttpTest::testAnOldPendingOrderStaysReachableBehindThirtyClosedOnes`](codigo/testes/casos.md) | method | public | `testAnOldPendingOrderStaysReachableBehindThirtyClosedOnes(): void` | 146 | ✓ |
| [`ReviewFlowsHttpTest::testEveryConfirmedMissionKeepsItsCloseActionsAndTheAgentPageLinksToIt`](codigo/testes/casos.md) | method | public | `testEveryConfirmedMissionKeepsItsCloseActionsAndTheAgentPageLinksToIt(): void` | 166 | ✓ |
| [`ReviewFlowsHttpTest::testAdminResetsAPasswordOnTheSameAccountAndOpenSessionsEnd`](codigo/testes/casos.md) | method | public | `testAdminResetsAPasswordOnTheSameAccountAndOpenSessionsEnd(): void` | 187 | ✓ |
| [`ReviewFlowsHttpTest::testWorkingSummaryNamesTheRegisteredSituationAndTheRecentSignal`](codigo/testes/casos.md) | method | public | `testWorkingSummaryNamesTheRegisteredSituationAndTheRecentSignal(): void` | 225 | ✓ |
| [`ReviewFlowsHttpTest::testFineRetificationConflictKeepsTypingAndPointsToTheProgress`](codigo/testes/casos.md) | method | public | `testFineRetificationConflictKeepsTypingAndPointsToTheProgress(): void` | 236 | ✓ |

### WebSecurityTest.php

Fonte: [`tests/Http/WebSecurityTest.php`](../../tests/Http/WebSecurityTest.php) · Referência: [casos](codigo/testes/casos.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`WebSecurityTest`](codigo/testes/casos.md#websecuritytest--segurança-da-aplicação-web-middlewareshttpmiddlewaresmd) | class | undefined | `` | 15 | ✓ |
| [`WebSecurityTest::setUp`](codigo/testes/casos.md) | method | protected | `setUp(): void` | 20 | ✓ |
| [`WebSecurityTest::request`](codigo/testes/casos.md) | method | private | `request(string $method, string $path, array $body = [], array $headers = [], bool $withCookie = true, ?Services $services = null): ResponseInterface` | 30 | ✓ |
| [`WebSecurityTest::loginAs`](codigo/testes/casos.md) | method | private | `loginAs(string $role): void` | 47 | ✓ |
| [`WebSecurityTest::login`](codigo/testes/casos.md) | method | private | `login(string $username, string $password): void` | 54 | ✓ |
| [`WebSecurityTest::testVisitorIsSentToLoginWithoutCookieAndWithSecurityHeaders`](codigo/testes/casos.md) | method | public | `testVisitorIsSentToLoginWithoutCookieAndWithSecurityHeaders(): void` | 60 | ✓ |
| [`WebSecurityTest::testEveryScreenAndTheApiRequireAnAccount`](codigo/testes/casos.md) | method | public | `testEveryScreenAndTheApiRequireAnAccount(): void` | 80 | ✓ |
| [`WebSecurityTest::testOnlyAccountsRegisteredByTheAdminGetIn`](codigo/testes/casos.md) | method | public | `testOnlyAccountsRegisteredByTheAdminGetIn(): void` | 102 | ✓ |
| [`WebSecurityTest::testLogoutReturnsToTheLoginPage`](codigo/testes/casos.md) | method | public | `testLogoutReturnsToTheLoginPage(): void` | 130 | ✓ |
| [`WebSecurityTest::testSignedInUserSeesUnavailabilityWhenTheAccountCannotBeChecked`](codigo/testes/casos.md) | method | public | `testSignedInUserSeesUnavailabilityWhenTheAccountCannotBeChecked(): void` | 140 | ✓ |
| [`WebSecurityTest::testWritesRequireCsrfAndAuthentication`](codigo/testes/casos.md) | method | public | `testWritesRequireCsrfAndAuthentication(): void` | 149 | ✓ |
| [`WebSecurityTest::testCrossOriginPostIsRejectedEvenWithToken`](codigo/testes/casos.md) | method | public | `testCrossOriginPostIsRejectedEvenWithToken(): void` | 163 | ✓ |
| [`WebSecurityTest::testOperatorCreatesEquipmentAndCannotManageAccounts`](codigo/testes/casos.md) | method | public | `testOperatorCreatesEquipmentAndCannotManageAccounts(): void` | 171 | ✓ |
| [`WebSecurityTest::testUntrustedTextIsEscapedInHtmlAndJson`](codigo/testes/casos.md) | method | public | `testUntrustedTextIsEscapedInHtmlAndJson(): void` | 185 | ✓ |
| [`WebSecurityTest::testLoginFailuresAreGenericThrottledAndRedirectStaysInternal`](codigo/testes/casos.md) | method | public | `testLoginFailuresAreGenericThrottledAndRedirectStaysInternal(): void` | 203 | ✓ |
| [`WebSecurityTest::testMalformedQueryParametersDegradeSafely`](codigo/testes/casos.md) | method | public | `testMalformedQueryParametersDegradeSafely(): void` | 224 | ✓ |
| [`WebSecurityTest::testEditConflictKeepsTypingShowsDifferencesAndCarriesTheCurrentVersion`](codigo/testes/casos.md) | method | public | `testEditConflictKeepsTypingShowsDifferencesAndCarriesTheCurrentVersion(): void` | 240 | ✓ |
| [`WebSecurityTest::testEquipmentFormAsksForLocationByMapOrAddress`](codigo/testes/casos.md) | method | public | `testEquipmentFormAsksForLocationByMapOrAddress(): void` | 263 | ✓ |
| [`WebSecurityTest::testEditConflictTableNamesTheRightAgencyAndRegion`](codigo/testes/casos.md) | method | public | `testEditConflictTableNamesTheRightAgencyAndRegion(): void` | 279 | ✓ |
| [`WebSecurityTest::testAcknowledgeAlertViaJsonUsesHeaderToken`](codigo/testes/casos.md) | method | public | `testAcknowledgeAlertViaJsonUsesHeaderToken(): void` | 298 | ✓ |
| [`WebSecurityTest::testPagesUseOnlyLocalBuiltAssetsWithoutInlineCode`](codigo/testes/casos.md) | method | public | `testPagesUseOnlyLocalBuiltAssetsWithoutInlineCode(): void` | 309 | ✓ |
| [`WebSecurityTest::testSimulateFailureViaJsonReportsTheServerOutcome`](codigo/testes/casos.md) | method | public | `testSimulateFailureViaJsonReportsTheServerOutcome(): void` | 337 | ✓ |

## tests/Integration

### AccountsTest.php

Fonte: [`tests/Integration/AccountsTest.php`](../../tests/Integration/AccountsTest.php) · Referência: [casos](codigo/testes/casos.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`AccountsTest`](codigo/testes/casos.md#accountstest--contas-autenticaçãoapplicationautenticacaomd) | class | undefined | `` | 12 | ✓ |
| [`AccountsTest::activeAdmins`](codigo/testes/casos.md) | method | private | `activeAdmins(): int` | 14 | ✓ |
| [`AccountsTest::testTheLastActiveAdminCannotBeDeactivated`](codigo/testes/casos.md) | method | public | `testTheLastActiveAdminCannotBeDeactivated(): void` | 19 | ✓ |
| [`AccountsTest::testCrossDeactivationAtTheSameTimeKeepsOneActiveAdmin`](codigo/testes/casos.md) | method | public | `testCrossDeactivationAtTheSameTimeKeepsOneActiveAdmin(): void` | 32 | ✓ |

### AgentBaseTest.php

Fonte: [`tests/Integration/AgentBaseTest.php`](../../tests/Integration/AgentBaseTest.php) · Referência: [casos](codigo/testes/casos.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`AgentBaseTest`](codigo/testes/casos.md#agentbasetest--base-de-trabalho-do-agente-agentesapplicationagentesmd) | class | undefined | `` | 15 | ✓ |
| [`AgentBaseTest::setUp`](codigo/testes/casos.md) | method | protected | `setUp(): void` | 20 | ✓ |
| [`AgentBaseTest::minimal`](codigo/testes/casos.md) | method | private | `minimal(array $override = []): array` | 29 | ✓ |
| [`AgentBaseTest::userId`](codigo/testes/casos.md) | method | private | `userId(): int` | 35 | ✓ |
| [`AgentBaseTest::testRegistersWithOnlyNameRoleAgencyStatusBaseNameAndPoint`](codigo/testes/casos.md) | method | public | `testRegistersWithOnlyNameRoleAgencyStatusBaseNameAndPoint(): void` | 40 | ✓ |
| [`AgentBaseTest::testOnlyNameRoleAgencyBaseNameAndPointAreRequired`](codigo/testes/casos.md) | method | public | `testOnlyNameRoleAgencyBaseNameAndPointAreRequired(): void` | 53 | ✓ |
| [`AgentBaseTest::testStreetComesFromTheFormAndRegionOrReferenceSentByTheClientAreIgnored`](codigo/testes/casos.md) | method | public | `testStreetComesFromTheFormAndRegionOrReferenceSentByTheClientAreIgnored(): void` | 64 | ✓ |
| [`AgentBaseTest::testWithoutThePointTheBaseGoesToTheAddress`](codigo/testes/casos.md) | method | public | `testWithoutThePointTheBaseGoesToTheAddress(): void` | 81 | ✓ |
| [`AgentBaseTest::testPointOutsideTheAddressGoesToTheAddressOnSave`](codigo/testes/casos.md) | method | public | `testPointOutsideTheAddressGoesToTheAddressOnSave(): void` | 120 | ✓ |
| [`AgentBaseTest::testLegacyAgentKeepsChosenRegionUntilThePointChanges`](codigo/testes/casos.md) | method | public | `testLegacyAgentKeepsChosenRegionUntilThePointChanges(): void` | 145 | ✓ |
| [`AgentBaseTest::testLegacyAgentWithoutRegionGetsOneOnTheNextSave`](codigo/testes/casos.md) | method | public | `testLegacyAgentWithoutRegionGetsOneOnTheNextSave(): void` | 180 | ✓ |

### AlertFollowUpTest.php

Fonte: [`tests/Integration/AlertFollowUpTest.php`](../../tests/Integration/AlertFollowUpTest.php) · Referência: [casos](codigo/testes/casos.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`AlertFollowUpTest`](codigo/testes/casos.md#alertfollowuptest--acompanhamento-de-alertas-alertasapplicationalertasmd) | class | undefined | `` | 15 | ✓ |
| [`AlertFollowUpTest::account`](codigo/testes/casos.md) | method | private | `account(string $name = 'Operadora'): int` | 17 | ✓ |
| [`AlertFollowUpTest::agentWithAlert`](codigo/testes/casos.md) | method | private | `agentWithAlert(): array` | 23 | ✓ |
| [`AlertFollowUpTest::version`](codigo/testes/casos.md) | method | private | `version(int $alert): string` | 33 | ✓ |
| [`AlertFollowUpTest::localIn`](codigo/testes/casos.md) | method | private | `localIn(string $interval): string` | 38 | ✓ |
| [`AlertFollowUpTest::testPauseKeepsTheSameAlertInsteadOfResolvingAndReopening`](codigo/testes/casos.md) | method | public | `testPauseKeepsTheSameAlertInsteadOfResolvingAndReopening(): void` | 43 | ✓ |
| [`AlertFollowUpTest::testAcknowledgeKeepsTheAlertPendingAndDoesNotAssign`](codigo/testes/casos.md) | method | public | `testAcknowledgeKeepsTheAlertPendingAndDoesNotAssign(): void` | 57 | ✓ |
| [`AlertFollowUpTest::testFollowUpsComeAfterAcknowledgeAndAreAppendOnly`](codigo/testes/casos.md) | method | public | `testFollowUpsComeAfterAcknowledgeAndAreAppendOnly(): void` | 69 | ✓ |
| [`AlertFollowUpTest::testFollowUpValidationKeepsNothingAndExplainsEachField`](codigo/testes/casos.md) | method | public | `testFollowUpValidationKeepsNothingAndExplainsEachField(): void` | 92 | ✓ |
| [`AlertFollowUpTest::testNewAccountIsAssignedAndReassignedWithHistory`](codigo/testes/casos.md) | method | public | `testNewAccountIsAssignedAndReassignedWithHistory(): void` | 121 | ✓ |
| [`AlertFollowUpTest::testDeactivatedAccountKeepsTheLinkButReceivesNoNewAssignment`](codigo/testes/casos.md) | method | public | `testDeactivatedAccountKeepsTheLinkButReceivesNoNewAssignment(): void` | 140 | ✓ |
| [`AlertFollowUpTest::testFormReadBeforeAnotherPersonActedIsRefused`](codigo/testes/casos.md) | method | public | `testFormReadBeforeAnotherPersonActedIsRefused(): void` | 157 | ✓ |
| [`AlertFollowUpTest::testResolutionReasonSeparatesNewPositionFromLeavingShift`](codigo/testes/casos.md) | method | public | `testResolutionReasonSeparatesNewPositionFromLeavingShift(): void` | 169 | ✓ |
| [`AlertFollowUpTest::testOldPendingAlertIsListedEvenWithManyRecentClosedOnes`](codigo/testes/casos.md) | method | public | `testOldPendingAlertIsListedEvenWithManyRecentClosedOnes(): void` | 196 | ✓ |
| [`AlertFollowUpTest::testManualStatusChangeNeedsAReason`](codigo/testes/casos.md) | method | public | `testManualStatusChangeNeedsAReason(): void` | 212 | ✓ |

### CentralAndAnalysisTest.php

Fonte: [`tests/Integration/CentralAndAnalysisTest.php`](../../tests/Integration/CentralAndAnalysisTest.php) · Referência: [casos](codigo/testes/casos.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`CentralAndAnalysisTest`](codigo/testes/casos.md#centralandanalysistest--visão-geral-e-revisões-centralapplicationcentralmd-análiseapplicationanalise-preventivamd) | class | undefined | `` | 14 | ✓ |
| [`CentralAndAnalysisTest::testMapListAndIndicatorsComeFromTheSameFilteredSet`](codigo/testes/casos.md) | method | public | `testMapListAndIndicatorsComeFromTheSameFilteredSet(): void` | 16 | ✓ |
| [`CentralAndAnalysisTest::testSearchAndAttentionFilterTheWholeSnapshot`](codigo/testes/casos.md) | method | public | `testSearchAndAttentionFilterTheWholeSnapshot(): void` | 41 | ✓ |
| [`CentralAndAnalysisTest::testNoEquipmentInServiceShowsNotApplicable`](codigo/testes/casos.md) | method | public | `testNoEquipmentInServiceShowsNotApplicable(): void` | 61 | ✓ |
| [`CentralAndAnalysisTest::testResourceWithoutCoordinatesIsListedButNotPlaced`](codigo/testes/casos.md) | method | public | `testResourceWithoutCoordinatesIsListedButNotPlaced(): void` | 70 | ✓ |
| [`CentralAndAnalysisTest::testLaterIntervalChangeDoesNotRewriteAPastCutoff`](codigo/testes/casos.md) | method | public | `testLaterIntervalChangeDoesNotRewriteAPastCutoff(): void` | 80 | ✓ |
| [`CentralAndAnalysisTest::testEventReceivedAfterTheCutoffIsUnknownAtTheCutoff`](codigo/testes/casos.md) | method | public | `testEventReceivedAfterTheCutoffIsUnknownAtTheCutoff(): void` | 98 | ✓ |
| [`CentralAndAnalysisTest::testEquipmentArchivedLaterStillExistedAtEarlierCutoffs`](codigo/testes/casos.md) | method | public | `testEquipmentArchivedLaterStillExistedAtEarlierCutoffs(): void` | 116 | ✓ |
| [`CentralAndAnalysisTest::testDemoSeedAnalysisIsReproducibleAndTemporallySeparated`](codigo/testes/casos.md) | method | public | `testDemoSeedAnalysisIsReproducibleAndTemporallySeparated(): void` | 129 | ✓ |
| [`CentralAndAnalysisTest::row`](codigo/testes/casos.md) | method | private | `row(int $id): array` | 156 | ✓ |

### DemoProfileTest.php

Fonte: [`tests/Integration/DemoProfileTest.php`](../../tests/Integration/DemoProfileTest.php) · Referência: [casos](codigo/testes/casos.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`DemoProfileTest`](codigo/testes/casos.md#demoprofiletest--perfis-da-demonstração-cenáriodemocenario-e-cargamd) | class | undefined | `` | 17 | ✓ |
| [`DemoProfileTest::seedProfile`](codigo/testes/casos.md) | method | private | `seedProfile(string $profile): void` | 19 | ✓ |
| [`DemoProfileTest::testCompactProfileCoversEveryCategoryStatusAndMaintenanceExampleWithTrueCounts`](codigo/testes/casos.md) | method | public | `testCompactProfileCoversEveryCategoryStatusAndMaintenanceExampleWithTrueCounts(): void` | 32 | ✓ |
| [`DemoProfileTest::testFullProfileRemainsAvailableForVolumeAndMethodology`](codigo/testes/casos.md) | method | public | `testFullProfileRemainsAvailableForVolumeAndMethodology(): void` | 88 | ✓ |
| [`DemoProfileTest::testCompactSelectionIsASubsetAndUnknownProfilesAreRejected`](codigo/testes/casos.md) | method | public | `testCompactSelectionIsASubsetAndUnknownProfilesAreRejected(): void` | 99 | ✓ |

### DriverContractTest.php

Fonte: [`tests/Integration/DriverContractTest.php`](../../tests/Integration/DriverContractTest.php) · Referência: [casos](codigo/testes/casos.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`DriverContractTest`](codigo/testes/casos.md#drivercontracttest--contrato-com-o-pdo_firebird-bancoinfrastructurebanco-e-migracoesmd) | class | undefined | `` | 13 | ✓ |
| [`DriverContractTest::testUtf8TextBlobDecimalAndBooleanRoundTrip`](codigo/testes/casos.md) | method | public | `testUtf8TextBlobDecimalAndBooleanRoundTrip(): void` | 15 | ✓ |
| [`DriverContractTest::testInsertReturningAndOffsetFetchPagination`](codigo/testes/casos.md) | method | public | `testInsertReturningAndOffsetFetchPagination(): void` | 29 | ✓ |
| [`DriverContractTest::testFailureInsideTransactionRollsBackEveryWrite`](codigo/testes/casos.md) | method | public | `testFailureInsideTransactionRollsBackEveryWrite(): void` | 42 | ✓ |
| [`DriverContractTest::testLockWaitIsBoundedAndBecomesConcurrencyConflict`](codigo/testes/casos.md) | method | public | `testLockWaitIsBoundedAndBecomesConcurrencyConflict(): void` | 58 | ✓ |
| [`DriverContractTest::testApplicationIdentityCannotRewriteHistoryOrChangeSchema`](codigo/testes/casos.md) | method | public | `testApplicationIdentityCannotRewriteHistoryOrChangeSchema(): void` | 78 | ✓ |

### EquipmentLocationTest.php

Fonte: [`tests/Integration/EquipmentLocationTest.php`](../../tests/Integration/EquipmentLocationTest.php) · Referência: [casos](codigo/testes/casos.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`EquipmentLocationTest`](codigo/testes/casos.md#equipmentlocationtest--local-do-equipamento-equipamentosapplicationequipamentosmd) | class | undefined | `` | 14 | ✓ |
| [`EquipmentLocationTest::setUp`](codigo/testes/casos.md) | method | protected | `setUp(): void` | 19 | ✓ |
| [`EquipmentLocationTest::planned`](codigo/testes/casos.md) | method | private | `planned(array $override = []): array` | 29 | ✓ |
| [`EquipmentLocationTest::testPlannedEquipmentNeedsALocation`](codigo/testes/casos.md) | method | public | `testPlannedEquipmentNeedsALocation(): void` | 34 | ✓ |
| [`EquipmentLocationTest::testWithoutThePointTheLocationGoesToTheAddressAndTheRegionComesFromIt`](codigo/testes/casos.md) | method | public | `testWithoutThePointTheLocationGoesToTheAddressAndTheRegionComesFromIt(): void` | 44 | ✓ |
| [`EquipmentLocationTest::testPointOutsideTheAddressGoesToTheAddress`](codigo/testes/casos.md) | method | public | `testPointOutsideTheAddressGoesToTheAddress(): void` | 52 | ✓ |
| [`EquipmentLocationTest::testLegacyEquipmentWithoutCoordinatesGetsALocationOnEdit`](codigo/testes/casos.md) | method | public | `testLegacyEquipmentWithoutCoordinatesGetsALocationOnEdit(): void` | 62 | ✓ |

### FinesTest.php

Fonte: [`tests/Integration/FinesTest.php`](../../tests/Integration/FinesTest.php) · Referência: [casos](codigo/testes/casos.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`FinesTest`](codigo/testes/casos.md#finestest--multas-serviçoapplicationmultasmd-repositóriosinfrastructurerepositorios-multasmd-cargademomultas-de-demonstracaomd) | class | undefined | `` | 21 | ✓ |
| [`FinesTest::setUp`](codigo/testes/casos.md) | method | protected | `setUp(): void` | 27 | ✓ |
| [`FinesTest::radarFine`](codigo/testes/casos.md) | method | private | `radarFine(array $override = []): array` | 36 | ✓ |
| [`FinesTest::version`](codigo/testes/casos.md) | method | private | `version(int $id): int` | 48 | ✓ |
| [`FinesTest::cmd`](codigo/testes/casos.md) | method | private | `cmd(int $id, array $data = []): array` | 54 | ✓ |
| [`FinesTest::rows`](codigo/testes/casos.md) | method | private | `rows(string $table, int $fineId): int` | 59 | ✓ |
| [`FinesTest::testDraftIsCompletedRegisteredAndKeepsInternalCodeApartFromAit`](codigo/testes/casos.md) | method | public | `testDraftIsCompletedRegisteredAndKeepsInternalCodeApartFromAit(): void` | 64 | ✓ |
| [`FinesTest::testSourceIsCheckedOnTheServerEvenWithManipulatedPost`](codigo/testes/casos.md) | method | public | `testSourceIsCheckedOnTheServerEvenWithManipulatedPost(): void` | 95 | ✓ |
| [`FinesTest::testRenavamKeepsLeadingZerosAndMayBeJustifiedlyMissing`](codigo/testes/casos.md) | method | public | `testRenavamKeepsLeadingZerosAndMayBeJustifiedlyMissing(): void` | 127 | ✓ |
| [`FinesTest::testSpeedsStayTranscribedAndIncoherentSpeedBlocksOnlyRegistration`](codigo/testes/casos.md) | method | public | `testSpeedsStayTranscribedAndIncoherentSpeedBlocksOnlyRegistration(): void` | 146 | ✓ |
| [`FinesTest::testDocumentChecksAreTemporalAndRetificationRequiresANewOne`](codigo/testes/casos.md) | method | public | `testDocumentChecksAreTemporalAndRetificationRequiresANewOne(): void` | 169 | ✓ |
| [`FinesTest::testPendingOnTheOccurrenceDayLeavesToReviewOnlyWhenRegularityIsInformedLater`](codigo/testes/casos.md) | method | public | `testPendingOnTheOccurrenceDayLeavesToReviewOnlyWhenRegularityIsInformedLater(): void` | 223 | ✓ |
| [`FinesTest::testRadarWithoutTheInfractionAmongItsFunctionsNeedsAnExplanation`](codigo/testes/casos.md) | method | public | `testRadarWithoutTheInfractionAmongItsFunctionsNeedsAnExplanation(): void` | 249 | ✓ |
| [`FinesTest::testNeverInstalledOrFixedRadarWithAnOperatorNeedsAnExplanation`](codigo/testes/casos.md) | method | public | `testNeverInstalledOrFixedRadarWithAnOperatorNeedsAnExplanation(): void` | 265 | ✓ |
| [`FinesTest::testReviewReminderAnswerIsStoredAsADateAndCannotPrecedeTheCheckedDay`](codigo/testes/casos.md) | method | public | `testReviewReminderAnswerIsStoredAsADateAndCannotPrecedeTheCheckedDay(): void` | 288 | ✓ |
| [`FinesTest::testLaterCheckReminderFollowsUpFromItsDayAndTheLastCheckDecides`](codigo/testes/casos.md) | method | public | `testLaterCheckReminderFollowsUpFromItsDayAndTheLastCheckDecides(): void` | 310 | ✓ |
| [`FinesTest::testRetificationCannotPlaceTheOccurrenceAfterFactsAlreadyRecorded`](codigo/testes/casos.md) | method | public | `testRetificationCannotPlaceTheOccurrenceAfterFactsAlreadyRecorded(): void` | 352 | ✓ |
| [`FinesTest::testProgressDimensionsAreIndependentAndCorrectionsNeedAReason`](codigo/testes/casos.md) | method | public | `testProgressDimensionsAreIndependentAndCorrectionsNeedAReason(): void` | 382 | ✓ |
| [`FinesTest::testCancellationIsInternalReadOnlyAndRepeatedCommandsDoNotDuplicate`](codigo/testes/casos.md) | method | public | `testCancellationIsInternalReadOnlyAndRepeatedCommandsDoNotDuplicate(): void` | 410 | ✓ |
| [`FinesTest::testSameCommandSentTwiceAtTheSameTimeIsAReplayNotAConflict`](codigo/testes/casos.md) | method | public | `testSameCommandSentTwiceAtTheSameTimeIsAReplayNotAConflict(): void` | 436 | ✓ |
| [`FinesTest::testVersionAndCommandKeyProtectAgainstLostUpdatesAndReplays`](codigo/testes/casos.md) | method | public | `testVersionAndCommandKeyProtectAgainstLostUpdatesAndReplays(): void` | 497 | ✓ |
| [`FinesTest::testAitIsUniquePerAgencyAndAbsentAitsNeverCollide`](codigo/testes/casos.md) | method | public | `testAitIsUniquePerAgencyAndAbsentAitsNeverCollide(): void` | 533 | ✓ |
| [`FinesTest::testConcurrentCreationsResolveAitAndCommandKeyInTheDatabase`](codigo/testes/casos.md) | method | public | `testConcurrentCreationsResolveAitAndCommandKeyInTheDatabase(): void` | 550 | ✓ |
| [`FinesTest::testFailureWritingHistoryRollsBackTheWholeOperation`](codigo/testes/casos.md) | method | public | `testFailureWritingHistoryRollsBackTheWholeOperation(): void` | 569 | ✓ |
| [`FinesTest::testApplicationIdentityCannotRewriteFineHistory`](codigo/testes/casos.md) | method | public | `testApplicationIdentityCannotRewriteFineHistory(): void` | 585 | ✓ |
| [`FinesTest::testSnapshotSurvivesLaterArchivingAndArchivedRadarStillServesHistory`](codigo/testes/casos.md) | method | public | `testSnapshotSurvivesLaterArchivingAndArchivedRadarStillServesHistory(): void` | 609 | ✓ |
| [`FinesTest::testListTotalsAndSummariesShareTheSameFilterWithoutDuplicates`](codigo/testes/casos.md) | method | public | `testListTotalsAndSummariesShareTheSameFilterWithoutDuplicates(): void` | 624 | ✓ |
| [`FinesTest::testNonDestructiveSeedAddsTheScenarioOnceAndRefusesUnmarkedDatabases`](codigo/testes/casos.md) | method | public | `testNonDestructiveSeedAddsTheScenarioOnceAndRefusesUnmarkedDatabases(): void` | 661 | ✓ |

### InventoryTest.php

Fonte: [`tests/Integration/InventoryTest.php`](../../tests/Integration/InventoryTest.php) · Referência: [casos](codigo/testes/casos.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`InventoryTest`](codigo/testes/casos.md#inventorytest--inventário-equipamentosapplicationequipamentosmd-agentesapplicationagentesmd) | class | undefined | `` | 13 | ✓ |
| [`InventoryTest::testCreatePersistsTypeSpecificSpecsAndHistory`](codigo/testes/casos.md) | method | public | `testCreatePersistsTypeSpecificSpecsAndHistory(): void` | 15 | ✓ |
| [`InventoryTest::testCodesKeepCountingPastTheMinimumWidth`](codigo/testes/casos.md) | method | public | `testCodesKeepCountingPastTheMinimumWidth(): void` | 28 | ✓ |
| [`InventoryTest::testSerialIsUniquePerManufacturerAndUnknownSerialsDoNotCollide`](codigo/testes/casos.md) | method | public | `testSerialIsUniquePerManufacturerAndUnknownSerialsDoNotCollide(): void` | 49 | ✓ |
| [`InventoryTest::testConcurrentDuplicateSerialIsResolvedByTheDatabase`](codigo/testes/casos.md) | method | public | `testConcurrentDuplicateSerialIsResolvedByTheDatabase(): void` | 64 | ✓ |
| [`InventoryTest::testStaleFormVersionIsRejectedWithoutLosingTheOtherEdit`](codigo/testes/casos.md) | method | public | `testStaleFormVersionIsRejectedWithoutLosingTheOtherEdit(): void` | 83 | ✓ |
| [`InventoryTest::testPositionAndTelemetryDoNotInvalidateAnOpenRegistrationForm`](codigo/testes/casos.md) | method | public | `testPositionAndTelemetryDoNotInvalidateAnOpenRegistrationForm(): void` | 100 | ✓ |
| [`InventoryTest::testAgentStatusChangeDoesNotInvalidateAnOpenAgentForm`](codigo/testes/casos.md) | method | public | `testAgentStatusChangeDoesNotInvalidateAnOpenAgentForm(): void` | 117 | ✓ |
| [`InventoryTest::testInstallationChangesTheRegistrationAndInvalidatesAnOlderForm`](codigo/testes/casos.md) | method | public | `testInstallationChangesTheRegistrationAndInvalidatesAnOlderForm(): void` | 128 | ✓ |
| [`InventoryTest::testPendingChangesCompareTheTypedVersionWithTheSavedOne`](codigo/testes/casos.md) | method | public | `testPendingChangesCompareTheTypedVersionWithTheSavedOne(): void` | 138 | ✓ |
| [`InventoryTest::testStatusTransitionsAreEnforcedAndRecorded`](codigo/testes/casos.md) | method | public | `testStatusTransitionsAreEnforcedAndRecorded(): void` | 147 | ✓ |
| [`InventoryTest::testLocationKindFollowsTheStatusOnCreationAndInTransitions`](codigo/testes/casos.md) | method | public | `testLocationKindFollowsTheStatusOnCreationAndInTransitions(): void` | 167 | ✓ |
| [`InventoryTest::testArchivingRemovesFromOperationButKeepsHistory`](codigo/testes/casos.md) | method | public | `testArchivingRemovesFromOperationButKeepsHistory(): void` | 192 | ✓ |
| [`InventoryTest::testMobileResourceKeepsBaseSeparateFromObservedPosition`](codigo/testes/casos.md) | method | public | `testMobileResourceKeepsBaseSeparateFromObservedPosition(): void` | 203 | ✓ |
| [`InventoryTest::userId`](codigo/testes/casos.md) | method | private | `userId(): int` | 222 | ✓ |
| [`InventoryTest::rawRow`](codigo/testes/casos.md) | method | private | `rawRow(string $serial, string $code): array` | 228 | ✓ |

### MaintenanceInvariantsTest.php

Fonte: [`tests/Integration/MaintenanceInvariantsTest.php`](../../tests/Integration/MaintenanceInvariantsTest.php) · Referência: [casos](codigo/testes/casos.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`MaintenanceInvariantsTest`](codigo/testes/casos.md#maintenanceinvariantstest--manutenção-manutençãoapplicationmanutencaomd) | class | undefined | `` | 17 | ✓ |
| [`MaintenanceInvariantsTest::testSecondInterventionWaitsForTheFirstAndCompletionReleasesOnlyThen`](codigo/testes/casos.md) | method | public | `testSecondInterventionWaitsForTheFirstAndCompletionReleasesOnlyThen(): void` | 21 | ✓ |
| [`MaintenanceInvariantsTest::testOrdersCanBeScheduledInAnyStatusButStartOnlyWhereTheTransitionExists`](codigo/testes/casos.md) | method | public | `testOrdersCanBeScheduledInAnyStatusButStartOnlyWhereTheTransitionExists(): void` | 44 | ✓ |
| [`MaintenanceInvariantsTest::testPendingOrdersAreAllReadEvenBehindMoreThanThirtyClosedOnes`](codigo/testes/casos.md) | method | public | `testPendingOrdersAreAllReadEvenBehindMoreThanThirtyClosedOnes(): void` | 65 | ✓ |
| [`MaintenanceInvariantsTest::testDatabaseRejectsTwoRunningInterventionsEvenOutsideTheService`](codigo/testes/casos.md) | method | public | `testDatabaseRejectsTwoRunningInterventionsEvenOutsideTheService(): void` | 81 | ✓ |
| [`MaintenanceInvariantsTest::testManualStatusChangeCannotReleaseARunningIntervention`](codigo/testes/casos.md) | method | public | `testManualStatusChangeCannotReleaseARunningIntervention(): void` | 92 | ✓ |
| [`MaintenanceInvariantsTest::testArchiveIsRefusedWhileAnInterventionRunsAndTheOrderCanStillBeCompleted`](codigo/testes/casos.md) | method | public | `testArchiveIsRefusedWhileAnInterventionRunsAndTheOrderCanStillBeCompleted(): void` | 107 | ✓ |
| [`MaintenanceInvariantsTest::testScheduledOrdersAreCancelledOnlyWithExplicitConfirmationAndKeepAuthorship`](codigo/testes/casos.md) | method | public | `testScheduledOrdersAreCancelledOnlyWithExplicitConfirmationAndKeepAuthorship(): void` | 126 | ✓ |
| [`MaintenanceInvariantsTest::testConcurrentStartsOnTheSameEquipmentAdmitExactlyOne`](codigo/testes/casos.md) | method | public | `testConcurrentStartsOnTheSameEquipmentAdmitExactlyOne(): void` | 149 | ✓ |
| [`MaintenanceInvariantsTest::testStartRacingArchiveNeverLeavesAnUnfinishableOrder`](codigo/testes/casos.md) | method | public | `testStartRacingArchiveNeverLeavesAnUnfinishableOrder(): void` | 163 | ✓ |
| [`MaintenanceInvariantsTest::openOrder`](codigo/testes/casos.md) | method | private | `openOrder(int $equipmentId): int` | 176 | ✓ |
| [`MaintenanceInvariantsTest::state`](codigo/testes/casos.md) | method | private | `state(int $orderId): string` | 181 | ✓ |
| [`MaintenanceInvariantsTest::userId`](codigo/testes/casos.md) | method | private | `userId(): int` | 186 | ✓ |

### OperationsTest.php

Fonte: [`tests/Integration/OperationsTest.php`](../../tests/Integration/OperationsTest.php) · Referência: [casos](codigo/testes/casos.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`OperationsTest`](codigo/testes/casos.md#operationstest--ingestão-alertas-e-simulador-ingestãoapplicationingestaomd-alertasapplicationalertasmd-consolecliconsolemd) | class | undefined | `` | 14 | ✓ |
| [`OperationsTest::testDuplicateEventIsIgnoredAndCounted`](codigo/testes/casos.md) | method | public | `testDuplicateEventIsIgnoredAndCounted(): void` | 18 | ✓ |
| [`OperationsTest::testLateEventGoesToHistoryWithoutRegressingCurrentState`](codigo/testes/casos.md) | method | public | `testLateEventGoesToHistoryWithoutRegressingCurrentState(): void` | 30 | ✓ |
| [`OperationsTest::testAdministrativeStatusPrevailsOverTelemetry`](codigo/testes/casos.md) | method | public | `testAdministrativeStatusPrevailsOverTelemetry(): void` | 46 | ✓ |
| [`OperationsTest::testOlderPositionDoesNotReplaceNewerOne`](codigo/testes/casos.md) | method | public | `testOlderPositionDoesNotReplaceNewerOne(): void` | 56 | ✓ |
| [`OperationsTest::testAlertLifecycleIsIdempotentAndAcknowledgeDoesNotResolve`](codigo/testes/casos.md) | method | public | `testAlertLifecycleIsIdempotentAndAcknowledgeDoesNotResolve(): void` | 67 | ✓ |
| [`OperationsTest::testConcurrentReconcileNeverDuplicatesAnOpenAlert`](codigo/testes/casos.md) | method | public | `testConcurrentReconcileNeverDuplicatesAnOpenAlert(): void` | 93 | ✓ |
| [`OperationsTest::testMaintenanceLifecycle`](codigo/testes/casos.md) | method | public | `testMaintenanceLifecycle(): void` | 103 | ✓ |
| [`OperationsTest::testUrgentSummaryCountsOnlyOrdersWaitingToStart`](codigo/testes/casos.md) | method | public | `testUrgentSummaryCountsOnlyOrdersWaitingToStart(): void` | 139 | ✓ |
| [`OperationsTest::testCompletionCanReportThatEquipmentStillFails`](codigo/testes/casos.md) | method | public | `testCompletionCanReportThatEquipmentStillFails(): void` | 156 | ✓ |
| [`OperationsTest::testSimulatorTickIsSkippedWhileAnotherTickHoldsTheLock`](codigo/testes/casos.md) | method | public | `testSimulatorTickIsSkippedWhileAnotherTickHoldsTheLock(): void` | 168 | ✓ |
| [`OperationsTest::userId`](codigo/testes/casos.md) | method | private | `userId(): int` | 192 | ✓ |

### PlanningTest.php

Fonte: [`tests/Integration/PlanningTest.php`](../../tests/Integration/PlanningTest.php) · Referência: [casos](codigo/testes/casos.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`PlanningTest`](codigo/testes/casos.md#planningtest--planejamento-planejamentoapplicationplanejamentomd-importaçãoapplicationimportacao-urbanamd) | class | undefined | `` | 18 | ✓ |
| [`PlanningTest::testImportPersistsTheRunWithFullPrecisionAndIsIdempotent`](codigo/testes/casos.md) | method | public | `testImportPersistsTheRunWithFullPrecisionAndIsIdempotent(): void` | 23 | ✓ |
| [`PlanningTest::testGridIsAddedToAnExistingRunOnceAndShownWithTheWeek`](codigo/testes/casos.md) | method | public | `testGridIsAddedToAnExistingRunOnceAndShownWithTheWeek(): void` | 47 | ✓ |
| [`PlanningTest::testInvalidRunLeavesNothingWritten`](codigo/testes/casos.md) | method | public | `testInvalidRunLeavesNothingWritten(): void` | 69 | ✓ |
| [`PlanningTest::testImportedRunCannotBeRewrittenByTheApplicationUser`](codigo/testes/casos.md) | method | public | `testImportedRunCannotBeRewrittenByTheApplicationUser(): void` | 89 | ✓ |
| [`PlanningTest::testPoissonComparisonUsesItsOwnValuesWithoutChangingTheImportedRun`](codigo/testes/casos.md) | method | public | `testPoissonComparisonUsesItsOwnValuesWithoutChangingTheImportedRun(): void` | 97 | ✓ |
| [`PlanningTest::testSuggestionOnlyListsFreeTrafficAgentsAndFlagsPositionsThatAreNotCurrent`](codigo/testes/casos.md) | method | public | `testSuggestionOnlyListsFreeTrafficAgentsAndFlagsPositionsThatAreNotCurrent(): void` | 117 | ✓ |
| [`PlanningTest::testConfirmationRechecksEligibilityAndRequiresJustificationForAManualChoice`](codigo/testes/casos.md) | method | public | `testConfirmationRechecksEligibilityAndRequiresJustificationForAManualChoice(): void` | 146 | ✓ |
| [`PlanningTest::testTheAgentSuggestedOnScreenNeedsNoJustificationEvenIfTheOrderChangedBeforeSending`](codigo/testes/casos.md) | method | public | `testTheAgentSuggestedOnScreenNeedsNoJustificationEvenIfTheOrderChangedBeforeSending(): void` | 173 | ✓ |
| [`PlanningTest::testMissionForASquareMeasuresDistanceToItAndKeepsTheSquare`](codigo/testes/casos.md) | method | public | `testMissionForASquareMeasuresDistanceToItAndKeepsTheSquare(): void` | 193 | ✓ |
| [`PlanningTest::testClosingReleasesTheAgentAndKeepsTheHistory`](codigo/testes/casos.md) | method | public | `testClosingReleasesTheAgentAndKeepsTheHistory(): void` | 215 | ✓ |
| [`PlanningTest::testTheDatabaseRefusesASecondActiveMissionForTheSameAgent`](codigo/testes/casos.md) | method | public | `testTheDatabaseRefusesASecondActiveMissionForTheSameAgent(): void` | 233 | ✓ |
| [`PlanningTest::testTwoManagersConfirmingTheSameAgentAtOnceGetOneMissionAndOneConflict`](codigo/testes/casos.md) | method | public | `testTwoManagersConfirmingTheSameAgentAtOnceGetOneMissionAndOneConflict(): void` | 245 | ✓ |
| [`PlanningTest::testSimulatorTicksNeverTouchAConfirmedMission`](codigo/testes/casos.md) | method | public | `testSimulatorTicksNeverTouchAConfirmedMission(): void` | 264 | ✓ |
| [`PlanningTest::testFinishedPeriodRefusesNewMissions`](codigo/testes/casos.md) | method | public | `testFinishedPeriodRefusesNewMissions(): void` | 279 | ✓ |
| [`PlanningTest::testReplayShowsFrozenPredictionsForEveryWeekAndRevealsObservedOnlyOnRequest`](codigo/testes/casos.md) | method | public | `testReplayShowsFrozenPredictionsForEveryWeekAndRevealsObservedOnlyOnRequest(): void` | 289 | ✓ |
| [`PlanningTest::import`](codigo/testes/casos.md) | method | private | `import(): array` | 312 | ✓ |
| [`PlanningTest::counts`](codigo/testes/casos.md) | method | private | `counts(): array` | 318 | ✓ |
| [`PlanningTest::agentAt`](codigo/testes/casos.md) | method | private | `agentAt(string $callSign, string $lat, string $lng, string $age = '-1 minute'): int` | 324 | ✓ |
| [`PlanningTest::confirm`](codigo/testes/casos.md) | method | private | `confirm(int $runId, int $agentId, array $extra = []): int` | 334 | ✓ |
| [`PlanningTest::assertRefused`](codigo/testes/casos.md) | method | private | `assertRefused(callable $action, string $message, string $class = RuleViolation::class): void` | 341 | ✓ |
| [`PlanningTest::userId`](codigo/testes/casos.md) | method | private | `userId(): int` | 352 | ✓ |

### UrbanUpdaterTest.php

Fonte: [`tests/Integration/UrbanUpdaterTest.php`](../../tests/Integration/UrbanUpdaterTest.php) · Referência: [casos](codigo/testes/casos.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`UrbanUpdaterTest`](codigo/testes/casos.md#urbanupdatertest--atualização-mensalsemanal-atualização-urbanaapplicationatualizacao-urbanamd) | class | undefined | `` | 21 | ✓ |
| [`UrbanUpdaterTest::setUp`](codigo/testes/casos.md) | method | protected | `setUp(): void` | 28 | ✓ |
| [`UrbanUpdaterTest::tearDown`](codigo/testes/casos.md) | method | protected | `tearDown(): void` | 35 | ✓ |
| [`UrbanUpdaterTest::testWeeklyForecastFromTheMonthlyFileReproducesTheReferenceRun`](codigo/testes/casos.md) | method | public | `testWeeklyForecastFromTheMonthlyFileReproducesTheReferenceRun(): void` | 40 | ✓ |
| [`UrbanUpdaterTest::testSameFileAndSameWeekAreNotStoredTwice`](codigo/testes/casos.md) | method | public | `testSameFileAndSameWeekAreNotStoredTwice(): void` | 66 | ✓ |
| [`UrbanUpdaterTest::testIdenticalFileInTheNextMonthCountsAsThatMonthsCheck`](codigo/testes/casos.md) | method | public | `testIdenticalFileInTheNextMonthCountsAsThatMonthsCheck(): void` | 86 | ✓ |
| [`UrbanUpdaterTest::testFiltersKeepOnlyEligibleMariliaRecordsOfTheLastTwelveMonths`](codigo/testes/casos.md) | method | public | `testFiltersKeepOnlyEligibleMariliaRecordsOfTheLastTwelveMonths(): void` | 105 | ✓ |
| [`UrbanUpdaterTest::testBrokenFilesAreRefusedWithoutWritingAndDownloadFailureKeepsTheForecastStep`](codigo/testes/casos.md) | method | public | `testBrokenFilesAreRefusedWithoutWritingAndDownloadFailureKeepsTheForecastStep(): void` | 131 | ✓ |
| [`UrbanUpdaterTest::testPoissonFormulaMatchesThePythonParityVectors`](codigo/testes/casos.md) | method | public | `testPoissonFormulaMatchesThePythonParityVectors(): void` | 148 | ✓ |
| [`UrbanUpdaterTest::importReference`](codigo/testes/casos.md) | method | private | `importReference(): int` | 157 | ✓ |
| [`UrbanUpdaterTest::zipFromGrid`](codigo/testes/casos.md) | method | private | `zipFromGrid(): string` | 168 | ✓ |
| [`UrbanUpdaterTest::zip`](codigo/testes/casos.md) | method | private | `zip(array $rows, string $member = 'sinistros_2025-2026.csv', string $header = self::HEADER): string` | 196 | ✓ |
| [`UrbanUpdaterTest::temp`](codigo/testes/casos.md) | method | private | `temp(string $suffix): string` | 207 | ✓ |
| [`UrbanUpdaterTest::assertRefusal`](codigo/testes/casos.md) | method | private | `assertRefusal(callable $action, string $class, string $message): void` | 213 | ✓ |

## tests/Support

### ArraySession.php

Fonte: [`tests/Support/ArraySession.php`](../../tests/Support/ArraySession.php) · Referência: [suporte](codigo/testes/suporte.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`ArraySession`](codigo/testes/suporte.md) | class | undefined | `` | 10 | ✓ |
| [`ArraySession::__construct`](codigo/testes/suporte.md) | method | public | `__construct()` | 14 | ✓ |
| [`ArraySession::start`](codigo/testes/suporte.md) | method | public | `start(): void` | 19 | ✓ |
| [`ArraySession::regenerate`](codigo/testes/suporte.md) | method | public | `regenerate(): void` | 24 | ✓ |
| [`ArraySession::destroy`](codigo/testes/suporte.md) | method | public | `destroy(): void` | 30 | ✓ |

### DatabaseSeparation.php

Fonte: [`tests/Support/DatabaseSeparation.php`](../../tests/Support/DatabaseSeparation.php) · Referência: [suporte](codigo/testes/suporte.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`DatabaseSeparation`](codigo/testes/suporte.md#databaseseparation) | class | undefined | `` | 12 | ✓ |
| [`DatabaseSeparation::problem`](codigo/testes/suporte.md) | method | public static | `problem(string $mainDsn, string $testDsn, ?string $mainFile = null, ?string $testFile = null): ?string` | 15 | ✓ |
| [`DatabaseSeparation::target`](codigo/testes/suporte.md) | method | private static | `target(string $dsn): string` | 31 | ✓ |

### FakeCepLookup.php

Fonte: [`tests/Support/FakeCepLookup.php`](../../tests/Support/FakeCepLookup.php) · Referência: [suporte](codigo/testes/suporte.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`FakeCepLookup`](codigo/testes/suporte.md) | class | undefined | `` | 12 | ✓ |
| [`FakeCepLookup::__construct`](codigo/testes/suporte.md) | method | public | `__construct(private readonly array $answers = [], public bool $down = false)` | 18 | ✓ |
| [`FakeCepLookup::find`](codigo/testes/suporte.md) | method | public | `find(string $cep): ?CepAddress` | 20 | ✓ |

### FakeGeocoder.php

Fonte: [`tests/Support/FakeGeocoder.php`](../../tests/Support/FakeGeocoder.php) · Referência: [suporte](codigo/testes/suporte.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`FakeGeocoder`](codigo/testes/suporte.md) | class | undefined | `` | 11 | ✓ |
| [`FakeGeocoder::__construct`](codigo/testes/suporte.md) | method | public | `__construct(private readonly array $points = [], public bool $down = false)` | 17 | ✓ |
| [`FakeGeocoder::locate`](codigo/testes/suporte.md) | method | public | `locate(string $street): ?array` | 19 | ✓ |

### FirebirdTestCase.php

Fonte: [`tests/Support/FirebirdTestCase.php`](../../tests/Support/FirebirdTestCase.php) · Referência: [suporte](codigo/testes/suporte.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`FirebirdTestCase`](codigo/testes/suporte.md#firebirdtestcase) | class | undefined | `` | 21 | ✓ |
| [`FirebirdTestCase::setUpBeforeClass`](codigo/testes/suporte.md) | method | public static | `setUpBeforeClass(): void` | 31 | ✓ |
| [`FirebirdTestCase::setUp`](codigo/testes/suporte.md) | method | protected | `setUp(): void` | 46 | ✓ |
| [`FirebirdTestCase::config`](codigo/testes/suporte.md) | method | public static | `config(): Config` | 63 | ✓ |
| [`FirebirdTestCase::assertSeparateFromMainDatabase`](codigo/testes/suporte.md) | method | private static | `assertSeparateFromMainDatabase(Database $testOwner): void` | 74 | ✓ |
| [`FirebirdTestCase::ownerDatabase`](codigo/testes/suporte.md) | method | protected static | `ownerDatabase(): Database` | 95 | ✓ |
| [`FirebirdTestCase::newServices`](codigo/testes/suporte.md) | method | protected | `newServices(): Services` | 103 | ✓ |
| [`FirebirdTestCase::equipmentData`](codigo/testes/suporte.md) | method | protected | `equipmentData(array $override = []): array` | 109 | ✓ |
| [`FirebirdTestCase::createEquipment`](codigo/testes/suporte.md) | method | protected | `createEquipment(array $override = [], ?Services $services = null): int` | 120 | ✓ |
| [`FirebirdTestCase::createAgent`](codigo/testes/suporte.md) | method | protected | `createAgent(array $override = []): int` | 126 | ✓ |
| [`FirebirdTestCase::equipmentStatus`](codigo/testes/suporte.md) | method | protected | `equipmentStatus(int $id): string` | 134 | ✓ |

### Interleaving.php

Fonte: [`tests/Support/Interleaving.php`](../../tests/Support/Interleaving.php) · Referência: [suporte](codigo/testes/suporte.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`Interleaving`](codigo/testes/suporte.md#concorrência-parallel-e-interleaving) | class | undefined | `` | 14 | ✓ |
| [`Interleaving::connect`](codigo/testes/suporte.md) | method | public static | `connect(Services $services): Services` | 20 | ✓ |
| [`Interleaving::after`](codigo/testes/suporte.md) | method | public static | `after(string $sql): void` | 50 | ✓ |
| [`PausingStatement`](codigo/testes/suporte.md) | class | undefined | `` | 63 | ✓ |
| [`PausingStatement::__construct`](codigo/testes/suporte.md) | method | protected | `__construct()` | 65 | ✓ |
| [`PausingStatement::execute`](codigo/testes/suporte.md) | method | public | `execute(?array $params = null): bool` | 67 | ✓ |

### Parallel.php

Fonte: [`tests/Support/Parallel.php`](../../tests/Support/Parallel.php) · Referência: [suporte](codigo/testes/suporte.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`Parallel`](codigo/testes/suporte.md#concorrência-parallel-e-interleaving) | class | undefined | `` | 11 | ✓ |
| [`Parallel::run`](codigo/testes/suporte.md) | method | public static | `run(array $jobs, \DateTimeImmutable $now): array` | 17 | ✓ |

## tests/Unit

### AddressLocatorTest.php

Fonte: [`tests/Unit/AddressLocatorTest.php`](../../tests/Unit/AddressLocatorTest.php) · Referência: [casos](codigo/testes/casos.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`AddressLocatorTest`](codigo/testes/casos.md#addresslocatortest--localização-pelo-endereço-endereçoapplicationenderecomd) | class | undefined | `` | 17 | ✓ |
| [`AddressLocatorTest::geocoder`](codigo/testes/casos.md) | method | private | `geocoder(array $reply): NominatimGeocoder` | 25 | ✓ |
| [`AddressLocatorTest::testStreetInMariliaBecomesAPointAndOnlyTheStreetGoesOut`](codigo/testes/casos.md) | method | public | `testStreetInMariliaBecomesAPointAndOnlyTheStreetGoesOut(): void` | 34 | ✓ |
| [`AddressLocatorTest::testStreetNotFoundOrOutsideTheAreaIsNotFound`](codigo/testes/casos.md) | method | public | `testStreetNotFoundOrOutsideTheAreaIsNotFound(): void` | 49 | ✓ |
| [`AddressLocatorTest::testProviderDownTimeoutOrOddAnswersAreUnavailable`](codigo/testes/casos.md) | method | public | `testProviderDownTimeoutOrOddAnswersAreUnavailable(): void` | 55 | ✓ |
| [`AddressLocatorTest::testLocatorUsesTheTypedStreetOrTheStreetOfTheCep`](codigo/testes/casos.md) | method | public | `testLocatorUsesTheTypedStreetOrTheStreetOfTheCep(): void` | 67 | ✓ |
| [`AddressLocatorTest::testAddressAreaIsTheStreetStretchOrTheHouse`](codigo/testes/casos.md) | method | public | `testAddressAreaIsTheStreetStretchOrTheHouse(): void` | 98 | ✓ |
| [`AddressLocatorTest::testGeocoderAddressComesFromConfigAndMustBeHttps`](codigo/testes/casos.md) | method | public | `testGeocoderAddressComesFromConfigAndMustBeHttps(): void` | 110 | ✓ |

### AttentionModelTest.php

Fonte: [`tests/Unit/AttentionModelTest.php`](../../tests/Unit/AttentionModelTest.php) · Referência: [casos](codigo/testes/casos.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`AttentionModelTest`](codigo/testes/casos.md#attentionmodeltest--heurística-de-revisão-atenção-preventivadomainatencao-preventivamd) | class | undefined | `` | 10 | ✓ |
| [`AttentionModelTest::features`](codigo/testes/casos.md) | method | private static | `features(array $override = []): array` | 13 | ✓ |
| [`AttentionModelTest::testQuietEquipmentIsLowAttention`](codigo/testes/casos.md) | method | public | `testQuietEquipmentIsLowAttention(): void` | 21 | ✓ |
| [`AttentionModelTest::testRecentFailuresAndOverduePreventiveRaiseAttentionWithExplanations`](codigo/testes/casos.md) | method | public | `testRecentFailuresAndOverduePreventiveRaiseAttentionWithExplanations(): void` | 30 | ✓ |
| [`AttentionModelTest::testShortHistoryIsInsufficientNotLowRisk`](codigo/testes/casos.md) | method | public | `testShortHistoryIsInsufficientNotLowRisk(): void` | 41 | ✓ |
| [`AttentionModelTest::testAlreadyFailingIsCorrectiveNotPrediction`](codigo/testes/casos.md) | method | public | `testAlreadyFailingIsCorrectiveNotPrediction(): void` | 49 | ✓ |
| [`AttentionModelTest::testF1ComesFromCountsAndKeepsZeroDistinctFromUndefined`](codigo/testes/casos.md) | method | public | `testF1ComesFromCountsAndKeepsZeroDistinctFromUndefined(): void` | 61 | ✓ |
| [`AttentionModelTest::testMetricsHandleEmptyAndCountConfusionMatrix`](codigo/testes/casos.md) | method | public | `testMetricsHandleEmptyAndCountConfusionMatrix(): void` | 86 | ✓ |

### CepLookupTest.php

Fonte: [`tests/Unit/CepLookupTest.php`](../../tests/Unit/CepLookupTest.php) · Referência: [casos](codigo/testes/casos.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`CepLookupTest`](codigo/testes/casos.md#ceplookuptest--consulta-de-cep-endereçoapplicationenderecomd) | class | undefined | `` | 14 | ✓ |
| [`CepLookupTest::client`](codigo/testes/casos.md) | method | private | `client(array $reply): ViaCepClient` | 22 | ✓ |
| [`CepLookupTest::testValidCepReturnsStreetAndOnlyTheDigitsGoOutWithAShortTimeout`](codigo/testes/casos.md) | method | public | `testValidCepReturnsStreetAndOnlyTheDigitsGoOutWithAShortTimeout(): void` | 31 | ✓ |
| [`CepLookupTest::testCepThatDoesNotExistIsNotFound`](codigo/testes/casos.md) | method | public | `testCepThatDoesNotExistIsNotFound(): void` | 41 | ✓ |
| [`CepLookupTest::testGeneralCityCepComesWithoutStreet`](codigo/testes/casos.md) | method | public | `testGeneralCityCepComesWithoutStreet(): void` | 47 | ✓ |
| [`CepLookupTest::testCepOfAnotherCityIsRecognized`](codigo/testes/casos.md) | method | public | `testCepOfAnotherCityIsRecognized(): void` | 56 | ✓ |
| [`CepLookupTest::testProviderDownOrOddAnswersAreUnavailable`](codigo/testes/casos.md) | method | public | `testProviderDownOrOddAnswersAreUnavailable(): void` | 63 | ✓ |
| [`CepLookupTest::testTimeoutIsUnavailable`](codigo/testes/casos.md) | method | public | `testTimeoutIsUnavailable(): void` | 75 | ✓ |
| [`CepLookupTest::testStreetIsCutToTheColumnSize`](codigo/testes/casos.md) | method | public | `testStreetIsCutToTheColumnSize(): void` | 82 | ✓ |
| [`CepLookupTest::testProviderAddressComesFromConfigAndMustBeHttps`](codigo/testes/casos.md) | method | public | `testProviderAddressComesFromConfigAndMustBeHttps(): void` | 90 | ✓ |

### DatabaseSeparationTest.php

Fonte: [`tests/Unit/DatabaseSeparationTest.php`](../../tests/Unit/DatabaseSeparationTest.php) · Referência: [casos](codigo/testes/casos.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`DatabaseSeparationTest`](codigo/testes/casos.md#databaseseparationtest--proteção-do-banco-principal-suportesuportemddatabaseseparation) | class | undefined | `` | 11 | ✓ |
| [`DatabaseSeparationTest::testSameTargetIsRefusedEvenWithDifferentOptions`](codigo/testes/casos.md) | method | public | `testSameTargetIsRefusedEvenWithDifferentOptions(): void` | 16 | ✓ |
| [`DatabaseSeparationTest::testDifferentAliasesForTheSameFileAreRefused`](codigo/testes/casos.md) | method | public | `testDifferentAliasesForTheSameFileAreRefused(): void` | 21 | ✓ |
| [`DatabaseSeparationTest::testSeparateDatabasesOrNoMainDatabaseAreAccepted`](codigo/testes/casos.md) | method | public | `testSeparateDatabasesOrNoMainDatabaseAreAccepted(): void` | 26 | ✓ |

### DomainRulesTest.php

Fonte: [`tests/Unit/DomainRulesTest.php`](../../tests/Unit/DomainRulesTest.php) · Referência: [casos](codigo/testes/casos.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`DomainRulesTest`](codigo/testes/casos.md#domainrulestest--regras-de-domínio-situaçõesdomainsituacoes-e-atualidademd-indicadores-e-alertasdomainindicadores-e-alertasmd-tempodomaintempomd) | class | undefined | `` | 19 | ✓ |
| [`DomainRulesTest::testNearestRegionIsTheClosestCenter`](codigo/testes/casos.md) | method | public | `testNearestRegionIsTheClosestCenter(): void` | 22 | ✓ |
| [`DomainRulesTest::testAvailabilityExcludesNotInstalledAndDeactivatedFromDenominator`](codigo/testes/casos.md) | method | public | `testAvailabilityExcludesNotInstalledAndDeactivatedFromDenominator(): void` | 36 | ✓ |
| [`DomainRulesTest::testZeroDenominatorIsNotApplicableInsteadOfZeroOrHundredPercent`](codigo/testes/casos.md) | method | public | `testZeroDenominatorIsNotApplicableInsteadOfZeroOrHundredPercent(): void` | 56 | ✓ |
| [`DomainRulesTest::testAgentsAreCountedSeparately`](codigo/testes/casos.md) | method | public | `testAgentsAreCountedSeparately(): void` | 66 | ✓ |
| [`DomainRulesTest::transitions`](codigo/testes/casos.md) | method | public static | `transitions(): iterable` | 82 | ✓ |
| [`DomainRulesTest::testStatusTransitions`](codigo/testes/casos.md) | method | public | `testStatusTransitions(EquipmentStatus $from, EquipmentStatus $to, bool $allowed): void` | 93 | ✓ |
| [`DomainRulesTest::testStartBlockerFollowsTheTransitionTable`](codigo/testes/casos.md) | method | public | `testStartBlockerFollowsTheTransitionTable(): void` | 98 | ✓ |
| [`DomainRulesTest::testAdministrativeStatusesAreNotOverriddenByTelemetry`](codigo/testes/casos.md) | method | public | `testAdministrativeStatusesAreNotOverriddenByTelemetry(): void` | 107 | ✓ |
| [`DomainRulesTest::urgencies`](codigo/testes/casos.md) | method | public static | `urgencies(): iterable` | 116 | ✓ |
| [`DomainRulesTest::testMaintenanceUrgencyUsesLocalDates`](codigo/testes/casos.md) | method | public | `testMaintenanceUrgencyUsesLocalDates(string $priority, ?string $due, MaintenanceUrgency $expected): void` | 126 | ✓ |
| [`DomainRulesTest::testLocalDateCrossesMidnightInSaoPaulo`](codigo/testes/casos.md) | method | public | `testLocalDateCrossesMidnightInSaoPaulo(): void` | 131 | ✓ |
| [`DomainRulesTest::testFreshnessThresholds`](codigo/testes/casos.md) | method | public | `testFreshnessThresholds(): void` | 137 | ✓ |
| [`DomainRulesTest::testNextActionFollowsTheAlertFromOpenToFollowUp`](codigo/testes/casos.md) | method | public | `testNextActionFollowsTheAlertFromOpenToFollowUp(): void` | 147 | ✓ |
| [`DomainRulesTest::testAlertConditionsFromCurrentState`](codigo/testes/casos.md) | method | public | `testAlertConditionsFromCurrentState(): void` | 165 | ✓ |

### FineRulesTest.php

Fonte: [`tests/Unit/FineRulesTest.php`](../../tests/Unit/FineRulesTest.php) · Referência: [casos](codigo/testes/casos.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`FineRulesTest`](codigo/testes/casos.md#finerulestest--regras-das-multas-multasdomainmultasmd) | class | undefined | `` | 16 | ✓ |
| [`FineRulesTest::plates`](codigo/testes/casos.md) | method | public static | `plates(): iterable` | 19 | ✓ |
| [`FineRulesTest::testPlateFormatOnlyAfterRemovingPresentationPunctuation`](codigo/testes/casos.md) | method | public | `testPlateFormatOnlyAfterRemovingPresentationPunctuation(string $raw, bool $valid): void` | 29 | ✓ |
| [`FineRulesTest::testInternalCodeIsDerivedFromIdAndParsedBack`](codigo/testes/casos.md) | method | public | `testInternalCodeIsDerivedFromIdAndParsedBack(): void` | 34 | ✓ |
| [`FineRulesTest::testAitKeyKeepsPunctuationAndIgnoresCaseAndEdges`](codigo/testes/casos.md) | method | public | `testAitKeyKeepsPunctuationAndIgnoresCaseAndEdges(): void` | 44 | ✓ |
| [`FineRulesTest::money`](codigo/testes/casos.md) | method | public static | `money(): iterable` | 51 | ✓ |
| [`FineRulesTest::testBrazilianMoneyBecomesCentsWithoutFloat`](codigo/testes/casos.md) | method | public | `testBrazilianMoneyBecomesCentsWithoutFloat(string $raw, ?int $cents): void` | 66 | ✓ |
| [`FineRulesTest::testArrayWhereTextIsExpectedIsTreatedAsEmpty`](codigo/testes/casos.md) | method | public | `testArrayWhereTextIsExpectedIsTreatedAsEmpty(): void` | 73 | ✓ |
| [`FineRulesTest::testLocalDateTimeIsStrictAndConvertedToUtc`](codigo/testes/casos.md) | method | public | `testLocalDateTimeIsStrictAndConvertedToUtc(): void` | 81 | ✓ |
| [`FineRulesTest::testFilterPeriodIsInclusiveLocalAndHalfOpenInUtc`](codigo/testes/casos.md) | method | public | `testFilterPeriodIsInclusiveLocalAndHalfOpenInUtc(): void` | 91 | ✓ |
| [`FineRulesTest::complete`](codigo/testes/casos.md) | method | private static | `complete(array $override = []): array` | 106 | ✓ |
| [`FineRulesTest::testRegistrationNeedsMinimumFieldsButJustifiedAbsencesCount`](codigo/testes/casos.md) | method | public | `testRegistrationNeedsMinimumFieldsButJustifiedAbsencesCount(): void` | 118 | ✓ |
| [`FineRulesTest::testSpeedCoherenceIsCheckedWithoutInventingTolerance`](codigo/testes/casos.md) | method | public | `testSpeedCoherenceIsCheckedWithoutInventingTolerance(): void` | 126 | ✓ |
| [`FineRulesTest::speeds`](codigo/testes/casos.md) | method | public static | `speeds(): iterable` | 135 | ✓ |
| [`FineRulesTest::testConsideredSpeedFollowsTheContranMargin`](codigo/testes/casos.md) | method | public | `testConsideredSpeedFollowsTheContranMargin(int $measured, int $considered): void` | 146 | ✓ |
| [`FineRulesTest::testExpiredMeterVerificationOnTheOccurrenceDayNeedsAttention`](codigo/testes/casos.md) | method | public | `testExpiredMeterVerificationOnTheOccurrenceDayNeedsAttention(): void` | 151 | ✓ |
| [`FineRulesTest::testAttentionReasonsComeFromTheSameRuleAndCancelledHasNone`](codigo/testes/casos.md) | method | public | `testAttentionReasonsComeFromTheSameRuleAndCancelledHasNone(): void` | 159 | ✓ |
| [`FineRulesTest::testPendingDocumentsStopNeedingAttentionOnlyWhenRegularityIsInformedLater`](codigo/testes/casos.md) | method | public | `testPendingDocumentsStopNeedingAttentionOnlyWhenRegularityIsInformedLater(): void` | 179 | ✓ |
| [`FineRulesTest::testReviewReminderAnswerBecomesADateCountedFromToday`](codigo/testes/casos.md) | method | public | `testReviewReminderAnswerBecomesADateCountedFromToday(): void` | 193 | ✓ |
| [`FineRulesTest::testUnusualSequencesAreOnlyWarnings`](codigo/testes/casos.md) | method | public | `testUnusualSequencesAreOnlyWarnings(): void` | 211 | ✓ |
| [`FineRulesTest::testDocumentSelectionUsesCheckInstantSubjectVersionAndScope`](codigo/testes/casos.md) | method | public | `testDocumentSelectionUsesCheckInstantSubjectVersionAndScope(): void` | 219 | ✓ |

### HelpTipTest.php

Fonte: [`tests/Unit/HelpTipTest.php`](../../tests/Unit/HelpTipTest.php) · Referência: [casos](codigo/testes/casos.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`HelpTipTest`](codigo/testes/casos.md#helptiptest--ajuda-contextual--macrostemplatesmacros-e-telasmd) | class | undefined | `` | 16 | ✓ |
| [`HelpTipTest::render`](codigo/testes/casos.md) | method | private static | `render(string $source): string` | 18 | ✓ |
| [`HelpTipTest::testFieldsWithoutHelpTipKeepTheSharedStructure`](codigo/testes/casos.md) | method | public | `testFieldsWithoutHelpTipKeepTheSharedStructure(): void` | 28 | ✓ |
| [`HelpTipTest::testHelpTipIsAnAccessibleButtonNextToTheLabel`](codigo/testes/casos.md) | method | public | `testHelpTipIsAnAccessibleButtonNextToTheLabel(): void` | 43 | ✓ |
| [`HelpTipTest::testStatusAndSpecHelpComeFromTheDomain`](codigo/testes/casos.md) | method | public | `testStatusAndSpecHelpComeFromTheDomain(): void` | 71 | ✓ |
| [`HelpTipTest::testEveryCatalogFieldHasHelp`](codigo/testes/casos.md) | method | public | `testEveryCatalogFieldHasHelp(): void` | 98 | ✓ |

### InputAndSpecsTest.php

Fonte: [`tests/Unit/InputAndSpecsTest.php`](../../tests/Unit/InputAndSpecsTest.php) · Referência: [casos](codigo/testes/casos.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`InputAndSpecsTest`](codigo/testes/casos.md#inputandspecstest--fronteira-de-entrada-validação-e-filtrosapplicationvalidacao-e-filtrosmd-catálogodomaincatalogo-de-equipamentosmd-configuraçãoraizconfiguracao-e-servicosmd) | class | undefined | `` | 16 | ✓ |
| [`InputAndSpecsTest::testRadarRequiresSpeedLimitOnlyWhenEnforcingSpeed`](codigo/testes/casos.md) | method | public | `testRadarRequiresSpeedLimitOnlyWhenEnforcingSpeed(): void` | 18 | ✓ |
| [`InputAndSpecsTest::testCameraCapabilitiesAreStructured`](codigo/testes/casos.md) | method | public | `testCameraCapabilitiesAreStructured(): void` | 37 | ✓ |
| [`InputAndSpecsTest::testBoundaryInputNormalization`](codigo/testes/casos.md) | method | public | `testBoundaryInputNormalization(): void` | 48 | ✓ |
| [`InputAndSpecsTest::testFiltersDropUnknownValuesAndScopeResourceKinds`](codigo/testes/casos.md) | method | public | `testFiltersDropUnknownValuesAndScopeResourceKinds(): void` | 65 | ✓ |
| [`InputAndSpecsTest::testDotEnvParsingAndBasePathValidation`](codigo/testes/casos.md) | method | public | `testDotEnvParsingAndBasePathValidation(): void` | 81 | ✓ |
| [`InputAndSpecsTest::testTileOriginForCsp`](codigo/testes/casos.md) | method | public | `testTileOriginForCsp(): void` | 91 | ✓ |
| [`InputAndSpecsTest::testMigrationSplitterIgnoresCommentsAndSplitsAtLineEnd`](codigo/testes/casos.md) | method | public | `testMigrationSplitterIgnoresCommentsAndSplitsAtLineEnd(): void` | 98 | ✓ |

### UrbanImportTest.php

Fonte: [`tests/Unit/UrbanImportTest.php`](../../tests/Unit/UrbanImportTest.php) · Referência: [casos](codigo/testes/casos.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`UrbanImportTest`](codigo/testes/casos.md#urbanimporttest--pacote-do-planejamento-importaçãoapplicationimportacao-urbanamd-setoresdomainsetores-e-regioesmd) | class | undefined | `` | 14 | ✓ |
| [`UrbanImportTest::tearDown`](codigo/testes/casos.md) | method | protected | `tearDown(): void` | 20 | ✓ |
| [`UrbanImportTest::testReferenceCopyIsByteIdenticalToTheCodexManifest`](codigo/testes/casos.md) | method | public | `testReferenceCopyIsByteIdenticalToTheCodexManifest(): void` | 28 | ✓ |
| [`UrbanImportTest::testReferenceRunIsValidAndKeepsEveryDigit`](codigo/testes/casos.md) | method | public | `testReferenceRunIsValidAndKeepsEveryDigit(): void` | 37 | ✓ |
| [`UrbanImportTest::testReplayRowsReproduceTheDeclaredTestMetrics`](codigo/testes/casos.md) | method | public | `testReplayRowsReproduceTheDeclaredTestMetrics(): void` | 54 | ✓ |
| [`UrbanImportTest::testGridCellsMatchTheRunAndRankTheCityCentreFirst`](codigo/testes/casos.md) | method | public | `testGridCellsMatchTheRunAndRankTheCityCentreFirst(): void` | 66 | ✓ |
| [`UrbanImportTest::invalidGrids`](codigo/testes/casos.md) | method | public static | `invalidGrids(): iterable` | 80 | ✓ |
| [`UrbanImportTest::testGridOutsideTheContractIsRejected`](codigo/testes/casos.md) | method | public | `testGridOutsideTheContractIsRejected(\Closure $mutate, string $errorKeyPrefix): void` | 90 | ✓ |
| [`UrbanImportTest::testRankIsDescendingWithLexicalTieBreak`](codigo/testes/casos.md) | method | public | `testRankIsDescendingWithLexicalTieBreak(): void` | 104 | ✓ |
| [`UrbanImportTest::testDistanceIsZeroInsideAndStraightLineOutside`](codigo/testes/casos.md) | method | public | `testDistanceIsZeroInsideAndStraightLineOutside(): void` | 111 | ✓ |
| [`UrbanImportTest::invalidBundles`](codigo/testes/casos.md) | method | public static | `invalidBundles(): iterable` | 120 | ✓ |
| [`UrbanImportTest::testFilesOutsideTheContractAreRejected`](codigo/testes/casos.md) | method | public | `testFilesOutsideTheContractAreRejected(string $file, \Closure $mutate, string $errorKey): void` | 187 | ✓ |
| [`UrbanImportTest::readDefault`](codigo/testes/casos.md) | method | private static | `readDefault(): array` | 213 | ✓ |

### ViewCacheTest.php

Fonte: [`tests/Unit/ViewCacheTest.php`](../../tests/Unit/ViewCacheTest.php) · Referência: [casos](codigo/testes/casos.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`ViewCacheTest`](codigo/testes/casos.md#viewcachetest-e-viteassetstest--renderização-e-assets-aplicação-webhttpaplicacao-webmd) | class | undefined | `` | 10 | ✓ |
| [`ViewCacheTest::setUp`](codigo/testes/casos.md) | method | protected | `setUp(): void` | 14 | ✓ |
| [`ViewCacheTest::tearDown`](codigo/testes/casos.md) | method | protected | `tearDown(): void` | 29 | ✓ |
| [`ViewCacheTest::request`](codigo/testes/casos.md) | method | private | `request(): string` | 39 | ✓ |
| [`ViewCacheTest::testProductionCacheRecompilesATemplateChangedByAnUpdate`](codigo/testes/casos.md) | method | public | `testProductionCacheRecompilesATemplateChangedByAnUpdate(): void` | 51 | ✓ |

### ViteAssetsTest.php

Fonte: [`tests/Unit/ViteAssetsTest.php`](../../tests/Unit/ViteAssetsTest.php) · Referência: [casos](codigo/testes/casos.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`ViteAssetsTest`](codigo/testes/casos.md#viewcachetest-e-viteassetstest--renderização-e-assets-aplicação-webhttpaplicacao-webmd) | class | undefined | `` | 10 | ✓ |
| [`ViteAssetsTest::setUp`](codigo/testes/casos.md) | method | protected | `setUp(): void` | 14 | ✓ |
| [`ViteAssetsTest::tearDown`](codigo/testes/casos.md) | method | protected | `tearDown(): void` | 20 | ✓ |
| [`ViteAssetsTest::assets`](codigo/testes/casos.md) | method | private | `assets(array $manifest, string $base = '/promotheusfiscaliza/assets/build'): ViteAssets` | 27 | ✓ |
| [`ViteAssetsTest::testTagsPointToHashedFilesUnderTheConfiguredPrefix`](codigo/testes/casos.md) | method | public | `testTagsPointToHashedFilesUnderTheConfiguredPrefix(): void` | 34 | ✓ |
| [`ViteAssetsTest::testRejectsUnexpectedFileNamesAndMissingManifest`](codigo/testes/casos.md) | method | public | `testRejectsUnexpectedFileNamesAndMissingManifest(): void` | 47 | ✓ |

## video

### capturar.mjs

Fonte: [`video/capturar.mjs`](../../video/capturar.mjs) · Referência: [video](codigo/video/video.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`arquivo`](codigo/video/video.md) | function | local | `arquivo(nome)` | 16 | ✓ |
| [`caixas`](codigo/video/video.md) | function | local | `caixas()` | 27 | ✓ |
| [`caixas.vis`](codigo/video/video.md) | function | local | `vis(el)` | 34 | ✓ |
| [`neutro`](codigo/video/video.md) | function | local | `neutro(x = 900, y = 30)` | 52 | ✓ |
| [`estado`](codigo/video/video.md) | function | local | `estado(id, { esperar = 1300, mouse, nota } = {})` | 57 | ✓ |
| [`clicar`](codigo/video/video.md) | function | local | `clicar(seletor, esperar = 900)` | 75 | ✓ |
| [`navegarPor`](codigo/video/video.md) | function | local | `navegarPor(seletor)` | 76 | ✓ |
| [`menu`](codigo/video/video.md) | function | local | `menu(texto)` | 77 | ✓ |
| [`instantaneo`](codigo/video/video.md) | function | local | `instantaneo()` | 78 | ✓ |
| [`marcadores`](codigo/video/video.md) | function | local | `marcadores()` | 79 | ✓ |
| [`parte1`](codigo/video/video.md) | function | local | `parte1()` | 94 | ✓ |
| [`parte1.recurso`](codigo/video/video.md) | function | local | `recurso(code)` | 99 | ✓ |
| [`parte2`](codigo/video/video.md) | function | local | `parte2()` | 117 | ✓ |
| [`parte2.sync`](codigo/video/video.md) | function | local | `sync()` | 150 | ✓ |
| [`parte3`](codigo/video/video.md) | function | local | `parte3()` | 161 | ✓ |
| [`camadasDoMapa`](codigo/video/video.md) | function | local | `camadasDoMapa(id)` | 216 | ✓ |
| [`camadasDoMapa.visivel`](codigo/video/video.md) | function | local | `visivel(seletores, valor)` | 220 | ✓ |
| [`camadasDoMapa.carregar`](codigo/video/video.md) | function | local | `carregar(b64)` | 254 | ✓ |

### comum.mjs

Fonte: [`video/comum.mjs`](../../video/comum.mjs) · Referência: [video](codigo/video/video.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`sleep`](codigo/video/video.md) | function | export | `sleep(ms)` | 6 | ✓ |
| [`abrir`](codigo/video/video.md) | function | export | `abrir({ largura = 1536, altura = 864, escala = 2.5, reduzir = false } = {})` | 8 | ✓ |
| [`entrar`](codigo/video/video.md) | function | export | `entrar(page)` | 15 | ✓ |

### motor.js

Fonte: [`video/motor.js`](../../video/motor.js) · Referência: [video](codigo/video/video.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`el`](codigo/video/video.md) | function | local | `el(tag, cls, pai, html)` | 22 | ✓ |
| [`Video`](codigo/video/video.md) | class | export | `Video()` | 34 | ✓ |
| [`Video.constructor`](codigo/video/video.md) | method | local | `constructor(raiz, dados, { reduzido = false } = {})` | 35 | ✓ |
| [`Video.duracao`](codigo/video/video.md) | method | local | `duracao()` | 52 | ✓ |
| [`Video.som`](codigo/video/video.md) | method | local | `som(tipo, t, dados = {})` | 55 | ✓ |
| [`Video.seek`](codigo/video/video.md) | method | local | `seek(t)` | 60 | ✓ |
| [`Video.aplicar`](codigo/video/video.md) | method | local | `aplicar()` | 65 | ✓ |
| [`Video.camera`](codigo/video/video.md) | method | local | `camera(t, dur, q, ease = 'power2.inOut')` | 75 | ✓ |
| [`Video.enquadrar`](codigo/video/video.md) | method | local | `enquadrar(t, dur, tela, caixa, q = {}, ease)` | 98 | ✓ |
| [`Video.tela`](codigo/video/video.md) | method | local | `tela(nome, pos = {})` | 104 | ✓ |
| [`Video.imagem`](codigo/video/video.md) | method | local | `imagem(src, cls, pai, larg, alt)` | 111 | ✓ |
| [`Video.recorte`](codigo/video/video.md) | method | local | `recorte(estadoId, caixa, { pad = 12, classe = '', margem = 0 } = {})` | 124 | ✓ |
| [`Video.realceEm`](codigo/video/video.md) | method | local | `realceEm(rec, caixa, t0, t1, opts = {})` | 143 | ✓ |
| [`Video.caixa`](codigo/video/video.md) | method | local | `caixa(estadoId, texto, { tag, classe, n = 0, dentro, exato } = {})` | 148 | ✓ |
| [`Video.uniao`](codigo/video/video.md) | method | local | `uniao(...cs)` | 164 | ✓ |
| [`Tela`](codigo/video/video.md) | class | export | `Tela()` | 171 | ✓ |
| [`Tela.constructor`](codigo/video/video.md) | method | local | `constructor(video, nome, { x = -TW / 2, y = -TH / 2, z = 0 } = {})` | 172 | ✓ |
| [`Tela.e`](codigo/video/video.md) | method | local | `e()` | 196 | ✓ |
| [`Tela.camadasDe`](codigo/video/video.md) | method | local | `camadasDe(id)` | 198 | ✓ |
| [`Tela.entrar`](codigo/video/video.md) | method | local | `entrar(t, dur = 0.6, de = {})` | 222 | ✓ |
| [`Tela.sair`](codigo/video/video.md) | method | local | `sair(t, dur = 0.5, para = {})` | 227 | ✓ |
| [`Tela.fecharJanela`](codigo/video/video.md) | method | local | `fecharJanela(t)` | 233 | ✓ |
| [`Tela.estado`](codigo/video/video.md) | method | local | `estado(id, t, dur = 0.22)` | 238 | ✓ |
| [`Tela.rolar`](codigo/video/video.md) | method | local | `rolar(y, t, dur = 0.9, ease = 'power2.inOut')` | 253 | ✓ |
| [`Tela.rolarPara`](codigo/video/video.md) | method | local | `rolarPara(caixa, t, dur = 0.9, fracao = 0.25, ease)` | 263 | ✓ |
| [`Tela.caixa`](codigo/video/video.md) | method | local | `caixa(texto, opts)` | 268 | ✓ |
| [`Tela.janela`](codigo/video/video.md) | method | local | `janela(c, rolagem)` | 271 | ✓ |
| [`Tela.mundoDe`](codigo/video/video.md) | method | local | `mundoDe(c, rolagem)` | 277 | ✓ |
| [`Tela.mostrarCursor`](codigo/video/video.md) | method | local | `mostrarCursor(t, ponto)` | 283 | ✓ |
| [`Tela.esconderCursor`](codigo/video/video.md) | method | local | `esconderCursor(t)` | 289 | ✓ |
| [`Tela.mover`](codigo/video/video.md) | method | local | `mover(t, caixa, { dur, fx = 0.5, fy = 0.5, mao = true, dx = 0, dy = 0 } = {})` | 295 | ✓ |
| [`Tela.clicar`](codigo/video/video.md) | method | local | `clicar(t)` | 316 | ✓ |
| [`Tela.irEClicar`](codigo/video/video.md) | method | local | `irEClicar(t, caixa, opts = {})` | 328 | ✓ |
| [`Tela.realce`](codigo/video/video.md) | method | local | `realce(caixa, t0, t1, opts = {})` | 339 | ✓ |
| [`Tela.fixo`](codigo/video/video.md) | method | local | `fixo(caixa, t0, t1)` | 351 | ✓ |
| [`Tela.separar`](codigo/video/video.md) | method | local | `separar(t, dur, { menu = 0, barra = 0, conteudo = 0 } = {})` | 366 | ✓ |
| [`marcar`](codigo/video/video.md) | function | local | `marcar(tl, pai, origem, caixa, t0, t1, { pad = 6, foco = false, raio = 10, classe = '', rotulo, lado = 'cima' } = {})` | 384 | ✓ |
| [`posicionar`](codigo/video/video.md) | function | local | `posicionar({ el: r, rect })` | 400 | ✓ |
| [`dividirVao`](codigo/video/video.md) | function | local | `dividirVao(a, b)` | 403 | ✓ |
| [`mapaDeCalor`](codigo/video/video.md) | function | export | `mapaDeCalor(video, dados)` | 432 | ✓ |
| [`mapaDeCalor.alinhar`](codigo/video/video.md) | function | local | `alinhar(img, dx = 0, dy = 0)` | 443 | ✓ |
| [`mapaDeCalor.alinhar.tamanho`](codigo/video/video.md) | function | local | `tamanho()` | 444 | ✓ |

### principal.js

Fonte: [`video/principal.js`](../../video/principal.js) · Referência: [video](codigo/video/video.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`json`](codigo/video/video.md) | function | local | `json(url)` | 14 | ✓ |
| [`decodificar`](codigo/video/video.md) | function | local | `decodificar(t, adiante = 4)` | 36 | ✓ |
| [`quadro`](codigo/video/video.md) | method | local | `quadro(t)` | 55 | ✓ |
| [`iniciarPrevia`](codigo/video/video.md) | function | local | `iniciarPrevia()` | 67 | ✓ |
| [`iniciarPrevia.encaixar`](codigo/video/video.md) | function | local | `encaixar()` | 77 | ✓ |
| [`iniciarPrevia.mostrar`](codigo/video/video.md) | function | local | `mostrar()` | 94 | ✓ |
| [`iniciarPrevia.ir`](codigo/video/video.md) | function | local | `ir(novo)` | 99 | ✓ |
| [`iniciarPrevia.fonte`](codigo/video/video.md) | function | local | `fonte()` | 108 | ✓ |
| [`iniciarPrevia.alternar`](codigo/video/video.md) | function | local | `alternar()` | 119 | ✓ |
| [`iniciarPrevia.laco`](codigo/video/video.md) | function | local | `laco()` | 132 | ✓ |
| [`fmt`](codigo/video/video.md) | function | local | `fmt(s)` | 144 | ✓ |

### render.mjs

Fonte: [`video/render.mjs`](../../video/render.mjs) · Referência: [video](codigo/video/video.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`arg`](codigo/video/video.md) | function | local | `arg(nome, padrao)` | 14 | ✓ |
| [`ffmpeg`](codigo/video/video.md) | function | local | `ffmpeg(args)` | 35 | ✓ |
| [`trecho`](codigo/video/video.md) | function | local | `trecho(k, a, b)` | 43 | ✓ |

### roteiro.js

Fonte: [`video/roteiro.js`](../../video/roteiro.js) · Referência: [video](codigo/video/video.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`roteiro`](codigo/video/video.md) | function | export | `roteiro(v)` | 23 | ✓ |
| [`letras`](codigo/video/video.md) | function | local | `letras(pai, texto, cls)` | 40 | ✓ |
| [`sobre`](codigo/video/video.md) | function | local | `sobre(v, cls, html, estilo = {})` | 46 | ✓ |
| [`aparecer`](codigo/video/video.md) | function | local | `aparecer(tl, n, t, de = { y: 24 }, dur = 0.6)` | 53 | ✓ |
| [`sumir`](codigo/video/video.md) | function | local | `sumir(tl, n, t, para = {}, dur = 0.4)` | 57 | ✓ |
| [`capitulo`](codigo/video/video.md) | function | local | `capitulo(ctx, t0, t1, n, titulo, kicker)` | 62 | ✓ |
| [`rodape`](codigo/video/video.md) | function | local | `rodape(ctx, t0, t1, html, amb = false)` | 74 | ✓ |
| [`recorte2D`](codigo/video/video.md) | function | local | `recorte2D(v, estadoId, caixa, { left, top, escala = 0.5, pad = 14 } = {})` | 84 | ✓ |
| [`linha`](codigo/video/video.md) | function | local | `linha(v, estado, rotulo)` | 97 | ✓ |
| [`sobreA`](codigo/video/video.md) | function | local | `sobreA(ctx, rec, z = 0)` | 105 | ✓ |
| [`abertura`](codigo/video/video.md) | function | local | `abertura(ctx)` | 112 | ✓ |
| [`tecnologias`](codigo/video/video.md) | function | local | `tecnologias(ctx)` | 163 | ✓ |
| [`tecnologias.placa`](codigo/video/video.md) | function | local | `placa(cls, nome, det, z)` | 172 | ✓ |
| [`tecnologias.etiqueta`](codigo/video/video.md) | function | local | `etiqueta(caixa, texto, t, cls = '', dx = 0, dy = -16)` | 203 | ✓ |
| [`inventario`](codigo/video/video.md) | function | local | `inventario(ctx)` | 255 | ✓ |
| [`inventario.linhasDe`](codigo/video/video.md) | function | local | `linhasDe(tipo)` | 270 | ✓ |
| [`central`](codigo/video/video.md) | function | local | `central(ctx)` | 409 | ✓ |
| [`central.marc`](codigo/video/video.md) | function | local | `marc(id, f)` | 517 | ✓ |
| [`preditiva`](codigo/video/video.md) | function | local | `preditiva(ctx)` | 543 | ✓ |
| [`preditiva.diag`](codigo/video/video.md) | function | local | `diag(c)` | 606 | ✓ |
| [`encerramento`](codigo/video/video.md) | function | local | `encerramento(ctx)` | 756 | ✓ |
| [`vida`](codigo/video/video.md) | function | local | `vida(tl, [h, s, o, c], reduzido, som)` | 861 | ✓ |
| [`vida.ev`](codigo/video/video.md) | function | local | `ev(tipo, t, m)` | 863 | ✓ |
| [`vida.piscar`](codigo/video/video.md) | function | local | `piscar(m, t)` | 864 | ✓ |
| [`vida.olhar`](codigo/video/video.md) | function | local | `olhar(m, t, rotY, ox = 0, oy = 0, dur = 0.45)` | 869 | ✓ |
| [`vida.olhos`](codigo/video/video.md) | function | local | `olhos(m, t, ox, oy, dur = 0.25)` | 873 | ✓ |
| [`vida.pular`](codigo/video/video.md) | function | local | `pular(m, t, alt = 30)` | 874 | ✓ |
| [`vida.respirar`](codigo/video/video.md) | function | local | `respirar(m, t0, periodo, amp = 0.03)` | 883 | ✓ |
| [`vida.acenar`](codigo/video/video.md) | function | local | `acenar(m, t, esquerdo = false)` | 888 | ✓ |

### sons.mjs

Fonte: [`video/sons.mjs`](../../video/sons.mjs) · Referência: [video](codigo/video/video.md)

| Símbolo | Tipo | Visibilidade | Assinatura | Linha | Ref. |
|---|---|---|---|---:|:---:|
| [`midia`](codigo/video/video.md) | function | local | `midia(n)` | 13 | ✓ |
| [`lerWav`](codigo/video/video.md) | function | local | `lerWav(buf)` | 33 | ✓ |
| [`escreverWav`](codigo/video/video.md) | function | local | `escreverWav(url, canais)` | 52 | ✓ |
| [`aleatorio`](codigo/video/video.md) | function | local | `aleatorio(semente)` | 71 | ✓ |
| [`biquad`](codigo/video/video.md) | function | local | `biquad()` | 77 | ✓ |
| [`biquad.f`](codigo/video/video.md) | function | local | `f(x)` | 79 | ✓ |
| [`somar`](codigo/video/video.md) | function | local | `somar(t, sinal, pan = 0, ganho = 1, [DL, DR] = [L, R])` | 95 | ✓ |
| [`tom`](codigo/video/video.md) | function | local | `tom(dur, freq, amp, harm = [1, 0.25, 0.08])` | 110 | ✓ |
| [`ataque`](codigo/video/video.md) | function | local | `ataque(t, a = 0.004)` | 124 | ✓ |
| [`clique`](codigo/video/video.md) | function | local | `clique(r, forca)` | 128 | ✓ |
| [`whoosh`](codigo/video/video.md) | function | local | `whoosh(r, dur, forca)` | 147 | ✓ |
| [`base`](codigo/video/video.md) | function | local | `base(e)` | 165 | ✓ |
| [`tamanho`](codigo/video/video.md) | function | local | `tamanho(e)` | 166 | ✓ |
| [`sino`](codigo/video/video.md) | function | local | `sino(f, dur, brilho = 2)` | 169 | ✓ |
| [`gerar`](codigo/video/video.md) | object | local | `gerar = { … }` | 180 | ✓ |
| [`gerar.clique`](codigo/video/video.md) | method | local | `clique(e, r)` | 181 | ✓ |
| [`gerar.camera`](codigo/video/video.md) | method | local | `camera(e, r)` | 185 | ✓ |
| [`gerar.queda`](codigo/video/video.md) | method | local | `queda(e)` | 190 | ✓ |
| [`gerar.pouso`](codigo/video/video.md) | method | local | `pouso(e)` | 194 | ✓ |
| [`gerar.quique`](codigo/video/video.md) | method | local | `quique(e)` | 207 | ✓ |
| [`gerar.pulo`](codigo/video/video.md) | method | local | `pulo(e)` | 212 | ✓ |
| [`gerar.piscar`](codigo/video/video.md) | method | local | `piscar(e)` | 217 | ✓ |
| [`gerar.rodopio`](codigo/video/video.md) | method | local | `rodopio(e)` | 221 | ✓ |
| [`gerar.aceno`](codigo/video/video.md) | method | local | `aceno(e)` | 225 | ✓ |
| [`gerar.aceno.nota`](codigo/video/video.md) | function | local | `nota(f, d)` | 227 | ✓ |
| [`gerar.volta`](codigo/video/video.md) | method | local | `volta(e)` | 231 | ✓ |
| [`suave`](codigo/video/video.md) | function | local | `suave(x)` | 269 | ✓ |
| [`db`](codigo/video/video.md) | function | local | `db(x)` | 283 | ✓ |
