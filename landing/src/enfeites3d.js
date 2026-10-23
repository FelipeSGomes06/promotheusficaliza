/*
 * Enfeites 3D junto das capturas: seis objetos pequenos, de fundo transparente, apoiados na borda das imagens — mapa
 * com pinos (O sistema), câmera de monitoramento (inventário), cone com sinalizador (manutenção), placa Mercosul
 * fictícia (multas), semáforo (revisões) e mapa de calor em colunas (Planejar reforço).
 *
 * Um único WebGLRenderer desenha todos, um de cada vez, num quadro quadrado de P × P px. Com OffscreenCanvas + WebGL,
 * cada quadro vira um ImageBitmap entregue ao canvas do enfeite por transferência (sem cópia); sem isso, o quadro é
 * copiado com drawImage para o canvas 2D do enfeite. Sem pós-processamento: as luzes são materiais emissivos e halos.
 *
 * Cada enfeite recebe um estado: entrada (0 → 1, uma vez, quando aparece), t (segundos de vida), giro (posição na
 * janela, −1 em cima a 1 embaixo), x e y (ponteiro relativo ao enfeite, −1 a 1) e parado (movimento reduzido).
 */
import {
  CanvasTexture, Color, CylinderGeometry, DirectionalLight, ExtrudeGeometry, Group, HemisphereLight, InstancedMesh,
  LatheGeometry, MathUtils, Mesh, MeshBasicMaterial, MeshStandardMaterial, NeutralToneMapping, Object3D,
  PerspectiveCamera, PlaneGeometry, PMREMGenerator, Scene, Shape, SphereGeometry, Sprite, SpriteMaterial,
  SRGBColorSpace, TorusGeometry, Vector2, Vector3, WebGLRenderer,
} from 'three';
import { RoomEnvironment } from 'three/addons/environments/RoomEnvironment.js';

const COR = { marca: '#2152e8', turquesa: '#7cf5e1', ambar: '#ffb21a', grafite: '#1c2430' };

// ---------- Utilidades ----------
const suave = (a, b, x) => {
  const t = MathUtils.clamp((x - a) / (b - a), 0, 1);
  return t * t * (3 - 2 * t);
};
const faixa = (a, b, x) => MathUtils.clamp((x - a) / (b - a), 0, 1);
/** Queda com quique curto (0 → 1). */
function quique(x) {
  if (x < 0.62) return (x / 0.62) ** 2;
  const k = (x - 0.62) / 0.38;
  return 1 - Math.sin(k * Math.PI) * 0.12 * (1 - k);
}
/** Passa um pouco e volta (0 → 1). */
const passa = (x) => {
  const c = 1.9;
  return 1 + (c + 1) * (x - 1) ** 3 + c * (x - 1) ** 2;
};

function imagem(w, h, desenhar) {
  const c = document.createElement('canvas');
  c.width = w;
  c.height = h;
  desenhar(c.getContext('2d'), w, h);
  return c;
}
function textura(canvas, cor = true) {
  const t = new CanvasTexture(canvas);
  if (cor) t.colorSpace = SRGBColorSpace;
  t.anisotropy = 4;
  return t;
}
function retangulo(w, h, r, x0 = -w / 2, y0 = -h / 2) {
  const s = new Shape();
  s.moveTo(x0 + r, y0);
  s.lineTo(x0 + w - r, y0);
  s.absarc(x0 + w - r, y0 + r, r, -Math.PI / 2, 0, false);
  s.lineTo(x0 + w, y0 + h - r);
  s.absarc(x0 + w - r, y0 + h - r, r, 0, Math.PI / 2, false);
  s.lineTo(x0 + r, y0 + h);
  s.absarc(x0 + r, y0 + h - r, r, Math.PI / 2, Math.PI, false);
  s.lineTo(x0, y0 + r);
  s.absarc(x0 + r, y0 + r, r, Math.PI, Math.PI * 1.5, false);
  return s;
}
function chapa(forma, prof, chanfro = 0.02) {
  return new ExtrudeGeometry(forma, {
    depth: prof - 2 * chanfro, bevelEnabled: true, bevelThickness: chanfro, bevelSize: chanfro, bevelOffset: -chanfro,
    bevelSegments: 3, curveSegments: 10,
  }).translate(0, 0, chanfro);
}
const fosco = (cor, rugosidade = 0.55, metal = 0) => new MeshStandardMaterial({ color: cor, roughness: rugosidade, metalness: metal });

