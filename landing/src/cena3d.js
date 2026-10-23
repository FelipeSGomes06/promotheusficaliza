/*
 * Marca 3D da hero. O Three.js desenha (geometria, materiais, luz e câmera); o GSAP coreografa (uma timeline com
 * etapas nomeadas). Os planos das cenas de recursos têm módulo e renderer próprios (planos3d.js).
 *
 * Coordenadas: as formas vêm do SVG oficial (32×32, y para baixo) e são convertidas para a cena (centro na origem,
 * y para cima, 1 unidade = 1 unidade do SVG). A frente da base azul fica em z = 0; as vias sobem a partir dela.
 */
import {
  BoxGeometry, DirectionalLight, ExtrudeGeometry, Group, HemisphereLight, LatheGeometry, MathUtils, Mesh,
  MeshStandardMaterial, NeutralToneMapping, PCFShadowMap, PerspectiveCamera, Plane, PMREMGenerator, PointLight, Scene,
  Shape, Vector2, Vector3, WebGLRenderer,
} from 'three';
import { SVGLoader } from 'three/addons/loaders/SVGLoader.js';
import { RoomEnvironment } from 'three/addons/environments/RoomEnvironment.js';
import { gsap } from 'gsap';

// Formas oficiais (public/assets/img/favicon.svg e símbolo i-brand de icons.svg).
const SVG = {
  base: 'M8 0H24A8 8 0 0 1 32 8V24A8 8 0 0 1 24 32H8A8 8 0 0 1 0 24V8A8 8 0 0 1 8 0Z',
  // Primeiro traço: haste + braço superior, o contorno oficial do F sem o desvio do braço curto.
  tracoA: 'M7.5 7.5A1.5 1.5 0 0 1 9 6h15.5a1.5 1.5 0 0 1 1.5 1.5v3a1.5 1.5 0 0 1-1.5 1.5H13.5V25a1.5 1.5 0 0 1-1.5 1.5H9A1.5 1.5 0 0 1 7.5 25z',
  // Segundo traço: o braço curto oficial, entrando 1 unidade na haste (a sobra fica escondida dentro do encaixe).
  tracoB: 'M12.5 15H19a1.5 1.5 0 0 1 1.5 1.5v3A1.5 1.5 0 0 1 19 20H12.5z',
};
// Faixas tracejadas (stroke 1,4 com pontas redondas), na ordem do percurso: haste de baixo para cima, depois o braço.
const FAIXAS = [[10.5, 23.6, 10.5, 21.8], [10.5, 18.6, 10.5, 16.8], [10.5, 13.6, 10.5, 11.8], [15.2, 9, 17, 9], [20.2, 9, 22, 9]];
const SENSOR = { x: 17.3, y: 17, r: 1.5 };
// Ondas: arcos de raio r entre (x, y0) e (x, y1), abertos para a direita, traço 2.
const ONDAS = [{ x: 23.2, y0: 14.6, y1: 19.4, r: 3.4 }, { x: 25.6, y0: 12.4, y1: 21.6, r: 6.6 }];

const COR = { azul: '#2152e8', branco: '#f2f5fa', ambar: '#ffb21a', turquesa: '#7cf5e1' };
const BASE = { prof: 3.2, chanfro: 0.9 };
const VIA = { prof: 1.5, chanfro: 0.38 };
const TOPO_VIA = VIA.prof + 2 * VIA.chanfro;
const JUNTA_X = 13.5 - 16; // onde o braço curto encosta na haste
// Percurso do pulso de sinal (cena): pé da haste → junção → ponta do braço superior.
const PERCURSO = [new Vector2(-5.5, -10.5), new Vector2(-5.5, 7), new Vector2(8.5, 7)];
const IMPACTOS = [1.27, 1.88]; // instantes (s, na escala de 4 s) em que cada traço encaixa
const POSE_FINAL = { az: 13, el: 8, d: 121 };

const svgParaCena = (x, y) => new Vector2(x - 16, 16 - y);