// Mancha radial branca (sombra de contato e halos).
let manchaCache = null;
function mancha() {
  // Queda gaussiana (sem borda) nos canais de cor, com alfa cheio: alphaMap lê o verde, e um degradê só no alfa vira
  // disco de borda dura em navegadores que guardam o canvas pré-multiplicado.
  manchaCache ??= textura(imagem(128, 128, (g) => {
    const d = g.createImageData(128, 128);
    for (let y = 0; y < 128; y++) {
      for (let x = 0; x < 128; x++) {
        const r = Math.hypot(x - 63.5, y - 63.5) / 64;
        const a = Math.round(255 * Math.exp(-r * r * 5) * (1 - suave(0.85, 1, r)));
        d.data.set([a, a, a, 255], (y * 128 + x) * 4);
      }
    }
    g.putImageData(d, 0, 0);
  }), false);
  return manchaCache;
}
/** Sombra de contato no chão (y = 0): o objeto assenta na borda da captura. */
function sombra(w, d, forca = 0.32) {
  const m = new Mesh(new PlaneGeometry(w, d).rotateX(-Math.PI / 2),
    new MeshBasicMaterial({ color: '#0a1322', alphaMap: mancha(), transparent: true, opacity: forca, depthWrite: false }));
  m.position.y = 0.002;
  m.renderOrder = -1;
  return m;
}
/** Halo colorido de uma luz (mistura normal: aparece também sobre o papel claro). */
function halo(cor, tamanho) {
  const s = new Sprite(new SpriteMaterial({ alphaMap: mancha(), color: cor, transparent: true, opacity: 0, depthWrite: false }));
  s.scale.setScalar(tamanho);
  return s;
}

// ---------- Os objetos ----------
// Cada fábrica devolve { grupo, camera: [posição, alvo], atualizar(estado) }. Chão em y = 0, objeto com ~2 de altura.

/** O sistema: um pedaço de mapa com pinos que caem e pulsam. */
function mapa() {
  const grupo = new Group();
  const placa = new Group();
  const tex = textura(imagem(512, 512, (g) => {
    g.fillStyle = '#e9eef5';
    g.fillRect(0, 0, 512, 512);
    g.fillStyle = '#d3ecda';
    g.beginPath();
    g.roundRect(300, 300, 150, 120, 18);
    g.fill();
    g.strokeStyle = '#bfdcf2';
    g.lineWidth = 26;
    g.beginPath();
    g.moveTo(-20, 120);
    g.bezierCurveTo(140, 80, 210, 260, 530, 210);
    g.stroke();
    g.save();
    g.translate(256, 256);
    g.rotate(-0.12);
    g.strokeStyle = '#ffffff';
    g.lineWidth = 9;
    for (let i = -6; i <= 6; i++) {
      g.beginPath();
      g.moveTo(i * 52, -380);
      g.lineTo(i * 52, 380);
      g.stroke();
      g.beginPath();
      g.moveTo(-380, i * 52);
      g.lineTo(380, i * 52);
      g.stroke();
    }
    g.strokeStyle = '#ffe2b0';
    g.lineWidth = 18;
    g.beginPath();
    g.moveTo(-380, 26);
    g.lineTo(380, 26);
    g.moveTo(-78, -380);
    g.lineTo(-78, 380);
    g.stroke();
    g.restore();
  }));
  tex.repeat.set(1 / 2.6, 1 / 2.6);
  const geo = chapa(retangulo(2.6, 2.6, 0.22, 0, 0), 0.16, 0.04).translate(-1.3, -1.3, 0).rotateX(-Math.PI / 2);
  const chao = new Mesh(geo, [fosco('#ffffff', 0.8), fosco('#c3cddd', 0.6)]);
  chao.material[0].map = tex;
  placa.add(chao);
  // Pinos: gota azul (âmbar no que pede atenção) com miolo branco; anel turquesa pulsando no chão.
  // Gota: ponta no chão, lados retos até tangenciar a cabeça (esfera de raio 0,24 com centro em y = 0,62).
  const perfil = [[0.001, 0], [0.07, 0.16], [0.15, 0.33], [0.2, 0.44]].map(([a, b]) => new Vector2(a, b));
  for (let i = 0; i <= 8; i++) {
    const a = MathUtils.degToRad(-30 + i * 15);
    perfil.push(new Vector2(Math.max(0.001, Math.cos(a) * 0.24), 0.62 + Math.sin(a) * 0.24));
  }
  const gotaGeo = new LatheGeometry(perfil, 28);
  const pinos = [[-0.62, 0.42, COR.marca], [0.58, -0.28, COR.marca], [0.18, 0.72, COR.ambar]].map(([x, z, cor], i) => {
    const pino = new Group();
    const gota = new Mesh(gotaGeo, new MeshStandardMaterial({ color: cor, roughness: 0.32, metalness: 0.05, envMapIntensity: 0.8 }));
    const miolo = new Mesh(new SphereGeometry(0.1, 20, 12), fosco('#ffffff', 0.35));
    miolo.position.set(0, 0.64, 0.17);
    pino.add(gota, miolo);
    const anel = new Mesh(new TorusGeometry(0.26, 0.022, 8, 48).rotateX(Math.PI / 2),
      new MeshBasicMaterial({ color: COR.turquesa, transparent: true, opacity: 0, depthWrite: false }));
    const base = new Group();
    base.position.set(x, 0.16, z);
    base.add(pino, anel, sombra(0.5, 0.5, 0.25));
    placa.add(base);
    return { pino, anel, i };
  });
  placa.position.y = 0.05;
  grupo.add(placa, sombra(3.6, 3.6, 0.22));
  return {
    grupo,
    camera: [[0, 3.9, 6.0], [0, 0.45, 0]],
    atualizar({ entrada, t, giro, x, y, parado }) {
      const e = passa(suave(0, 0.5, entrada));
      placa.rotation.set(-0.35 * (1 - e) + y * 0.08, -0.55 + (1 - e) * -0.6 + giro * 0.18 + x * 0.22, 0);
      placa.scale.setScalar(0.82 + 0.18 * e);
      for (const { pino, anel, i } of pinos) {
        const queda = quique(faixa(0.35 + i * 0.13, 0.7 + i * 0.13, entrada));
        pino.position.y = (1 - queda) * 2.2 + (parado ? 0 : Math.sin(t * 2 + i * 1.7) * 0.03);
        pino.visible = queda > 0;
        const fase = parado ? 0.4 : ((t * 0.55 + i * 0.33) % 1);
        anel.scale.setScalar(0.5 + fase * 1.5);
        anel.material.opacity = queda >= 1 ? (1 - fase) * 0.9 : 0;
      }
      grupo.position.y = parado ? 0 : Math.sin(t * 1.1) * 0.04;
    },
  };
}

/** Inventário: câmera de monitoramento num poste curto, varrendo — e seguindo o ponteiro quando ele chega perto. */
function camera() {
  const grupo = new Group();
  const branco = new MeshStandardMaterial({ color: '#f2f5fa', roughness: 0.38, envMapIntensity: 0.7 });
  const metal = fosco('#aeb8c6', 0.35, 0.6);
  const base = new Mesh(new CylinderGeometry(0.34, 0.38, 0.06, 32).translate(0, 0.03, 0), metal);
  const poste = new Mesh(new CylinderGeometry(0.06, 0.07, 1, 16).translate(0, 0.5, 0), metal);
  const junta = new Mesh(new SphereGeometry(0.1, 20, 12), metal);
  junta.position.y = 1.02;
  const pan = new Group();
  pan.position.y = 1.02;
  const tilt = new Group();
  pan.add(tilt);
  const corpo = new Mesh(new CylinderGeometry(0.21, 0.21, 0.95, 32).rotateX(Math.PI / 2).translate(0, 0.16, 0.12), branco);
  const traseira = new Mesh(new SphereGeometry(0.21, 24, 12, 0, Math.PI * 2, 0, Math.PI / 2).rotateX(-Math.PI / 2).translate(0, 0.16, -0.355), branco);
  const viseira = new Mesh(new CylinderGeometry(0.26, 0.26, 1.12, 32, 1, true, Math.PI * 0.38, Math.PI * 1.24).rotateX(Math.PI / 2).translate(0, 0.16, 0.2),
    new MeshStandardMaterial({ color: '#e6ebf2', roughness: 0.4, side: 2 }));
  const aro = new Mesh(new TorusGeometry(0.19, 0.025, 10, 40).translate(0, 0.16, 0.6), new MeshStandardMaterial({ color: COR.marca, roughness: 0.35 }));
  const vidro = new Mesh(new CylinderGeometry(0.18, 0.18, 0.02, 32).rotateX(Math.PI / 2).translate(0, 0.16, 0.595),
    new MeshStandardMaterial({ color: '#0a1322', roughness: 0.08, metalness: 0.6, envMapIntensity: 1.2 }));
  const lente = new Mesh(new CylinderGeometry(0.075, 0.075, 0.04, 24).rotateX(Math.PI / 2).translate(0, 0.16, 0.61),
    new MeshStandardMaterial({ color: '#03060c', roughness: 0.05, metalness: 0.8 }));
  const brilhoLente = new Mesh(new TorusGeometry(0.05, 0.008, 6, 24).translate(0, 0.16, 0.632), new MeshBasicMaterial({ color: COR.turquesa }));
  const suporte = new Mesh(new CylinderGeometry(0.035, 0.035, 0.12, 10).translate(0, 0.06, 0), metal);
  const ledMat = new MeshBasicMaterial({ color: COR.turquesa });
  const led = new Mesh(new SphereGeometry(0.022, 10, 8), ledMat);
  led.position.set(0.13, 0.04, 0.6);
  const ledHalo = halo(COR.turquesa, 0.28);
  ledHalo.position.copy(led.position);
  tilt.add(corpo, traseira, viseira, aro, vidro, lente, brilhoLente, suporte, led, ledHalo);
  grupo.add(base, poste, junta, pan, sombra(1.1, 1.1, 0.34));
  const FRENTE = Math.atan2(2.1, 3.45);
  let panAtual = FRENTE;
  let tiltAtual = 0;
  return {
    grupo,
    camera: [[2.1, 1.8, 3.45], [0, 1.02, 0]],
    atualizar({ entrada, t, giro, x, y, parado, ponteiro }) {
      const e = passa(suave(0, 0.55, entrada));
      poste.scale.y = Math.max(0.001, suave(0, 0.3, entrada));
      junta.position.y = 0.02 + poste.scale.y;
      pan.position.y = junta.position.y;
      pan.visible = entrada > 0.18;
      // Varre devagar; com o ponteiro por perto, olha para ele.
      // A lente aponta para quem olha (a câmera da cena fica em +x, +z) e gira a partir daí.
      const varre = parado ? -0.75 : -0.7 + Math.sin(t * 0.45) * 0.45;
      const alvoPan = FRENTE + (ponteiro ? Math.atan(x * 1.6) : varre);
      const alvoTilt = ponteiro ? Math.atan(y * 1.2) * 0.6 : 0.12 + Math.sin(t * 0.3) * 0.04;
      panAtual += (alvoPan - panAtual) * (parado ? 1 : 0.08);
      tiltAtual += (alvoTilt - tiltAtual) * (parado ? 1 : 0.08);
      pan.rotation.y = panAtual - (1 - e) * 1.6;
      tilt.rotation.x = tiltAtual;
      grupo.rotation.y = giro * 0.15;
      const pisca = parado || (t % 2) < 1.6 ? 1 : 0.15;
      ledMat.color.set(COR.turquesa).multiplyScalar(pisca);
      ledHalo.material.opacity = 0.75 * pisca;
    },
  };
}