function formaDePontos(pontos) {
  const out = [];
  for (const [x, y] of pontos) {
    const v = svgParaCena(x, y);
    if (!out.length || out[out.length - 1].distanceToSquared(v) > 1e-8) out.push(v);
  }
  if (out.length > 2 && out[0].distanceToSquared(out[out.length - 1]) < 1e-8) out.pop();
  return new Shape(out);
}

function formaSvg(d, divisoes) {
  const caminho = new SVGLoader().parse(`<svg xmlns="http://www.w3.org/2000/svg"><path d="${d}"/></svg>`).paths[0];
  return formaDePontos(caminho.toShapes()[0].getPoints(divisoes).map((p) => [p.x, p.y]));
}

/** Extrusão com chanfro para dentro: a silhueta máxima é exatamente a forma oficial. Costas em z = 0. */
function extrudar(forma, prof, chanfro, segmentos) {
  const g = new ExtrudeGeometry(forma, {
    depth: prof, bevelEnabled: chanfro > 0, bevelThickness: chanfro, bevelSize: chanfro, bevelOffset: -chanfro,
    bevelSegments: segmentos, curveSegments: 10,
  });
  g.translate(0, 0, chanfro);
  return g;
}

/** Segmento com pontas redondas ao longo de +x, de 0 a comprimento (stroke-linecap: round). */
function capsula(comprimento, raio) {
  const s = new Shape();
  s.moveTo(0, -raio);
  s.lineTo(comprimento, -raio);
  s.absarc(comprimento, 0, raio, -Math.PI / 2, Math.PI / 2, false);
  s.lineTo(0, raio);
  s.absarc(0, 0, raio, Math.PI / 2, Math.PI * 1.5, false);
  return s;
}

/** Arco simétrico em torno de +x (de -meio a +meio), com espessura e pontas redondas, centrado na origem. */
function arco(raio, meio, espessura) {
  const h = espessura / 2;
  const s = new Shape();
  s.absarc(0, 0, raio + h, -meio, meio, false);
  s.absarc(raio * Math.cos(meio), raio * Math.sin(meio), h, meio, meio + Math.PI, false);
  s.absarc(0, 0, raio - h, meio, -meio, true);
  s.absarc(raio * Math.cos(meio), -raio * Math.sin(meio), h, Math.PI - meio, 2 * Math.PI - meio, false);
  return s;
}

function materiais() {
  return {
    azul: new MeshStandardMaterial({ color: COR.azul, roughness: 0.46, metalness: 0.04, envMapIntensity: 0.12 }),
    branco: new MeshStandardMaterial({ color: COR.branco, roughness: 0.52, envMapIntensity: 0.22 }),
    pintura: new MeshStandardMaterial({ color: COR.azul, roughness: 0.6 }),
    ambar: new MeshStandardMaterial({ color: COR.ambar, emissive: COR.ambar, emissiveIntensity: 0.9, roughness: 0.35 }),
    turquesa: new MeshStandardMaterial({ color: COR.turquesa, emissive: COR.turquesa, emissiveIntensity: 0.35, roughness: 0.4 }),
  };
}

function luzes(cena, celular) {
  cena.add(new HemisphereLight('#dce6ff', '#0a1322', 0.1));
  const chave = new DirectionalLight('#ffffff', 2.3);
  chave.position.set(-34, 40, 56);
  const recorte = new DirectionalLight('#a9d8ff', 0.9);
  recorte.position.set(40, 8, -18);
  cena.add(chave, recorte);
  chave.castShadow = true;
  chave.shadow.mapSize.set(celular ? 512 : 1024, celular ? 512 : 1024);
  Object.assign(chave.shadow.camera, { left: -24, right: 24, top: 24, bottom: -24, near: 30, far: 160 });
  chave.shadow.bias = -0.0004;
  chave.shadow.normalBias = 0.03;
  return { chave, recorte };
}