/** Manutenção: cone com faixas refletivas e um sinalizador âmbar; cai, balança e assenta. */
function cone() {
  const grupo = new Group();
  const corpo = new Group();
  // Corpo com o pé que se abre em curva até a base, como os cones de PVC usados nas vias.
  const perfil = [[0.001, 1.32], [0.07, 1.32], [0.37, 0.2], [0.39, 0.12], [0.415, 0.08], [0.44, 0.06]].map(([a, b]) => new Vector2(a, b));
  const laranja = new MeshStandardMaterial({ color: '#ff6414', roughness: 0.5, envMapIntensity: 0.6 });
  const refletivo = new MeshStandardMaterial({ color: '#f5f6f8', roughness: 0.25, emissive: '#ffffff', emissiveIntensity: 0.12 });
  const faixaCone = (y0, y1) => {
    const r = (y) => 0.075 + ((1.32 - y) / 1.12) * 0.3;
    return new Mesh(new CylinderGeometry(r(y0), r(y1), y0 - y1, 40, 1, true).translate(0, (y0 + y1) / 2, 0), refletivo);
  };
  const casco = new Mesh(new LatheGeometry(perfil, 40), laranja);
  const chanfrado = new Shape();
  [[-0.46, -0.33], [-0.33, -0.46], [0.33, -0.46], [0.46, -0.33], [0.46, 0.33], [0.33, 0.46], [-0.33, 0.46], [-0.46, 0.33]].forEach(([a, b], i) => (i ? chanfrado.lineTo(a, b) : chanfrado.moveTo(a, b)));
  const sapata = new Mesh(chapa(chanfrado, 0.06, 0.015).rotateX(-Math.PI / 2), laranja);
  const sinalizador = new Mesh(new CylinderGeometry(0.11, 0.12, 0.12, 24).translate(0, 1.38, 0), fosco('#22262c', 0.5));
  const lenteMat = new MeshStandardMaterial({ color: '#ffcf70', roughness: 0.25, emissive: COR.ambar, emissiveIntensity: 0 });
  const lente = new Mesh(new SphereGeometry(0.115, 24, 12, 0, Math.PI * 2, 0, Math.PI / 2).translate(0, 1.44, 0), lenteMat);
  const brilho = halo(COR.ambar, 1.2);
  brilho.position.y = 1.5;
  corpo.add(casco, faixaCone(0.92, 0.76), faixaCone(0.58, 0.44), sapata, sinalizador, lente, brilho);
  const chao = sombra(1.6, 1.6, 0.4);
  grupo.add(corpo, chao);
  return {
    grupo,
    camera: [[2.1, 1.9, 3.5], [0, 0.9, 0]],
    atualizar({ entrada, t, giro, x, y, parado }) {
      // Cai (0–0,4), achata no impacto e balança até parar.
      const queda = faixa(0, 0.4, entrada);
      corpo.position.y = (1 - queda ** 2) * 2.4;
      const depois = Math.max(0, entrada - 0.4) * 2.6;
      const balanco = parado ? 0 : 0.2 * Math.exp(-depois * 2.4) * Math.sin(depois * 13) * (queda >= 1 ? 1 : 0);
      corpo.scale.set(1, queda >= 1 ? 1 - 0.08 * Math.exp(-depois * 9) : 1, 1);
      corpo.rotation.set(balanco + y * 0.06, giro * 0.5 + x * 0.25, balanco * 0.6);
      chao.material.opacity = 0.34 * (0.4 + 0.6 * queda);
      // Sinalizador: pisca em pulsos curtos depois que o cone assenta.
      const fase = (t * 1.25) % 1;
      const aceso = parado ? 1 : entrada < 1 ? 0 : (fase < 0.18 || (fase > 0.3 && fase < 0.46) ? 1 : 0);
      lenteMat.emissiveIntensity = 0.15 + aceso * 2.6;
      brilho.material.opacity = aceso * 0.85;
    },
  };
}

/** Multas: placa Mercosul fictícia que gira para a frente; um reflexo corre pela chapa de tempos em tempos. */
function placa() {
  const grupo = new Group();
  const chapaGrupo = new Group();
  const W = 1.62;
  const H = 0.52;
  const frente = imagem(1024, 329, () => {});
  const desenhar = () => {
    const g = frente.getContext('2d');
    g.clearRect(0, 0, 1024, 329);
    g.fillStyle = '#f7f8f5';
    g.fillRect(0, 0, 1024, 329);
    g.fillStyle = '#1b3f9c';
    g.fillRect(0, 0, 1024, 72);
    g.fillStyle = '#ffffff';
    g.font = '800 44px Overpass, system-ui, sans-serif';
    g.textAlign = 'center';
    g.textBaseline = 'middle';
    g.fillText('BRASIL', 512, 38);
    g.fillStyle = '#111317';
    g.font = '800 210px Overpass, system-ui, sans-serif';
    g.textBaseline = 'alphabetic';
    const texto = 'FIS2C26';
    const largura = g.measureText(texto).width;
    g.save();
    g.translate(512, 290);
    g.scale(Math.min(1, 900 / largura), 1);
    g.fillText(texto, 0, 0);
    g.restore();
    g.strokeStyle = '#2b2f36';
    g.lineWidth = 10;
    g.strokeRect(5, 5, 1014, 319);
    tex.needsUpdate = true;
  };
  const tex = textura(frente);
  tex.repeat.set(1 / W, 1 / H);
  desenhar();
  document.fonts?.load('800 64px Overpass').then(desenhar).catch(() => {});
  // Reflexo: faixa diagonal no mapa emissivo, que corre pela chapa (mesma UV da frente, deslocada).
  const reflexo = textura(imagem(512, 128, (g) => {
    g.fillStyle = '#000000';
    g.fillRect(0, 0, 512, 128);
    const r = g.createLinearGradient(196, 0, 316, 0);
    r.addColorStop(0, 'rgba(255,255,255,0)');
    r.addColorStop(0.5, 'rgba(255,255,255,0.9)');
    r.addColorStop(1, 'rgba(255,255,255,0)');
    g.fillStyle = r;
    g.setTransform(1, 0, -0.5, 1, 32, 0);
    g.fillRect(0, 0, 512, 128);
  }), false);
  reflexo.repeat.set(0.42 / W, 1 / H);
  const frenteMat = new MeshStandardMaterial({ map: tex, roughness: 0.32, metalness: 0.25, emissive: '#ffffff', emissiveMap: reflexo, emissiveIntensity: 0.55, envMapIntensity: 0.9 });
  const geo = chapa(retangulo(W, H, 0.045, 0, 0), 0.03, 0.008).translate(-W / 2, -H / 2, -0.015);
  // As duas tampas da extrusão usam a mesma textura: o verso aponta para um canto branco da frente.
  const uv = geo.attributes.uv;
  const normal = geo.attributes.normal;
  for (let i = 0; i < uv.count; i++) if (normal.getZ(i) < -0.5) uv.setXY(i, W * 0.985, H * 0.4);
  const corpo = new Mesh(geo, [frenteMat, fosco('#b9c0ca', 0.35, 0.6)]);
  const parafusos = [-1, 1].map((s) => {
    const p = new Mesh(new CylinderGeometry(0.022, 0.022, 0.012, 16).rotateX(Math.PI / 2), fosco('#c9ced6', 0.3, 0.8));
    p.position.set(s * 0.6, H / 2 - 0.13, 0.02);
    return p;
  });
  chapaGrupo.add(corpo, ...parafusos);
  chapaGrupo.position.y = 0.42;
  grupo.add(chapaGrupo, sombra(2.0, 0.7, 0.26));
  return {
    grupo,
    camera: [[0.4, 0.9, 3.3], [0, 0.68, 0]],
    atualizar({ entrada, t, giro, x, y, parado }) {
      const e = passa(suave(0, 0.7, entrada));
      chapaGrupo.rotation.set(
        0.05 + (parado ? 0 : Math.sin(t * 0.8) * 0.04) + y * 0.1,
        (1 - e) * -1.5 + 0.22 + (parado ? 0 : Math.sin(t * 0.55) * 0.1) + giro * 0.2 + x * 0.25,
        0,
      );
      chapaGrupo.position.y = 0.42 + (1 - e) * 0.4 + (parado ? 0 : Math.sin(t * 1.2) * 0.025);
      // Reflexo a cada 3,6 s (ou parado no meio da chapa).
      const ciclo = parado ? 0.45 : (t % 3.6) / 1.1;
      reflexo.offset.x = MathUtils.lerp(0.75, -0.2, MathUtils.clamp(ciclo, 0, 1));
    },
  };
}