export function criarCena({ host, reduzido, celular, duracao, ponteiro, nome, sombra, aoEstado }) {
  let renderer;
  try {
    renderer = new WebGLRenderer({ antialias: true, alpha: true, failIfMajorPerformanceCaveat: true, powerPreference: 'high-performance' });
  } catch {
    return null;
  }
  // Exposição baixa: o canal azul da marca fica abaixo da faixa que o tone mapping comprime (e desbota para o
  // branco). Luz lateral forte e pouco ambiente, para a sombra de contato aparecer. Medido no navegador: frente da base
  // #1d51e9 (marca #2152e8) e vias quase papel (#edf1f7).
  renderer.toneMapping = NeutralToneMapping;
  renderer.toneMappingExposure = 0.45;
  renderer.shadowMap.enabled = true;
  renderer.shadowMap.type = PCFShadowMap;
  renderer.localClippingEnabled = true;
  renderer.setClearColor(0x000000, 0);
  const canvas = renderer.domElement;
  canvas.setAttribute('aria-hidden', 'true');
  gsap.set(canvas, { opacity: 0 });
  host.append(canvas);

  // Ambiente neutro para os reflexos discretos e a luz de preenchimento (sem ele as cores da marca escurecem).
  // Mapa pequeno: 128 px no desktop, 64 px no celular.
  const pmrem = new PMREMGenerator(renderer);
  const ambiente = pmrem.fromScene(new RoomEnvironment(), 0.04, 0.1, 100, { size: celular ? 64 : 128 }).texture;
  pmrem.dispose();

  const m = materiais();
  const cena = new Scene();
  cena.environment = ambiente;
  const { chave, recorte } = luzes(cena, celular);
  const camera = new PerspectiveCamera(24, 1, 10, 400);
  const alvo = new Vector3(0, 0, 0);

  // ---------- A marca ----------
  const div = celular ? 5 : 8;
  const segs = celular ? 2 : 4;
  const pivo = new Group(); // giro pelo ponteiro (depois da montagem)
  const montagem = new Group(); // movimentos da timeline: entrada, recuos dos impactos
  pivo.add(montagem);
  cena.add(pivo);

  const baseGeo = extrudar(formaSvg(SVG.base, div * 2), BASE.prof, BASE.chanfro, segs);
  baseGeo.translate(0, 0, -(BASE.prof + 2 * BASE.chanfro));
  const base = new Mesh(baseGeo, m.azul);
  base.receiveShadow = true;

  // Primeiro traço: revelado por dois planos de recorte (haste para cima, depois braço para a direita).
  const matA = m.branco.clone();
  const planosLocais = [new Plane(new Vector3(0, -1, 0), 0), new Plane(new Vector3(-1, 0, 0), 0)];
  matA.clippingPlanes = planosLocais.map((p) => p.clone());
  matA.clipShadows = true;
  const pecaA = new Mesh(extrudar(formaSvg(SVG.tracoA, div), VIA.prof, VIA.chanfro, segs), matA);
  // Frente de instalação: barra clara que cobre o corte enquanto a via avança.
  const barra = new Mesh(new BoxGeometry(6.8, 0.55, TOPO_VIA + 0.5),
    new MeshStandardMaterial({ color: '#ffffff', emissive: '#bdfcf2', emissiveIntensity: 1.1, roughness: 0.3 }));
  barra.position.z = TOPO_VIA / 2;
  pecaA.add(barra);

  // Faixas tracejadas: pintura azul sobre a via, filhas do primeiro traço (acompanham queda e compressão).
  const faixas = FAIXAS.map(([x1, y1, x2, y2]) => {
    const a = svgParaCena(x1, y1);
    const b = svgParaCena(x2, y2);
    const faixa = new Mesh(new ExtrudeGeometry(capsula(a.distanceTo(b), 0.7), { depth: 0.08, bevelEnabled: false, curveSegments: 6 }), m.pintura);
    faixa.position.set(a.x, a.y, TOPO_VIA + 0.004);
    faixa.rotation.z = Math.atan2(b.y - a.y, b.x - a.x);
    faixa.receiveShadow = true;
    // Distância ao longo do percurso do pulso em que a faixa começa.
    faixa.userData.s = a.x < -5 ? a.y - PERCURSO[0].y : PERCURSO[1].y - PERCURSO[0].y + (a.x - PERCURSO[1].x);
    faixa.userData.comprimento = a.distanceTo(b);
    pecaA.add(faixa);
    return faixa;
  });

  // Segundo traço: pivô no encaixe, para comprimir contra a haste.
  const geoB = extrudar(formaSvg(SVG.tracoB, div), VIA.prof - 0.04, VIA.chanfro, segs);
  geoB.translate(-JUNTA_X, 0, 0);
  const pecaB = new Mesh(geoB, m.branco);
  const sensorPerfil = [[0, 0], [1.5, 0], [1.5, 0.42], [1.44, 0.68], [1.26, 0.86], [0.9, 0.98], [0, 1.03]].map(([x, y]) => new Vector2(x * SENSOR.r / 1.5, y));
  const sensorGeo = new LatheGeometry(sensorPerfil, celular ? 20 : 32);
  sensorGeo.rotateX(Math.PI / 2);
  const matSensor = m.ambar.clone();
  const sensor = new Mesh(sensorGeo, matSensor);
  const pSensor = svgParaCena(SENSOR.x, SENSOR.y);
  sensor.position.set(pSensor.x - JUNTA_X, pSensor.y, TOPO_VIA - 0.04);
  const luzSensor = new PointLight(COR.ambar, 0, 7, 2);
  luzSensor.position.set(sensor.position.x, sensor.position.y, TOPO_VIA + 3);
  pecaB.add(sensor, luzSensor);

  // Ondas do radar, cada uma com o próprio material (acendem em momentos diferentes).
  const ondas = ONDAS.map(({ x, y0, y1, r }) => {
    const meio = Math.asin((y1 - y0) / 2 / r);
    const centro = svgParaCena(x - r * Math.cos(meio), (y0 + y1) / 2);
    const onda = new Mesh(extrudar(arco(r, meio, 2), 0.7, 0.18, 2), m.turquesa.clone());
    onda.position.set(centro.x, centro.y, 0);
    return onda;
  });
  const fantasma = new Mesh(new ExtrudeGeometry(arco(ONDAS[1].r, Math.asin(4.6 / 6.6), 1.2), { depth: 0.2, bevelEnabled: false }),
    new MeshStandardMaterial({ color: COR.turquesa, emissive: COR.turquesa, emissiveIntensity: 1, transparent: true, opacity: 0, depthWrite: false }));
  fantasma.position.copy(ondas[1].position);
  const luzPulso = new PointLight('#c9fff6', 0, 12, 2);

  for (const malha of [base, pecaA, pecaB, sensor, ...ondas]) malha.castShadow = true;
  for (const malha of [pecaA, pecaB, ...ondas]) malha.receiveShadow = true;
  montagem.add(base, pecaA, pecaB, ...ondas, fantasma, luzPulso);

  // ---------- Estado animado (a timeline mexe nestes números; aplicar() converte em cena) ----------
  const cam = { ...POSE_FINAL };
  const revela = { p: 1 };
  const pulso = { p: 1 };
  const pontoNoPercurso = (s, alvoV) => {
    const trecho = PERCURSO[1].y - PERCURSO[0].y;
    if (s <= trecho) return alvoV.set(PERCURSO[0].x, PERCURSO[0].y + s);
    return alvoV.set(PERCURSO[1].x + Math.min(s - trecho, PERCURSO[2].x - PERCURSO[1].x), PERCURSO[1].y);
  };
  const totalPercurso = (PERCURSO[1].y - PERCURSO[0].y) + (PERCURSO[2].x - PERCURSO[1].x);
  const ponto = new Vector2();
  let tl = null;

  function aplicar() {
    const az = MathUtils.degToRad(cam.az);
    const el = MathUtils.degToRad(cam.el);
    camera.position.set(Math.sin(az) * Math.cos(el) * cam.d, Math.sin(el) * cam.d, Math.cos(az) * Math.cos(el) * cam.d);
    camera.lookAt(alvo);
    // Tremor de poucos pixels, só no palco 3D, derivado do tempo da timeline (volta a zero sozinho).
    const t = tl ? tl.time() : 99;
    const forca = IMPACTOS.reduce((soma, ti) => {
      const dt = t - ti;
      return dt >= 0 && dt < 0.22 ? soma + (1 - dt / 0.22) ** 2 : soma;
    }, 0);
    if (forca > 0) {
      camera.translateX(Math.sin(t * 97) * 0.3 * forca);
      camera.translateY(Math.cos(t * 83) * 0.24 * forca);
    }

    // Primeiro traço: haste de baixo para cima, depois o braço da esquerda para a direita.
    const haste = 26.5 - 6;
    const s = revela.p * (haste + 12.5);
    // Folga de 0,02 nos dois cortes: um plano rente à parede da peça (topo do braço em y = 10) recorta pixel sim,
    // pixel não, e a aresta chia.
    const corteY = revela.p >= 1 ? 99 : 16.02 - Math.max(26.5 - s, 6);
    const corteX = revela.p >= 1 ? 99 : JUNTA_X + 0.02 + Math.max(0, s - haste);
    planosLocais[0].constant = corteY;
    planosLocais[1].constant = corteX;
    barra.visible = revela.p > 0 && revela.p < 1;
    const naHaste = s <= haste;
    barra.position.set(naHaste ? -5.5 : corteX, naHaste ? corteY : 7, TOPO_VIA / 2);
    barra.rotation.z = naHaste ? 0 : Math.PI / 2;

    // Pulso de sinal: percorre a via e pinta as faixas quando passa por elas.
    const sp = pulso.p * totalPercurso;
    pontoNoPercurso(sp, ponto);
    luzPulso.position.set(ponto.x, ponto.y, TOPO_VIA + 3.2);
    luzPulso.intensity = pulso.p > 0 && pulso.p < 1 ? Math.sin(pulso.p * Math.PI) * 70 : 0;
    for (const faixa of faixas) {
      const k = MathUtils.clamp((sp - faixa.userData.s) / faixa.userData.comprimento, 0, 1);
      faixa.visible = k > 0;
      faixa.scale.x = Math.max(k, 0.001);
    }

    invalidar();
  }

  function impacto(linha, t, eixo, peca, escala, compressao) {
    linha.to(montagem.position, { [eixo]: eixo === 'z' ? -0.9 : -0.8, duration: 0.05, ease: 'power2.out' }, t)
      .to(montagem.position, { [eixo]: 0, duration: 0.34, ease: 'elastic.out(1, 0.55)' }, t + 0.05)
      .to(peca.scale, { [escala]: compressao, duration: 0.05, ease: 'power2.out' }, t)
      .to(peca.scale, { [escala]: 1, duration: 0.3, ease: 'elastic.out(1, 0.5)' }, t + 0.05)
      .to(base.scale, { z: 0.93, duration: 0.05, ease: 'power2.out' }, t)
      .to(base.scale, { z: 1, duration: 0.32, ease: 'elastic.out(1, 0.55)' }, t + 0.05);
    if (sombra) {
      linha.to(sombra, { scaleX: 0.9, opacity: 1, duration: 0.05 }, t)
        .to(sombra, { scaleX: 1, opacity: 0.85, duration: 0.35, ease: 'power2.out' }, t + 0.05);
    }
  }

  function criarTimeline() {
    // Estado inicial da sequência (a timeline parte dele).
    pecaA.visible = false;
    pecaB.visible = false;
    pecaA.position.z = 3.4;
    pecaB.position.set(JUNTA_X + 15, 0, 1.8);
    sensor.scale.setScalar(0.001);
    matSensor.emissiveIntensity = 0;
    for (const onda of ondas) {
      onda.scale.setScalar(0.001);
      onda.material.emissiveIntensity = 0;
    }
    chave.intensity = 1.85;
    recorte.intensity = 0.4;

    const linha = gsap.timeline({ paused: true, onUpdate: aplicar, onComplete: () => aoEstado('pronta') });
    linha.addLabel('quadrado', 0).addLabel('primeiroTraco', 0.5).addLabel('segundoTraco', 1.4).addLabel('sinal', 2.2).addLabel('marcaPronta', 3.2);

    // 1. Quadrado: a base azul aparece em perspectiva e assenta.
    linha.fromTo(cam, { az: -40, el: 30, d: 150 }, { az: -26, el: 21, d: 136, duration: 0.5, ease: 'power2.out' }, 'quadrado')
      .to(cam, { az: -6, el: 13, d: 128, duration: 2.7, ease: 'sine.inOut' }, 0.5)
      .fromTo(montagem.rotation, { x: -1.15 }, { x: 0.06, duration: 0.36, ease: 'power3.out' }, 'quadrado')
      .to(montagem.rotation, { x: 0, duration: 0.14, ease: 'power2.inOut' }, 0.36)
      .fromTo(montagem.position, { y: -7 }, { y: 0, duration: 0.4, ease: 'power3.out' }, 'quadrado')
      .fromTo(montagem.scale, { x: 0.84, y: 0.84, z: 0.84 }, { x: 1, y: 1, z: 1, duration: 0.42, ease: 'power3.out' }, 'quadrado');
    if (sombra) linha.fromTo(sombra, { opacity: 0, scaleX: 0.6 }, { opacity: 0.85, scaleX: 1, duration: 0.45, ease: 'power2.out' }, 'quadrado');
    if (nome) linha.to(nome, { opacity: 0, duration: 0.2 }, 'quadrado').set(nome, { y: 10, letterSpacing: '0.16em' }, 0.2);

    // 2. Primeiro traço: a via é instalada (haste e braço superior num percurso só) e encaixa com um impacto seco.
    linha.set(pecaA, { visible: true }, 'primeiroTraco')
      .fromTo(revela, { p: 0 }, { p: 1, duration: 0.64, ease: 'power2.inOut' }, 'primeiroTraco')
      .to(pecaA.position, { z: 0, duration: 0.11, ease: 'power3.in' }, IMPACTOS[0] - 0.11);
    impacto(linha, IMPACTOS[0], 'z', pecaA, 'z', 0.84);

    // 3. Segundo traço: o braço curto desliza de lado e encaixa na haste — outro movimento, outro impacto.
    linha.set(pecaB, { visible: true }, 'segundoTraco')
      .to(pecaB.position, { x: JUNTA_X, duration: 0.44, ease: 'power3.in' }, IMPACTOS[1] - 0.44)
      .to(pecaB.position, { z: 0, duration: 0.44, ease: 'power2.in' }, IMPACTOS[1] - 0.44);
    impacto(linha, IMPACTOS[1], 'x', pecaB, 'x', 0.86);

    // 4. Sinal: o sensor acende, as ondas saem e um pulso percorre a via pintando as faixas.
    linha.to(sensor.scale, { x: 1, y: 1, z: 1, duration: 0.3, ease: 'back.out(2.4)' }, 'sinal')
      .to(matSensor, { emissiveIntensity: 1.6, duration: 0.18, ease: 'power2.out' }, 'sinal')
      .to(matSensor, { emissiveIntensity: 0.5, duration: 0.5, ease: 'power2.inOut' }, 'sinal+=0.18')
      .to(luzSensor, { intensity: 14, duration: 0.18 }, 'sinal')
      .to(luzSensor, { intensity: 3, duration: 0.5 }, 'sinal+=0.18')
      .fromTo(pulso, { p: 0 }, { p: 1, duration: 0.75, ease: 'power1.inOut' }, 'sinal+=0.1');
    ondas.forEach((onda, i) => {
      const t = 2.36 + i * 0.14;
      linha.to(onda.scale, { x: 1, y: 1, z: 1, duration: 0.38, ease: 'power3.out' }, t)
        .to(onda.material, { emissiveIntensity: 1.5, duration: 0.14 }, t)
        .to(onda.material, { emissiveIntensity: 0.25, duration: 0.6, ease: 'power2.inOut' }, t + 0.14);
    });
    linha.fromTo(fantasma.scale, { x: 1, y: 1, z: 1 }, { x: 1.7, y: 1.7, z: 1, duration: 0.65, ease: 'power2.out', immediateRender: false }, 2.52)
      .fromTo(fantasma.material, { opacity: 0.55 }, { opacity: 0, duration: 0.65, ease: 'power2.out', immediateRender: false }, 2.52);

    // 5. Marca pronta: a câmera assenta quase de frente, a luz destaca a marca e o nome se firma.
    linha.to(cam, { ...POSE_FINAL, duration: 0.8, ease: 'power3.inOut' }, 'marcaPronta')
      .to(chave, { intensity: 2.3, duration: 0.8 }, 'marcaPronta')
      .to(recorte, { intensity: 0.9, duration: 0.8 }, 'marcaPronta');
    if (nome) linha.to(nome, { opacity: 1, y: 0, letterSpacing: '0em', duration: 0.7, ease: 'power3.out' }, 'marcaPronta+=0.05');

    linha.timeScale(4 / duracao);
    return linha;
  }

  // ---------- Desenho sob demanda ----------
  let sujo = true;
  let visivel = true;
  const planosMundo = matA.clippingPlanes;
  function desenhar() {
    cena.updateMatrixWorld();
    planosLocais.forEach((p, i) => planosMundo[i].copy(p).applyMatrix4(pecaA.matrixWorld));
    renderer.render(cena, camera);
  }
  function invalidar() {
    sujo = true;
  }
  const tique = () => {
    if (sujo && visivel) {
      sujo = false;
      desenhar();
    }
  };
  gsap.ticker.add(tique);

  const limitePixels = celular ? 1.5 : 1.75;
  const redimensionar = () => {
    const { width, height } = host.getBoundingClientRect();
    if (!width || !height) return;
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, limitePixels));
    renderer.setSize(width, height, false);
    camera.aspect = width / height;
    camera.updateProjectionMatrix();
    invalidar();
  };
  new ResizeObserver(redimensionar).observe(host);
  new IntersectionObserver(([entrada]) => {
    visivel = entrada.isIntersecting;
    if (visivel) invalidar();
  }, { rootMargin: '120px' }).observe(host);
  canvas.addEventListener('webglcontextlost', (evento) => {
    evento.preventDefault();
    gsap.ticker.remove(tique);
    aoEstado('falhou');
  });

  // ---------- Resposta ao ponteiro (só com a marca pronta e visível) ----------
  // Quem ouve o mouse é o palco (palco.js), que move também a foto: aqui só se gira o pivô, em até ~5° × 3,5°.
  let ponteiroLigado = false;
  const giroY = gsap.quickTo(pivo.rotation, 'y', { duration: 0.9, ease: 'power3.out', onUpdate: invalidar });
  const giroX = gsap.quickTo(pivo.rotation, 'x', { duration: 0.9, ease: 'power3.out', onUpdate: invalidar });
  const repousar = () => {
    giroY(0);
    giroX(0);
  };

  // ---------- Controle exposto ao palco ----------
  tl = criarTimeline();
  redimensionar();
  // Compila os shaders sem travar a página (KHR_parallel_shader_compile quando existe); a montagem espera por isso.
  const compilado = renderer.compileAsync(cena, camera).catch(() => {});

  return {
    iniciar: (aoComecar) => compilado.then(() => {
      aoComecar();
      gsap.to(canvas, { opacity: 1, duration: reduzido ? 0 : 0.3 });
      if (reduzido) {
        tl.progress(1);
        aoEstado('pronta');
      } else {
        tl.play(0);
      }
    }),
    definirReduzido(valor) {
      reduzido = valor;
      if (valor) {
        tl.progress(1);
        repousar();
      }
      ponteiroLigado = !valor && ponteiro && tl.progress() === 1;
    },
    ligarPonteiro() {
      ponteiroLigado = !reduzido && ponteiro;
    },
    /** Posição normalizada do mouse no palco, de -1 a 1 em cada eixo (0, 0 = repouso). */
    apontar(x, y) {
      if (!ponteiroLigado || !visivel) return;
      giroY(x * 0.09);
      giroX(y * 0.06);
    },
    repousar,
  };
}