/** Revisões: semáforo que testa as luzes ao aparecer e depois segue o ciclo, com o vermelho em destaque. */
function semaforo() {
  const grupo = new Group();
  const cabeca = new Group();
  const metal = fosco('#5a6472', 0.4, 0.5);
  const base = new Mesh(new CylinderGeometry(0.26, 0.3, 0.05, 28).translate(0, 0.025, 0), metal);
  const poste = new Mesh(new CylinderGeometry(0.055, 0.06, 0.78, 16).translate(0, 0.39, 0), metal);
  const caixa = new Mesh(chapa(retangulo(0.5, 1.4, 0.1), 0.34, 0.03).translate(0, 0, -0.17), new MeshStandardMaterial({ color: COR.grafite, roughness: 0.45, metalness: 0.2 }));
  const fundo = new Mesh(chapa(retangulo(0.64, 1.56, 0.12), 0.03, 0.01).translate(0, 0, -0.2), fosco('#0b0f15', 0.6));
  cabeca.add(caixa, fundo);
  const cores = ['#ff3a24', COR.ambar, '#2bd67b'];
  const lampadas = cores.map((cor, i) => {
    const y = 0.44 - i * 0.44;
    const mat = new MeshStandardMaterial({ color: new Color(cor).multiplyScalar(0.18), roughness: 0.2, emissive: cor, emissiveIntensity: 0 });
    const lampada = new Mesh(new SphereGeometry(0.15, 28, 14, 0, Math.PI * 2, 0, Math.PI / 2).rotateX(Math.PI / 2).scale(1, 1, 0.45).translate(0, y, 0.17), mat);
    const visor = new Mesh(new CylinderGeometry(0.19, 0.19, 0.2, 28, 1, true, Math.PI * 0.62, Math.PI * 0.76).rotateX(Math.PI / 2).translate(0, y, 0.27),
      new MeshStandardMaterial({ color: '#141a22', roughness: 0.5, side: 2 }));
    const brilho = halo(cor, 0.95);
    brilho.position.set(0, y, 0.2);
    cabeca.add(lampada, visor, brilho);
    return { mat, brilho };
  });
  cabeca.position.y = 0.78 + 0.72;
  grupo.add(base, poste, cabeca, sombra(1.2, 1.2, 0.3));
  // Ciclo de 6 s: verde 2,4 s → âmbar 0,9 s → vermelho 2,7 s.
  const qualAcesa = (t) => {
    const f = t % 6;
    return f < 2.4 ? 2 : f < 3.3 ? 1 : 0;
  };
  return {
    grupo,
    camera: [[1.5, 1.7, 4.3], [0, 1.18, 0]],
    atualizar({ entrada, t, giro, x, y, parado }) {
      const e = passa(suave(0, 0.45, entrada));
      grupo.scale.setScalar(0.75 + 0.25 * e);
      cabeca.rotation.set(y * 0.06, (1 - e) * 1.2 - 0.32 + giro * 0.25 + x * 0.25, 0);
      // Teste das três luzes durante a entrada; depois, o ciclo.
      lampadas.forEach(({ mat, brilho }, i) => {
        let k;
        if (parado) k = i === 0 ? 1 : 0;
        else if (entrada < 1) k = Math.max(0, 1 - Math.abs(entrada - (0.45 + i * 0.16)) / 0.09);
        else k = qualAcesa(t) === i ? 1 : 0;
        mat.emissiveIntensity = 0.05 + k * 2.4;
        brilho.material.opacity = k * 0.8;
      });
    },
  };
}

/** Planejar reforço: o mapa de calor em quadrados que viram colunas; as quatro prioridades numeradas. */
function calor() {
  const grupo = new Group();
  const tabuleiro = new Group();
  const VALORES = [
    [0.12, 0.22, 0.34, 0.28, 0.18, 0.08],
    [0.22, 0.52, 0.8, 0.58, 0.32, 0.14],
    [0.28, 0.66, 1, 0.74, 0.4, 0.2],
    [0.18, 0.42, 0.62, 0.86, 0.5, 0.24],
    [0.08, 0.18, 0.3, 0.42, 0.3, 0.14],
  ];
  const PASSO = 0.44;
  const rampa = [[0, '#fff1b8'], [0.35, '#ffd166'], [0.6, COR.ambar], [0.8, '#ff7a1a'], [1, '#e8452c']];
  const corDe = (v) => {
    for (let i = 1; i < rampa.length; i++) {
      if (v <= rampa[i][0]) {
        const k = (v - rampa[i - 1][0]) / (rampa[i][0] - rampa[i - 1][0]);
        return new Color(rampa[i - 1][1]).lerp(new Color(rampa[i][1]), k);
      }
    }
    return new Color(rampa[rampa.length - 1][1]);
  };
  const celulas = [];
  VALORES.forEach((linha, l) => linha.forEach((v, c) => {
    celulas.push({ v, x: (c - 2.5) * PASSO, z: (l - 2) * PASSO, d: Math.hypot(c - 2, l - 2) });
  }));
  const base = new Mesh(chapa(retangulo(PASSO * 6 + 0.24, PASSO * 5 + 0.24, 0.12), 0.08, 0.02).rotateX(-Math.PI / 2), new MeshStandardMaterial({ color: '#17263b', roughness: 0.6, metalness: 0.2 }));
  const colunas = new InstancedMesh(chapa(retangulo(0.38, 0.38, 0.05), 1, 0.02).rotateX(-Math.PI / 2),
    new MeshStandardMaterial({ roughness: 0.45, emissive: '#ffffff', emissiveIntensity: 0.06, envMapIntensity: 0.6 }), celulas.length);
  celulas.forEach((c, i) => colunas.setColorAt(i, corDe(c.v)));
  tabuleiro.add(base, colunas);
  // Números das quatro maiores, como na captura.
  const maiores = [...celulas].sort((a, b) => b.v - a.v).slice(0, 4);
  const numeros = maiores.map((c, i) => {
    const s = new Sprite(new SpriteMaterial({
      map: textura(imagem(128, 128, (g) => {
        g.fillStyle = '#0f1a2b';
        g.beginPath();
        g.arc(64, 64, 58, 0, Math.PI * 2);
        g.fill();
        g.lineWidth = 8;
        g.strokeStyle = '#ffffff';
        g.stroke();
        g.fillStyle = '#ffffff';
        g.font = '800 70px Overpass, system-ui, sans-serif';
        g.textAlign = 'center';
        g.textBaseline = 'middle';
        g.fillText(String(i + 1), 64, 70);
      })),
      transparent: true, depthWrite: false,
    }));
    s.scale.setScalar(0.3);
    tabuleiro.add(s);
    return { s, c };
  });
  grupo.add(tabuleiro, sombra(3.8, 3.4, 0.5));
  const aux = new Object3D();
  return {
    grupo,
    camera: [[3.3, 4.2, 4.9], [0, 0.35, 0]],
    atualizar({ entrada, t, giro, x, y, parado }) {
      tabuleiro.rotation.set(y * 0.08, -0.35 + giro * 0.3 + x * 0.3 + (parado ? 0 : Math.sin(t * 0.25) * 0.08), 0);
      celulas.forEach((c, i) => {
        // As colunas sobem em onda a partir do quadrado mais quente; depois "respiram" de leve.
        const sobe = passa(faixa(0.12 + c.d * 0.1, 0.5 + c.d * 0.1, entrada));
        const respira = parado ? 1 : 1 + 0.06 * Math.sin(t * 1.6 - c.d * 1.1);
        const h = Math.max(0.001, (0.05 + c.v * 1.15) * sobe * respira);
        aux.position.set(c.x, 0.08, c.z);
        aux.scale.set(1, h, 1);
        aux.updateMatrix();
        colunas.setMatrixAt(i, aux.matrix);
        c.h = 0.08 + h;
      });
      colunas.instanceMatrix.needsUpdate = true;
      numeros.forEach(({ s, c }, i) => {
        const aparece = suave(0.75 + i * 0.05, 0.9 + i * 0.05, entrada);
        s.position.set(c.x, c.h + 0.22, c.z);
        s.material.opacity = aparece;
        s.scale.setScalar(0.3 * (0.6 + 0.4 * aparece));
      });
    },
  };
}

const FABRICAS = { mapa, camera, cone, placa, semaforo, calor };

// ---------- O renderer ----------
export function criarEnfeites({ lado }) {
  let renderer = null;
  let tela = null;
  let bitmap = false;
  const opcoes = { alpha: true, antialias: true, premultipliedAlpha: true, failIfMajorPerformanceCaveat: true };
  if (typeof OffscreenCanvas !== 'undefined' && 'transferToImageBitmap' in OffscreenCanvas.prototype) {
    try {
      tela = new OffscreenCanvas(lado, lado);
      renderer = new WebGLRenderer({ ...opcoes, canvas: tela });
      bitmap = true;
    } catch {
      renderer = null;
    }
  }
  if (!renderer) {
    try {
      tela = document.createElement('canvas');
      renderer = new WebGLRenderer({ ...opcoes, canvas: tela });
    } catch {
      return null;
    }
  }
  renderer.setPixelRatio(1);
  renderer.setSize(lado, lado, false);
  renderer.setClearColor(0x000000, 0);
  renderer.toneMapping = NeutralToneMapping;
  renderer.toneMappingExposure = 1.0;

  const cena = new Scene();
  const pmrem = new PMREMGenerator(renderer);
  cena.environment = pmrem.fromScene(new RoomEnvironment(), 0.04, 0.1, 100, { size: 64 }).texture;
  pmrem.dispose();
  cena.add(new HemisphereLight('#ffffff', '#9fb0c8', 0.9));
  const chave = new DirectionalLight('#ffffff', 2.2);
  chave.position.set(-3, 5, 4);
  const recorte = new DirectionalLight('#a9d8ff', 1.1);
  recorte.position.set(4, 2.5, -3);
  cena.add(chave, recorte);

  const enfeites = {};
  for (const [tipo, fabrica] of Object.entries(FABRICAS)) {
    const objeto = fabrica();
    objeto.grupo.visible = false;
    const cam = new PerspectiveCamera(30, 1, 0.1, 50);
    cam.position.set(...objeto.camera[0]);
    cam.lookAt(new Vector3(...objeto.camera[1]));
    cena.add(objeto.grupo);
    enfeites[tipo] = { ...objeto, cam };
  }

  return {
    bitmap,
    /** Compila os shaders de todos os enfeites sem travar a página. */
    preparar() {
      for (const e of Object.values(enfeites)) e.grupo.visible = true;
      const pronto = renderer.compileAsync(cena, enfeites.mapa.cam).catch(() => {});
      return pronto.then(() => {
        for (const e of Object.values(enfeites)) e.grupo.visible = false;
      });
    },
    /** Desenha um enfeite e entrega o quadro ao contexto do canvas dele (bitmaprenderer ou 2D). */
    desenhar(tipo, estado, destino) {
      const e = enfeites[tipo];
      e.grupo.visible = true;
      e.atualizar(estado);
      renderer.render(cena, e.cam);
      e.grupo.visible = false;
      if (bitmap) {
        destino.transferFromImageBitmap(tela.transferToImageBitmap());
      } else {
        destino.clearRect(0, 0, lado, lado);
        destino.drawImage(tela, 0, 0);
      }
    },
    /** O contexto WebGL caiu (aviso único). */
    aoPerder(f) {
      tela.addEventListener?.('webglcontextlost', (evento) => {
        evento.preventDefault();
        f();
      });
      tela.addEventListener?.('contextlost', (evento) => {
        evento.preventDefault();
        f();
      });
    },
  };
}
