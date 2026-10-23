/*
 * Planos 3D das cenas de recursos: tomadas noturnas numa avenida, uma depois de cada cena com plano (inventário,
 * manutenção, revisões). Um único mundo — avenida, prédios, postes, trânsito — com três cenários ao longo dela; cada
 * plano é um caminho de câmera ligado à rolagem e um arranjo de luzes. O renderer é próprio (a hero continua com o
 * dela) e o canvas é emprestado ao plano em cena por planos.js: nada é copiado a cada quadro.
 *
 * Coordenadas em metros. A avenida corre ao longo de z, com o horizonte em -z. Do lado direito (x > 0) o trânsito vai
 * para -z (lanternas); do esquerdo, vem para +z (faróis). Chão em y = 0. Tudo é gerado aqui (geometria e texturas em
 * canvas), sem arquivos para baixar — a CSP da landing não aceita data: nem outras origens.
 */
import {
  AdditiveBlending, BoxGeometry, BufferAttribute, BufferGeometry, CanvasTexture, CatmullRomCurve3, Color,
  CylinderGeometry, DirectionalLight, DoubleSide, DynamicDrawUsage, ExtrudeGeometry, FogExp2, Group, HalfFloatType,
  HemisphereLight, IcosahedronGeometry, InstancedMesh, LatheGeometry, MathUtils, Mesh, MeshBasicMaterial,
  MeshStandardMaterial, Object3D, PCFShadowMap, PerspectiveCamera, PlaneGeometry, PointLight, Quaternion,
  RepeatWrapping, Scene, ShaderMaterial, Shape, SphereGeometry, SpotLight, Sprite, SpriteMaterial, SRGBColorSpace,
  TorusGeometry, UniformsLib, UniformsUtils, Vector2, Vector3, WebGLRenderer, WebGLRenderTarget, ACESFilmicToneMapping,
  BackSide,
} from 'three';
import { mergeGeometries } from 'three/addons/utils/BufferGeometryUtils.js';
import { EffectComposer } from 'three/addons/postprocessing/EffectComposer.js';
import { RenderPass } from 'three/addons/postprocessing/RenderPass.js';
import { UnrealBloomPass } from 'three/addons/postprocessing/UnrealBloomPass.js';
import { OutputPass } from 'three/addons/postprocessing/OutputPass.js';
import { ShaderPass } from 'three/addons/postprocessing/ShaderPass.js';

const COR = { turquesa: '#7cf5e1', ambar: '#ffb21a', marca: '#2152e8' };
// Avenida: meia pista de 8 m para cada lado do canteiro (1 m), calçadas de 4,2 m.
const VIA = { canteiro: 1, borda: 8, calcada: 12.2, z0: 90, comprimento: 520 };
// Faixas de rolamento: x do centro, sentido (−1 = para o horizonte) e velocidade em m/s (constante por faixa: os
// veículos de uma faixa nunca se alcançam).
const FAIXAS = [
  { x: 2.75, dir: -1, v: 11.2 }, { x: 6.25, dir: -1, v: 13.1 },
  { x: -2.75, dir: 1, v: 12.4 }, { x: -6.25, dir: 1, v: 10.6 },
];
const CENARIO = { radar: 0, manutencao: -150, revisoes: -300 };
const LIMITE = 40; // km/h da lombada
// Pose de repouso de cada plano (movimento reduzido): o quadro mais legível do caminho.
export const REPOUSO = { radar: 0.52, manutencao: 0.32, revisoes: 0.14 };
const PRIORIDADES = [
  { tipo: 'cancela', z: -289, valor: 32 }, { tipo: 'patinete', z: -296.5, valor: 27 },
  { tipo: 'lombada', z: -304, valor: 18 }, { tipo: 'camera', z: -311.5, valor: 9 },
];

// ---------- Utilidades ----------
function aleatorio(semente) {
  let a = semente >>> 0;
  return () => {
    a = (a + 0x6d2b79f5) | 0;
    let t = Math.imul(a ^ (a >>> 15), 1 | a);
    t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
    return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
  };
}
const suave = (a, b, x) => {
  const t = MathUtils.clamp((x - a) / (b - a), 0, 1);
  return t * t * (3 - 2 * t);
};
const linear = (hex, k = 1) => new Color(hex).multiplyScalar(k);
/** O mesmo, numa cor que já existe (para o que muda a cada quadro). */
const tingir = (cor, hex, k) => cor.set(hex).multiplyScalar(k);

function imagem(w, h, desenhar) {
  const c = document.createElement('canvas');
  c.width = w;
  c.height = h;
  desenhar(c.getContext('2d'), w, h);
  return c;
}
/** Canvas pixel a pixel; f(u, v) com v = 0 embaixo (como a UV da textura) devolve [r, g, b, a] de 0 a 255. */
function pixels(w, h, f) {
  return imagem(w, h, (g) => {
    const d = g.createImageData(w, h);
    for (let y = 0; y < h; y++) {
      for (let x = 0; x < w; x++) {
        const px = f(x / (w - 1), 1 - y / (h - 1), x, y);
        d.data.set(px, (y * w + x) * 4);
      }
    }
    g.putImageData(d, 0, 0);
  });
}
function textura(canvas, { cor = true, repetir = null } = {}) {
  const t = new CanvasTexture(canvas);
  if (cor) t.colorSpace = SRGBColorSpace;
  if (repetir) {
    t.wrapS = RepeatWrapping;
    t.wrapT = RepeatWrapping;
    t.repeat.set(...repetir);
  }
  t.anisotropy = 4;
  return t;
}
/** Ruído de valor que se repete nas bordas (para texturas em mosaico), de 0 a 1. */
function ruido(tam, oitavas, semente) {
  const r = aleatorio(semente);
  const out = new Float32Array(tam * tam);
  let soma = 0;
  for (const [celulas, peso] of oitavas) {
    soma += peso;
    const grade = Array.from({ length: celulas * celulas }, r);
    const em = (x, y) => grade[((y + celulas) % celulas) * celulas + ((x + celulas) % celulas)];
    for (let y = 0; y < tam; y++) {
      for (let x = 0; x < tam; x++) {
        const fx = (x / tam) * celulas;
        const fy = (y / tam) * celulas;
        const x0 = Math.floor(fx);
        const y0 = Math.floor(fy);
        const tx = suave(0, 1, fx - x0);
        const ty = suave(0, 1, fy - y0);
        const a = MathUtils.lerp(em(x0, y0), em(x0 + 1, y0), tx);
        const b = MathUtils.lerp(em(x0, y0 + 1), em(x0 + 1, y0 + 1), tx);
        out[y * tam + x] += MathUtils.lerp(a, b, ty) * peso;
      }
    }
  }
  for (let i = 0; i < out.length; i++) out[i] /= soma;
  return out;
}

/** Geometria sem índice, com cor por vértice (para juntar peças de cores diferentes numa malha só). */
function pintar(geo, cor) {
  const g = geo.index ? geo.toNonIndexed() : geo;
  const c = cor instanceof Color ? cor : new Color(cor);
  const n = g.attributes.position.count;
  const arr = new Float32Array(n * 3);
  for (let i = 0; i < n; i++) arr.set([c.r, c.g, c.b], i * 3);
  g.setAttribute('color', new BufferAttribute(arr, 3));
  if (!g.attributes.uv) g.setAttribute('uv', new BufferAttribute(new Float32Array(n * 2), 2));
  return g;
}
const juntar = (lista) => mergeGeometries(lista.map((g) => (g.attributes.color ? g : pintar(g, '#ffffff'))));
const caixa = (w, h, d, x = 0, y = 0, z = 0) => new BoxGeometry(w, h, d).translate(x, y, z);
const cilindro = (rt, rb, h, seg, x = 0, y = 0, z = 0) => new CylinderGeometry(rt, rb, h, seg).translate(x, y, z);
function perfil(pontos) {
  return new Shape(pontos.map(([x, y]) => new Vector2(x, y)));
}
/** Extrusão centrada em z, com chanfro para dentro (a silhueta é exatamente o perfil). */
function extrusao(forma, largura, chanfro = 0.05, curvas = 6) {
  const fundo = largura - 2 * chanfro;
  const g = new ExtrudeGeometry(forma, {
    depth: fundo, bevelEnabled: chanfro > 0, bevelThickness: chanfro, bevelSize: chanfro, bevelOffset: -chanfro,
    bevelSegments: 2, curveSegments: curvas,
  });
  g.translate(0, 0, -fundo / 2);
  return g;
}
function retangulo(w, h, r) {
  const s = new Shape();
  const x = -w / 2;
  const y = -h / 2;
  s.moveTo(x + r, y);
  s.lineTo(x + w - r, y);
  s.absarc(x + w - r, y + r, r, -Math.PI / 2, 0, false);
  s.lineTo(x + w, y + h - r);
  s.absarc(x + w - r, y + h - r, r, 0, Math.PI / 2, false);
  s.lineTo(x + r, y + h);
  s.absarc(x + r, y + h - r, r, Math.PI / 2, Math.PI, false);
  s.lineTo(x, y + r);
  s.absarc(x + r, y + r, r, Math.PI, Math.PI * 1.5, false);
  return s;
}
/** Arco das ondas da marca (aberto para +x), com pontas redondas. */
function arco(raio, meio, espessura) {
  const h = espessura / 2;
  const s = new Shape();
  s.absarc(0, 0, raio + h, -meio, meio, false);
  s.absarc(raio * Math.cos(meio), raio * Math.sin(meio), h, meio, meio + Math.PI, false);
  s.absarc(0, 0, raio - h, meio, -meio, true);
  s.absarc(raio * Math.cos(meio), -raio * Math.sin(meio), h, Math.PI - meio, 2 * Math.PI - meio, false);
  return s;
}
/** Quadrilátero solto (dois triângulos) de a0-a1 (v = 0) a b0-b1 (v = 1), com cor por vértice. */
function quad(a0, a1, b1, b0, cor) {
  const pos = [...a0, ...a1, ...b1, ...a0, ...b1, ...b0];
  const uv = [0, 0, 1, 0, 1, 1, 0, 0, 1, 1, 0, 1];
  const g = new BufferGeometry();
  g.setAttribute('position', new BufferAttribute(new Float32Array(pos), 3));
  g.setAttribute('uv', new BufferAttribute(new Float32Array(uv), 2));
  g.computeVertexNormals();
  return pintar(g, cor);
}

// ---------- Texturas ----------
function texturas() {
  // Rastro e facho: forte junto da luz (v = 0), some ao longe; bordas laterais suaves.
  const rastro = textura(pixels(32, 128, (u, v) => {
    const a = 255 * (1 - v) ** 1.8 * Math.sin(Math.PI * u) ** 1.4;
    return [a, a, a, 255];
  }), { cor: false });
  const mancha = textura(pixels(128, 128, (u, v) => {
    const d = Math.hypot(u - 0.5, v - 0.5) * 2;
    const a = 255 * Math.exp(-d * d * 3.2) * (1 - suave(0.8, 1, d));
    return [a, a, a, 255];
  }), { cor: false });
  // Asfalto: variação de tom em três escalas e manchas mais lisas (molhadas) no mapa de rugosidade.
  const T = 128;
  const tom = ruido(T, [[4, 0.5], [16, 0.3], [64, 0.2]], 7);
  const molhado = ruido(T, [[3, 0.7], [9, 0.3]], 11);
  const graos = aleatorio(5);
  const asfalto = textura(pixels(T, T, (u, v, x, y) => {
    const k = 0.78 + tom[y * T + x] * 0.36 + (graos() - 0.5) * 0.16;
    return [26 * k, 30 * k, 37 * k, 255];
  }), { repetir: [9, 60] });
  const rugosidade = textura(pixels(T, T, (u, v, x, y) => {
    const m = suave(0.42, 0.66, molhado[y * T + x]);
    const r = 255 * MathUtils.lerp(0.78, 0.42, m) + (graos() - 0.5) * 16;
    return [r, r, r, 255];
  }), { cor: false, repetir: [9, 60] });
  // Janelas dos prédios: célula de 32 px = 2,2 m × 3,2 m (um andar). A fileira de baixo é de lojas.
  const r = aleatorio(23);
  const janelas = textura(imagem(512, 512, (g) => {
    g.fillStyle = '#07090d';
    g.fillRect(0, 0, 512, 512);
    const tons = ['#ffc98a', '#ffb070', '#ffd9a8', '#d6e4ff', '#9fb8ff'];
    for (let linha = 0; linha < 16; linha++) {
      const ocupado = r() > 0.18;
      for (let col = 0; col < 16; col++) {
        const x = col * 32;
        const y = linha * 32;
        if (linha === 15) {
          const acesa = r() > 0.55;
          g.fillStyle = acesa ? tons[Math.floor(r() * 3)] : '#0c1017';
          g.globalAlpha = acesa ? 0.25 + r() * 0.3 : 1;
          g.fillRect(x + 1, y + 10, 30, 20);
          g.globalAlpha = 1;
          continue;
        }
        const acesa = ocupado && r() < 0.24;
        g.fillStyle = acesa ? tons[Math.floor(r() * tons.length)] : '#0b0f16';
        g.globalAlpha = acesa ? 0.2 + r() * 0.55 : 1;
        g.fillRect(x + 10, y + 11, 13, 14);
        g.globalAlpha = 1;
      }
    }
  }), { repetir: [1, 1] });
  return { rastro, mancha, asfalto, rugosidade, janelas };
}

// Fonte de matriz de pontos 5 × 7 do painel da lombada.
const DIGITOS = {
  0: ['01110', '10001', '10011', '10101', '11001', '10001', '01110'], 1: ['00100', '01100', '00100', '00100', '00100', '00100', '01110'],
  2: ['01110', '10001', '00001', '00010', '00100', '01000', '11111'], 3: ['11110', '00001', '00001', '01110', '00001', '00001', '11110'],
  4: ['00010', '00110', '01010', '10010', '11111', '00010', '00010'], 5: ['11111', '10000', '11110', '00001', '00001', '10001', '01110'],
  6: ['00110', '01000', '10000', '11110', '10001', '10001', '01110'], 7: ['11111', '00001', '00010', '00100', '01000', '01000', '01000'],
  8: ['01110', '10001', '10001', '01110', '10001', '10001', '01110'], 9: ['01110', '10001', '10001', '01111', '00001', '00010', '01100'],
  '-': ['00000', '00000', '00000', '11111', '00000', '00000', '00000'],
};
function desenharPainel(g, valor) {
  const w = g.canvas.width;
  const h = g.canvas.height;
  g.fillStyle = '#030405';
  g.fillRect(0, 0, w, h);
  const texto = valor == null ? '--' : String(valor).padStart(2, ' ');
  const acima = valor != null && valor > LIMITE;
  const passo = 13;
  const x0 = 16;
  const y0 = 25;
  for (let d = 0; d < 2; d++) {
    const mapa = DIGITOS[texto[d]] ?? DIGITOS['-'];
    for (let l = 0; l < 7; l++) {
      for (let c = 0; c < 5; c++) {
        const aceso = texto[d] !== ' ' && mapa[l][c] === '1';
        g.fillStyle = aceso ? (acima ? '#ff3a24' : COR.ambar) : '#1c1206';
        g.beginPath();
        g.arc(x0 + d * 76 + c * passo, y0 + l * passo, aceso ? 5.2 : 4.2, 0, Math.PI * 2);
        g.fill();
      }
    }
  }
  g.fillStyle = acima ? '#ff3a24' : COR.ambar;
  g.font = '800 22px Overpass, system-ui, sans-serif';
  g.textBaseline = 'alphabetic';
  g.fillText('km/h', 184, 103);
}

// ---------- Materiais com shader (luz no ar, hologramas) ----------
const FOG_GLSL = /* glsl */ `
  float neblina() {
    #ifdef USE_FOG
      #ifdef FOG_EXP2
        return 1.0 - exp(-fogDensity * fogDensity * vFogDepth * vFogDepth);
      #else
        return smoothstep(fogNear, fogFar, vFogDepth);
      #endif
    #else
      return 0.0;
    #endif
  }`;

/** Cone de luz no ar (aditivo): forte junto da lâmpada, bordas suaves, some na neblina. */
function materialFacho(cor, intensidade) {
  return new ShaderMaterial({
    uniforms: UniformsUtils.merge([UniformsLib.fog, { cor: { value: linear(cor) }, intensidade: { value: intensidade } }]),
    vertexShader: /* glsl */ `
      #include <common>
      #include <fog_pars_vertex>
      varying float vAltura;
      varying vec3 vNormal;
      varying vec3 vVista;
      void main() {
        vec4 p = vec4(position, 1.0);
        vec3 n = normal;
        #ifdef USE_INSTANCING
          p = instanceMatrix * p;
          n = mat3(instanceMatrix) * n;
        #endif
        vec4 mvPosition = modelViewMatrix * p;
        vAltura = uv.y;
        vNormal = normalize(normalMatrix * n);
        vVista = normalize(-mvPosition.xyz);
        gl_Position = projectionMatrix * mvPosition;
        #include <fog_vertex>
      }`,
    fragmentShader: /* glsl */ `
      uniform vec3 cor;
      uniform float intensidade;
      #include <common>
      #include <fog_pars_fragment>
      varying float vAltura;
      varying vec3 vNormal;
      varying vec3 vVista;
      ${FOG_GLSL}
      void main() {
        float borda = pow(abs(dot(normalize(vNormal), normalize(vVista))), 1.8);
        float queda = mix(0.06, 1.0, pow(vAltura, 2.2));
        float a = intensidade * borda * queda * (1.0 - neblina());
        gl_FragColor = vec4(cor * a, 1.0);
      }`,
    transparent: true, depthWrite: false, blending: AdditiveBlending, side: DoubleSide, fog: true,
  });
}

/** Coluna de prioridade (holograma): borda acesa, faixas que sobem, topo marcado; cresce com "altura". */
function materialColuna(cor) {
  return new ShaderMaterial({
    uniforms: UniformsUtils.merge([UniformsLib.fog, {
      cor: { value: linear(cor) }, tempo: { value: 0 }, altura: { value: 1 }, brilho: { value: 1 },
    }]),
    vertexShader: /* glsl */ `
      #include <common>
      #include <fog_pars_vertex>
      varying vec2 vUv;
      varying vec3 vNormal;
      varying vec3 vVista;
      void main() {
        vUv = uv;
        vec4 mvPosition = modelViewMatrix * vec4(position, 1.0);
        vNormal = normalize(normalMatrix * normal);
        vVista = normalize(-mvPosition.xyz);
        gl_Position = projectionMatrix * mvPosition;
        #include <fog_vertex>
      }`,
    fragmentShader: /* glsl */ `
      uniform vec3 cor;
      uniform float tempo;
      uniform float altura;
      uniform float brilho;
      #include <common>
      #include <fog_pars_fragment>
      varying vec2 vUv;
      varying vec3 vNormal;
      varying vec3 vVista;
      ${FOG_GLSL}
      void main() {
        if (vUv.y > altura) discard;
        float borda = 1.0 - abs(dot(normalize(vNormal), normalize(vVista)));
        float faixas = 0.55 + 0.45 * smoothstep(0.35, 0.5, abs(fract(vUv.y * 14.0 - tempo * 0.35) - 0.5));
        float topo = smoothstep(altura - 0.035, altura, vUv.y);
        float a = (0.16 + 0.9 * pow(borda, 2.2)) * faixas + topo * 2.2;
        a *= mix(0.55, 1.0, vUv.y) * brilho * (1.0 - neblina());
        gl_FragColor = vec4(cor * a, 1.0);
      }`,
    transparent: true, depthWrite: false, blending: AdditiveBlending, side: DoubleSide, fog: true,
  });
}

/** Acabamento de filme, depois da saída em sRGB: vinheta, grão e uma aberração cromática leve nas bordas. */
const FILME = {
  uniforms: { tDiffuse: { value: null }, tempo: { value: 0 }, grao: { value: 0.045 }, aspecto: { value: 2.39 } },
  vertexShader: /* glsl */ `
    varying vec2 vUv;
    void main() { vUv = uv; gl_Position = projectionMatrix * modelViewMatrix * vec4(position, 1.0); }`,
  fragmentShader: /* glsl */ `
    uniform sampler2D tDiffuse;
    uniform float tempo;
    uniform float grao;
    uniform float aspecto;
    varying vec2 vUv;
    float hash(vec2 p) { return fract(sin(dot(p, vec2(12.9898, 78.233))) * 43758.5453); }
    void main() {
      vec2 c = vUv - 0.5;
      vec2 desvio = c * vec2(1.0, 1.0 / aspecto) * 0.0028 * dot(c, c) * 4.0;
      vec3 cor = vec3(texture2D(tDiffuse, vUv + desvio).r, texture2D(tDiffuse, vUv).g, texture2D(tDiffuse, vUv - desvio).b);
      float lum = dot(cor, vec3(0.2126, 0.7152, 0.0722));
      cor += vec3(-0.004, 0.010, 0.020) * (1.0 - smoothstep(0.0, 0.35, lum));
      cor = mix(cor, cor * cor * (3.0 - 2.0 * cor), 0.22);
      float d = length(c * vec2(aspecto / 1.6, 1.0));
      cor *= mix(1.0, 0.42, smoothstep(0.35, 1.05, d));
      float n = hash(vUv * vec2(1931.0, 1117.0) + fract(tempo * 7.13)) - 0.5;
      cor += n * grao * (1.0 - cor * 0.6);
      gl_FragColor = vec4(cor, 1.0);
    }`,
};

// ---------- O mundo ----------
export function criarMundo({ celular }) {
  let renderer;
  try {
    renderer = new WebGLRenderer({ antialias: false, alpha: false, powerPreference: 'high-performance', failIfMajorPerformanceCaveat: true });
  } catch {
    return null;
  }
  renderer.toneMapping = ACESFilmicToneMapping;
  renderer.toneMappingExposure = 1.05;
  renderer.shadowMap.enabled = !celular;
  renderer.shadowMap.type = PCFShadowMap;
  renderer.setClearColor('#05080d', 1);
  const canvas = renderer.domElement;
  canvas.setAttribute('aria-hidden', 'true');

  const tex = texturas();
  const cena = new Scene();
  const corNeblina = new Color('#0d1626');
  cena.fog = new FogExp2(corNeblina, 0.0125);
  const camera = new PerspectiveCamera(30, 2.39, 0.1, 1400);

  // ---------- Céu: gradiente com o brilho da cidade no horizonte ----------
  const ceu = new Mesh(new SphereGeometry(1000, 32, 16), new ShaderMaterial({
    uniforms: { zenite: { value: linear('#02040a') }, horizonte: { value: corNeblina.clone() }, cidade: { value: linear('#3b2c2a', 0.7) } },
    vertexShader: /* glsl */ `
      varying vec3 vDir;
      void main() { vDir = normalize(position); vec4 p = projectionMatrix * modelViewMatrix * vec4(position, 1.0); gl_Position = p.xyww; }`,
    fragmentShader: /* glsl */ `
      uniform vec3 zenite;
      uniform vec3 horizonte;
      uniform vec3 cidade;
      varying vec3 vDir;
      void main() {
        float h = vDir.y;
        vec3 c = mix(horizonte, zenite, smoothstep(-0.02, 0.5, h));
        c += cidade * exp(-max(h, 0.0) * 10.0) * (0.45 + 0.55 * max(-vDir.z, 0.0));
        gl_FragColor = vec4(c, 1.0);
      }`,
    side: BackSide, depthWrite: false,
  }));
  ceu.renderOrder = -1;
  cena.add(ceu);

  // ---------- Luzes (quantidade fixa: trocar de plano só muda posição e intensidade, sem recompilar shader) ----------
  const ceuLuz = new HemisphereLight('#2a4170', '#07080b', 0.6);
  const lua = new DirectionalLight('#7f97cf', 0.38);
  lua.position.set(-40, 50, -80);
  const chave = new SpotLight('#ffd6a3', 0, 40, 0.75, 0.55, 1.6);
  chave.castShadow = !celular;
  chave.shadow.mapSize.set(1024, 1024);
  // normalBias pequeno: um valor alto descola a sombra do pé das peças, e elas parecem flutuar.
  chave.shadow.bias = -0.0004;
  chave.shadow.normalBias = 0.012;
  chave.shadow.camera.near = 1;
  chave.shadow.camera.far = 40;
  const giro = new SpotLight(COR.ambar, 0, 26, 0.42, 0.7, 1.4);
  const ponto1 = new PointLight(COR.ambar, 0, 14, 2);
  const ponto2 = new PointLight(COR.turquesa, 0, 14, 2);
  cena.add(ceuLuz, lua, chave, chave.target, giro, giro.target, ponto1, ponto2);

  // ---------- Avenida ----------
  const larguraChao = 120;
  const chao = new Mesh(new PlaneGeometry(larguraChao, VIA.comprimento + 200).rotateX(-Math.PI / 2).translate(0, 0, VIA.z0 - VIA.comprimento / 2),
    new MeshStandardMaterial({ map: tex.asfalto, roughnessMap: tex.rugosidade, roughness: 1, metalness: 0.08 }));
  chao.receiveShadow = true;
  tex.asfalto.repeat.set(larguraChao / 9, (VIA.comprimento + 200) / 9);
  tex.rugosidade.repeat.copy(tex.asfalto.repeat);

  // Calçadas, guias e canteiro (uma malha só).
  const comp = VIA.comprimento + 200;
  const zc = VIA.z0 - VIA.comprimento / 2;
  const concreto = new Color('#3a414c');
  const guia = new Color('#8d96a3');
  // Calçada e canteiro encostam nas guias sem dividir face com elas (faces no mesmo plano piscam: z-fighting).
  const largCalcada = VIA.calcada - VIA.borda - 0.18;
  const meioFio = juntar([
    pintar(caixa(largCalcada, 0.16, comp, VIA.borda + 0.18 + largCalcada / 2, 0.08, zc), concreto),
    pintar(caixa(largCalcada, 0.16, comp, -VIA.borda - 0.18 - largCalcada / 2, 0.08, zc), concreto),
    pintar(caixa(0.18, 0.18, comp, VIA.borda + 0.09, 0.09, zc), guia),
    pintar(caixa(0.18, 0.18, comp, -VIA.borda - 0.09, 0.09, zc), guia),
    pintar(caixa(VIA.canteiro * 2 - 0.24, 0.22, comp, 0, 0.11, zc), new Color('#1a2a20')),
    pintar(caixa(0.12, 0.24, comp, VIA.canteiro - 0.06, 0.12, zc), guia),
    pintar(caixa(0.12, 0.24, comp, -VIA.canteiro + 0.06, 0.12, zc), guia),
  ]);
  const calcadas = new Mesh(meioFio, new MeshStandardMaterial({ vertexColors: true, roughness: 0.82 }));
  // Lotes entre a calçada e os prédios: chão escuro e fosco (o asfalto molhado fica só na pista).
  const lotes = new Mesh(juntar([-1, 1].map((lado) => pintar(caixa(60, 0.14, comp, lado * (VIA.calcada + 30), 0.07, zc), '#151a20'))),
    new MeshStandardMaterial({ vertexColors: true, roughness: 0.95 }));
  lotes.receiveShadow = true;
  calcadas.receiveShadow = true;

  // Pintura: bordas contínuas, divisória tracejada, faixa de pedestres e linha de retenção perto da lombada,
  // laços detectores na pista da direita.
  const pintura = [];
  const branco = new Color('#c9cfd8');
  for (const lado of [1, -1]) {
    pintura.push(caixa(0.14, 0.02, comp, lado * (VIA.borda - 0.35), 0.006, zc));
    for (let z = VIA.z0; z > VIA.z0 - VIA.comprimento; z -= 12) pintura.push(caixa(0.13, 0.02, 4, lado * 4.5, 0.006, z));
  }
  for (const lado of [1, -1]) {
    for (let x = VIA.canteiro + 0.55; x < VIA.borda - 0.4; x += 0.95) pintura.push(caixa(0.48, 0.02, 3.4, lado * x, 0.007, CENARIO.radar - 9));
  }
  pintura.push(caixa(VIA.borda - VIA.canteiro - 0.3, 0.02, 0.4, (VIA.borda + VIA.canteiro) / 2, 0.007, CENARIO.radar - 5.8));
  const sinalizacao = new Mesh(juntar(pintura.map((g) => pintar(g, branco))), new MeshStandardMaterial({ vertexColors: true, roughness: 0.55 }));
  sinalizacao.receiveShadow = true;
  // Laços: o corte escuro no asfalto (dois por faixa) e o brilho que acende quando um carro passa.
  const lacos = [];
  const cortes = [];
  for (const faixa of FAIXAS.slice(0, 2)) {
    for (const dz of [13.5, 9.5]) {
      for (const [w, d, ox, oz] of [[2, 0.06, 0, -0.8], [2, 0.06, 0, 0.8], [0.06, 1.6, -1, 0], [0.06, 1.6, 1, 0]]) {
        cortes.push(caixa(w, 0.02, d, faixa.x + ox, 0.008, CENARIO.radar + dz + oz));
      }
    }
    const brilho = new Mesh(new PlaneGeometry(2.6, 6.2).rotateX(-Math.PI / 2),
      new MeshBasicMaterial({ color: linear(COR.turquesa, 1.4), alphaMap: tex.mancha, transparent: true, opacity: 0, blending: AdditiveBlending, depthWrite: false }));
    brilho.position.set(faixa.x, 0.03, CENARIO.radar + 11.5);
    lacos.push(brilho);
  }
  const cortesLaco = new Mesh(juntar(cortes.map((g) => pintar(g, '#05070a'))), new MeshBasicMaterial({ vertexColors: true }));
  cena.add(chao, calcadas, lotes, sinalizacao, cortesLaco, ...lacos);

  // ---------- Prédios: uma malha, janelas por UV em metros ----------
  const r = aleatorio(41);
  const predios = [];
  for (const lado of [1, -1]) {
    let z = VIA.z0 + 40;
    while (z > VIA.z0 - VIA.comprimento - 120) {
      const w = 10 + r() * 16;
      const d = 10 + r() * 14;
      const alto = r() < 0.18;
      const h = alto ? 26 + r() * 22 : 7 + r() * 13;
      const recuo = VIA.calcada + 2 + r() * 5 + (alto ? 8 : 0);
      const g = new BoxGeometry(d, h, w);
      const desvio = Math.floor(r() * 16) / 16;
      // Faces: +x, −x, +y, −y, +z, −z (4 vértices cada). Topo e base vão para um canto escuro da textura.
      const uv = g.attributes.uv;
      const dims = [[w, h], [w, h], [0, 0], [0, 0], [d, h], [d, h]];
      for (let f = 0; f < 6; f++) {
        for (let k = 0; k < 4; k++) {
          const i = f * 4 + k;
          const [fw, fh] = dims[f];
          if (!fw) uv.setXY(i, 0.995, 0.5);
          else uv.setXY(i, (uv.getX(i) * fw) / 35.2 + desvio, (uv.getY(i) * fh) / 51.2);
        }
      }
      g.translate(lado * (recuo + d / 2), h / 2, z - w / 2);
      predios.push(g);
      z -= w + 1 + r() * 4;
    }
  }
  // Silhueta distante no fim da avenida.
  for (let i = 0; i < 26; i++) {
    const w = 14 + r() * 24;
    const h = 18 + r() * 50;
    const g = new BoxGeometry(w, h, 16);
    const uv = g.attributes.uv;
    for (let k = 0; k < uv.count; k++) uv.setXY(k, (uv.getX(k) * w) / 35.2, (uv.getY(k) * h) / 51.2);
    g.translate(-160 + i * 13 + r() * 6, h / 2, VIA.z0 - VIA.comprimento - 140 - r() * 80);
    predios.push(g);
  }
  const cidade = new Mesh(mergeGeometries(predios), new MeshBasicMaterial({ map: tex.janelas, color: linear('#ffffff', 0.85) }));
  cena.add(cidade);

  // ---------- Postes: instâncias dos dois lados, com o cone de luz no ar e a poça no chão ----------
  const postes = [];
  // Perto dos cenários, os postes do lado da cena saem da frente da câmera e um fica logo atrás da peça (luz de recorte).
  const livre = (lado, z) => !(lado > 0 && (Math.abs(z - CENARIO.radar) < 12 || (z < -268 && z > -322)))
    && !(lado < 0 && Math.abs(z - CENARIO.manutencao) < 16);
  for (let z = VIA.z0 + 10; z > VIA.z0 - VIA.comprimento - 40; z -= 32) {
    if (livre(1, z)) postes.push({ x: VIA.borda + 0.7, z, lado: 1 });
    if (livre(-1, z - 16)) postes.push({ x: -VIA.borda - 0.7, z: z - 16, lado: -1 });
  }
  postes.push({ x: VIA.borda + 0.7, z: CENARIO.radar - 12, lado: 1 }, { x: VIA.borda + 0.7, z: -300.2, lado: 1 });
  const BRACO = 2.6;
  const ALTURA_POSTE = 8.6;
  const posteGeo = juntar([
    pintar(cilindro(0.09, 0.14, ALTURA_POSTE, 10, 0, ALTURA_POSTE / 2, 0), '#4a525e'),
    pintar(cilindro(0.05, 0.05, BRACO, 6).rotateZ(Math.PI / 2).translate(-BRACO / 2, ALTURA_POSTE - 0.1, 0), '#4a525e'),
    pintar(caixa(0.95, 0.16, 0.42, -BRACO, ALTURA_POSTE - 0.1, 0), '#2b3039'),
  ]);
  const lampadaGeo = new PlaneGeometry(0.82, 0.32).rotateX(Math.PI / 2).translate(-BRACO, ALTURA_POSTE - 0.19, 0);
  const fachoGeo = new CylinderGeometry(0.3, 4.2, ALTURA_POSTE - 0.3, 28, 1, true).translate(-BRACO, (ALTURA_POSTE - 0.3) / 2, 0);
  const pocaGeo = new PlaneGeometry(14, 14).rotateX(-Math.PI / 2).translate(-BRACO, 0.025, 0);
  const posteMalha = new InstancedMesh(posteGeo, new MeshStandardMaterial({ vertexColors: true, roughness: 0.5, metalness: 0.6 }), postes.length);
  const lampadas = new InstancedMesh(lampadaGeo, new MeshBasicMaterial({ color: linear('#ffe2bd', 9), side: DoubleSide }), postes.length);
  const fachos = new InstancedMesh(fachoGeo, materialFacho('#ffd9a8', 0.1), postes.length);
  const pocas = new InstancedMesh(pocaGeo, new MeshBasicMaterial({ color: linear('#ffcf96', 0.2), alphaMap: tex.mancha, transparent: true, blending: AdditiveBlending, depthWrite: false }), postes.length);
  /** Lâmpada do poste mais perto de z, de um lado: a luz-chave do cenário sai dela. */
  const lampadaPerto = (lado, z) => {
    const p = postes.filter((q) => q.lado === lado).reduce((a, b) => (Math.abs(b.z - z) < Math.abs(a.z - z) ? b : a));
    return new Vector3(p.x - lado * BRACO, ALTURA_POSTE - 0.3, p.z);
  };
  const aux = new Object3D();
  postes.forEach((p, i) => {
    aux.position.set(p.x, 0, p.z);
    aux.rotation.set(0, p.lado > 0 ? 0 : Math.PI, 0);
    aux.updateMatrix();
    for (const m of [posteMalha, lampadas, fachos, pocas]) m.setMatrixAt(i, aux.matrix);
  });
  posteMalha.castShadow = true;
  // Reflexo de cada lâmpada no asfalto molhado: uma mancha no ponto especular, na linha entre o pé da luz e a câmera
  // (a distância s = d·h / (h + altura da câmera)), que se alonga em direção à câmera quanto mais rasante o olhar.
  // Reposicionada a cada quadro; some com a distância.
  const reflexos = new InstancedMesh(new PlaneGeometry(1, 1).rotateX(-Math.PI / 2),
    new MeshBasicMaterial({ color: '#ffffff', alphaMap: tex.mancha, transparent: true, blending: AdditiveBlending, depthWrite: false, fog: false }), postes.length);
  reflexos.instanceMatrix.setUsage(DynamicDrawUsage);
  reflexos.frustumCulled = false;
  const corReflexo = linear('#ffc98e', 0.42);
  const tomReflexo = new Color();
  const densidade = cena.fog.density;
  function refletir(cam) {
    postes.forEach((p, i) => {
      const lx = p.x - p.lado * BRACO;
      const dx = cam.x - lx;
      const dz = cam.z - p.z;
      const d = Math.hypot(dx, dz);
      const k = (ALTURA_POSTE - 0.2) / (ALTURA_POSTE - 0.2 + Math.max(cam.y, 0.3));
      const comp = MathUtils.clamp(d * 0.2 * MathUtils.clamp(2.5 / Math.max(cam.y, 0.5), 0.15, 1.6), 0.7, 9);
      aux.position.set(lx + dx * k, 0.03, p.z + dz * k);
      aux.rotation.set(0, Math.atan2(dx, dz), 0);
      aux.scale.set(0.55, 1, comp);
      aux.updateMatrix();
      reflexos.setMatrixAt(i, aux.matrix);
      const f = Math.exp(-((densidade * d) ** 2));
      reflexos.setColorAt(i, tomReflexo.copy(corReflexo).multiplyScalar(f));
    });
    aux.scale.set(1, 1, 1);
    reflexos.instanceMatrix.needsUpdate = true;
    reflexos.instanceColor.needsUpdate = true;
  }
  cena.add(posteMalha, lampadas, fachos, pocas, reflexos);

  // ---------- Árvores no canteiro (silhuetas escuras, recortadas pela luz dos postes) ----------
  const copaGeo = juntar([
    pintar(cilindro(0.1, 0.16, 2.6, 6, 0, 1.3, 0), '#1d1712'),
    pintar(new IcosahedronGeometry(1.5, 1).translate(0, 3.4, 0), '#13261b'),
    pintar(new IcosahedronGeometry(1.1, 1).translate(0.8, 3.0, 0.4), '#102018'),
    pintar(new IcosahedronGeometry(1.0, 1).translate(-0.7, 3.1, -0.5), '#152a1e'),
  ]);
  const arvoresPos = [];
  for (let z = VIA.z0; z > VIA.z0 - VIA.comprimento; z -= 13 + r() * 9) {
    // Fora dos cenários e dos caminhos de câmera (uma copa na frente da lente vira um borrão escuro).
    if ([[-18, 60], [CENARIO.manutencao - 18, CENARIO.manutencao + 36], [-322, -252]].some(([a, b]) => z > a && z < b)) continue;
    arvoresPos.push(z);
  }
  const arvores = new InstancedMesh(copaGeo, new MeshStandardMaterial({ vertexColors: true, roughness: 0.95 }), arvoresPos.length);
  arvoresPos.forEach((z, i) => {
    aux.position.set(0, 0.2, z);
    aux.rotation.set(0, r() * 6.28, 0);
    aux.scale.setScalar(0.8 + r() * 0.5);
    aux.updateMatrix();
    arvores.setMatrixAt(i, aux.matrix);
  });
  aux.scale.setScalar(1);
  arvores.castShadow = true;
  cena.add(arvores);

  // ---------- Trânsito: carros e ônibus instanciados, com faróis, lanternas, rastros de longa exposição e fachos ----------
  // Carro: frente em −z. Perfil lateral extrudado na largura; vidros escuros; rodas.
  const carroGeo = juntar([
    pintar(extrusao(perfil([[-2.2, 0.3], [2.12, 0.3], [2.22, 0.5], [2.2, 0.78], [1.95, 0.9], [1.15, 1.0], [-1.55, 1.04], [-2.12, 0.98], [-2.24, 0.78], [-2.25, 0.45]]), 1.76, 0.07), '#ffffff'),
    pintar(extrusao(perfil([[1.16, 0.98], [0.45, 1.41], [-0.78, 1.44], [-1.56, 1.03]]), 1.6, 0.06), new Color(0.025, 0.028, 0.035)),
    ...[[-1.38, 0.78], [-1.38, -0.78], [1.4, 0.78], [1.4, -0.78]].map(([x, z]) => pintar(cilindro(0.33, 0.33, 0.22, 14).rotateX(Math.PI / 2).translate(x, 0.33, z), new Color(0.01, 0.01, 0.012))),
  ]).rotateY(Math.PI / 2);
  const onibusGeo = juntar([
    pintar(extrusao(retangulo(12, 2.75, 0.32), 2.5, 0.08, 4).translate(0, 1.72, 0), '#ffffff'),
    ...[[-4.2, 1.1], [-4.2, -1.1], [3.6, 1.1], [3.6, -1.1]].map(([x, z]) => pintar(cilindro(0.5, 0.5, 0.3, 14).rotateX(Math.PI / 2).translate(x, 0.5, z), new Color(0.01, 0.01, 0.012))),
  ]).rotateY(Math.PI / 2);
  // Luzes do veículo (cores acima de 1 = HDR, que o bloom espalha). Frente em −z depois de girar.
  const luzesVeiculo = (meio, alturaFarol, alturaLanterna, larg, extra = []) => juntar([
    ...[-1, 1].map((s) => pintar(caixa(0.36, 0.13, 0.06, s * larg, alturaFarol, -meio - 0.01), new Color(6, 6, 6.6))),
    ...[-1, 1].map((s) => pintar(caixa(0.34, 0.12, 0.06, s * larg, alturaLanterna, meio + 0.01), new Color(3.4, 0.14, 0.08))),
    ...extra,
  ]);
  const carroLuzGeo = luzesVeiculo(2.22, 0.72, 0.86, 0.6);
  // Ônibus: faixa de janelas iluminadas dos dois lados e o letreiro âmbar na frente.
  const janelasOnibus = [];
  for (const s of [-1, 1]) {
    for (let zj = -5.2; zj < 4.4; zj += 1.45) janelasOnibus.push(pintar(caixa(0.02, 0.85, 1.25, s * 1.26, 2.15, zj), new Color(0.95, 0.78, 0.52)));
  }
  const onibusLuzGeo = luzesVeiculo(6.02, 0.75, 1.0, 0.9, [
    ...janelasOnibus,
    pintar(caixa(1.7, 0.28, 0.04, 0, 2.86, -6.03), new Color(3.2, 1.6, 0.2)),
  ]);
  // Rastros: fitas horizontais e verticais atrás de cada luz; fachos dos faróis no chão, à frente.
  const rastros = (meio, alturaFarol, alturaLanterna, larg, compRastro, compFacho) => {
    const lista = [];
    for (const s of [-1, 1]) {
      const xs = s * larg;
      for (const [y, z0, cor] of [[alturaFarol, -meio, new Color(1.7, 1.75, 2)], [alturaLanterna, meio, new Color(2.2, 0.1, 0.06)]]) {
        const z1 = z0 + compRastro;
        lista.push(quad([xs - 0.09, y, z0], [xs + 0.09, y, z0], [xs + 0.09, y, z1], [xs - 0.09, y, z1], cor));
        lista.push(quad([xs, y - 0.07, z0], [xs, y + 0.07, z0], [xs, y + 0.07, z1], [xs, y - 0.07, z1], cor));
      }
    }
    const f0 = -meio - 0.4;
    const f1 = f0 - compFacho;
    lista.push(quad([-0.9, 0.03, f0], [0.9, 0.03, f0], [2.6, 0.03, f1], [-2.6, 0.03, f1], new Color(0.75, 0.7, 0.6)));
    return juntar(lista);
  };
  const carroRastroGeo = rastros(2.22, 0.72, 0.86, 0.6, 9, 15);
  const onibusRastroGeo = rastros(6.02, 0.75, 1.0, 0.9, 9, 17);
  const matCarro = new MeshStandardMaterial({ vertexColors: true, roughness: 0.32, metalness: 0.55 });
  const matLuz = new MeshBasicMaterial({ vertexColors: true });
  const matRastro = new MeshBasicMaterial({ vertexColors: true, alphaMap: tex.rastro, transparent: true, blending: AdditiveBlending, depthWrite: false, side: DoubleSide });

  // Veículos por faixa, com distância fixa entre eles (o laço tem o comprimento da avenida).
  const pinturas = ['#d9dde3', '#9aa3ad', '#5c6470', '#1b1f26', '#7a1d1d', '#1f3357', '#c8ccd2', '#2c3a2f'];
  const veiculos = [];
  FAIXAS.forEach((faixa, f) => {
    const n = 7;
    let s = r() * 40;
    for (let i = 0; i < n; i++) {
      // Ônibus só na faixa interna da esquerda: nenhum caminho de câmera passa por ela abaixo de 3,6 m, e nenhum
      // enquadramento olha através dela para a peça do cenário.
      const onibus = f === 2 && (i === 1 || i === 4);
      veiculos.push({ faixa, s, onibus, cor: new Color(pinturas[Math.floor(r() * pinturas.length)]), jitter: Math.round((r() - 0.5) * 7) });
      s += VIA.comprimento / n * (0.7 + r() * 0.6);
    }
  });
  const carros = veiculos.filter((v) => !v.onibus);
  const onibus = veiculos.filter((v) => v.onibus);
  const instanciar = (geo, mat, lista, sombra) => {
    const m = new InstancedMesh(geo, mat, lista.length);
    m.instanceMatrix.setUsage(DynamicDrawUsage);
    m.castShadow = sombra;
    m.frustumCulled = false;
    return m;
  };
  const malhasCarro = [instanciar(carroGeo, matCarro, carros, true), instanciar(carroLuzGeo, matLuz, carros, false), instanciar(carroRastroGeo, matRastro, carros, false)];
  const malhasOnibus = [instanciar(onibusGeo, matCarro, onibus, true), instanciar(onibusLuzGeo, matLuz, onibus, false), instanciar(onibusRastroGeo, matRastro, onibus, false)];
  carros.forEach((v, i) => malhasCarro[0].setColorAt(i, v.cor));
  onibus.forEach((v, i) => malhasOnibus[0].setColorAt(i, i ? new Color('#e8e2d0') : new Color('#d6dce4')));
  cena.add(...malhasCarro, ...malhasOnibus);
  const zDe = (v, t) => {
    const s = (v.s + v.faixa.v * t) % VIA.comprimento;
    return v.faixa.dir < 0 ? VIA.z0 - s : VIA.z0 - VIA.comprimento + s;
  };
  function posicionarTransito(t) {
    for (const [lista, malhas] of [[carros, malhasCarro], [onibus, malhasOnibus]]) {
      lista.forEach((v, i) => {
        aux.position.set(v.faixa.x, 0, zDe(v, t));
        aux.rotation.set(0, v.faixa.dir < 0 ? 0 : Math.PI, 0);
        aux.updateMatrix();
        for (const m of malhas) m.setMatrixAt(i, aux.matrix);
      });
      for (const m of malhas) m.instanceMatrix.needsUpdate = true;
    }
  }

  // ---------- Cenário 1: lombada eletrônica na calçada da direita ----------
  const fontePronta = document.fonts?.load('800 64px Overpass').catch(() => {}) ?? Promise.resolve();
  const matMetal = new MeshStandardMaterial({ color: '#1f2733', roughness: 0.48, metalness: 0.65 });
  const matEscuro = new MeshStandardMaterial({ color: '#0d1117', roughness: 0.4, metalness: 0.3 });
  const matTurquesa = new MeshBasicMaterial({ color: linear(COR.turquesa, 3) });
  function lombada(escala = 1, comPlaca = true) {
    const g = new Group();
    const corpo = new Mesh(juntar([
      pintar(extrusao(retangulo(0.95, 1.05, 0.08), 0.6, 0.03).translate(0, 0.525, 0), '#1f2733'),
      pintar(caixa(0.34, 1.6, 0.26, 0, 1.8, 0), '#26303d'),
      pintar(extrusao(retangulo(1.56, 0.9, 0.1), 0.32, 0.04).translate(0, 3.0, 0), '#141a22'),
      pintar(caixa(0.6, 0.26, 0.34, 0, 3.6, 0), '#1a212b'),
    ]), new MeshStandardMaterial({ vertexColors: true, roughness: 0.45, metalness: 0.6 }));
    corpo.castShadow = true;
    corpo.receiveShadow = true;
    // Sapata de concreto e sombra de contato: o encontro com a calçada escurece, e a peça assenta.
    const sapata = new Mesh(caixa(1.2, 0.06, 0.85, 0, 0.03, 0), new MeshStandardMaterial({ color: '#4a515c', roughness: 0.9 }));
    sapata.receiveShadow = true;
    const contato = new Mesh(new PlaneGeometry(1.9, 1.5).rotateX(-Math.PI / 2),
      new MeshBasicMaterial({ color: '#000000', alphaMap: tex.mancha, transparent: true, opacity: 0.85, depthWrite: false }));
    contato.position.y = 0.065;
    g.add(sapata, contato);
    // Painel de LED: textura redesenhada só quando o número muda.
    const telaPainel = document.createElement('canvas');
    telaPainel.width = 256;
    telaPainel.height = 128;
    const ctx = telaPainel.getContext('2d');
    desenharPainel(ctx, null);
    const texPainel = textura(telaPainel);
    const painel = new Mesh(new PlaneGeometry(1.36, 0.68), new MeshBasicMaterial({ map: texPainel, color: linear('#ffffff', 3.4) }));
    painel.position.set(0, 3.0, 0.165);
    // Faixa turquesa do sensor e a lente.
    const faixaSensor = new Mesh(new BoxGeometry(0.44, 0.035, 0.02), matTurquesa);
    faixaSensor.position.set(0, 3.52, 0.175);
    const lente = new Mesh(new CylinderGeometry(0.075, 0.075, 0.05, 20).rotateX(Math.PI / 2), matEscuro);
    lente.position.set(0.16, 3.62, 0.18);
    g.add(corpo, painel, faixaSensor, lente);
    if (comPlaca) {
      const telaPlaca = imagem(256, 256, () => {});
      const desenharPlaca = () => {
        const c = telaPlaca.getContext('2d');
        c.fillStyle = '#ffffff';
        c.beginPath();
        c.arc(128, 128, 124, 0, Math.PI * 2);
        c.fill();
        c.lineWidth = 30;
        c.strokeStyle = '#d4161c';
        c.beginPath();
        c.arc(128, 128, 106, 0, Math.PI * 2);
        c.stroke();
        c.fillStyle = '#111111';
        c.textAlign = 'center';
        c.font = '800 104px Overpass, system-ui, sans-serif';
        c.fillText(String(LIMITE), 128, 150);
        c.font = '700 30px Overpass, system-ui, sans-serif';
        c.fillText('km/h', 128, 190);
        texPlaca.needsUpdate = true;
      };
      const texPlaca = textura(telaPlaca);
      desenharPlaca();
      fontePronta.then(desenharPlaca);
      const placa = new Mesh(new CylinderGeometry(0.44, 0.44, 0.03, 40).rotateX(Math.PI / 2).rotateZ(Math.PI / 2),
        new MeshStandardMaterial({ map: texPlaca, emissiveMap: texPlaca, emissive: '#ffffff', emissiveIntensity: 0.12, roughness: 0.4 }));
      placa.position.set(0, 4.25, 0);
      const haste = new Mesh(new BoxGeometry(0.08, 0.5, 0.06), matMetal);
      haste.position.set(0, 3.88, -0.04);
      placa.castShadow = true;
      g.add(placa, haste);
    }
    g.scale.setScalar(escala);
    return { g, ctx, texPainel, valor: undefined };
  }
  const lomb = lombada();
  lomb.g.position.set(VIA.borda + 1.5, 0.16, CENARIO.radar);
  // A lombada encara o trânsito que vem de +z; levemente voltada para a pista.
  lomb.g.rotation.y = -0.18;
  const LUZ_RADAR = lampadaPerto(1, CENARIO.radar);
  // Ondas da marca saindo do sensor em direção à pista.
  const ondas = [0, 1, 2].map(() => {
    const m = new Mesh(new ExtrudeGeometry(arco(0.4, 0.7, 0.045), { depth: 0.02, bevelEnabled: false, curveSegments: 12 }),
      new MeshBasicMaterial({ color: linear(COR.turquesa, 1.6), transparent: true, opacity: 0, blending: AdditiveBlending, depthWrite: false }));
    // Centro do arco a ~0,9 m do sensor, na direção do trânsito que chega (−x, +z): as ondas nascem longe da caixa.
    m.rotation.y = -2.2;
    m.position.set(VIA.borda + 0.95, 3.72, CENARIO.radar + 0.95);
    cena.add(m);
    return m;
  });
  cena.add(lomb.g);

  // ---------- Cenário 2: manutenção de um radar de braço na calçada da esquerda ----------
  const Z2 = CENARIO.manutencao;
  const XE = -VIA.borda - 1.4;
  const radarBraco = new Group();
  const estrutura = new Mesh(juntar([
    pintar(cilindro(0.13, 0.17, 6.2, 14, 0, 3.1, 0), '#5a6472'),
    pintar(cilindro(0.08, 0.08, 4.6, 10).rotateZ(Math.PI / 2).translate(2.3, 6.0, 0), '#5a6472'),
    pintar(caixa(0.72, 0.42, 0.5, 4.3, 5.68, 0), '#aeb6c1'),
    pintar(caixa(0.8, 0.06, 0.62, 4.3, 5.92, 0.04), '#c9cfd8'),
    pintar(caixa(0.3, 0.3, 0.06, 4.3, 5.68, 0.27), '#0b0e13'),
    pintar(caixa(0.36, 0.24, 0.3, 3.55, 5.62, 0), '#d3d8de'),
  ]), new MeshStandardMaterial({ vertexColors: true, roughness: 0.45, metalness: 0.4 }));
  estrutura.castShadow = true;
  // Anel de situação na câmera: vermelho (falha), âmbar (em execução), turquesa (concluída).
  const matSituacao = new MeshBasicMaterial({ color: linear('#ff3a24', 3) });
  const anel = new Mesh(new TorusGeometry(0.2, 0.028, 8, 40), matSituacao);
  anel.position.set(4.3, 5.68, 0.31);
  // Gabinete com porta que abre e o miolo aceso.
  const gabinete = new Mesh(extrusao(retangulo(0.9, 1.4, 0.06), 0.55, 0.03), new MeshStandardMaterial({ color: '#c9ced6', roughness: 0.5, metalness: 0.35 }));
  gabinete.position.set(0.85, 0.86, 0);
  gabinete.castShadow = true;
  gabinete.receiveShadow = true;
  const miolo = new Mesh(new PlaneGeometry(0.72, 1.18), new MeshBasicMaterial({
    map: textura(imagem(64, 96, (c) => {
      c.fillStyle = '#071410';
      c.fillRect(0, 0, 64, 96);
      const rr = aleatorio(3);
      for (let i = 0; i < 70; i++) {
        c.fillStyle = rr() > 0.7 ? '#ffb21a' : rr() > 0.4 ? '#4dffb0' : '#7cf5e1';
        c.globalAlpha = 0.4 + rr() * 0.6;
        c.fillRect(Math.floor(rr() * 14) * 4 + 4, Math.floor(rr() * 22) * 4 + 4, 2, 2);
      }
    })), color: linear('#ffffff', 0.9),
  }));
  miolo.position.set(0.85, 0.86, 0.281);
  const dobradica = new Group();
  dobradica.position.set(0.85 - 0.45, 0.86, 0.29);
  const porta = new Mesh(new BoxGeometry(0.88, 1.36, 0.03).translate(0.44, 0, 0), new MeshStandardMaterial({ color: '#bfc5ce', roughness: 0.45, metalness: 0.4 }));
  porta.castShadow = true;
  dobradica.add(porta);
  radarBraco.add(estrutura, anel, gabinete, miolo, dobradica);
  // Na calçada da esquerda, o braço avança sobre a pista (+x), com a câmera voltada para o trânsito que vem.
  radarBraco.position.set(XE, 0.16, Z2);
  cena.add(radarBraco);
  // Furgão de serviço na calçada, com a barra de luz âmbar girando.
  const furgaoGeo = juntar([
    pintar(extrusao(perfil([[-2.6, 0.38], [2.6, 0.38], [2.62, 1.12], [2.35, 1.32], [1.72, 2.12], [1.25, 2.28], [-2.5, 2.3], [-2.62, 2.18]]), 2.0, 0.08), '#e4e7eb'),
    pintar(extrusao(perfil([[2.34, 1.34], [1.72, 2.1], [1.18, 2.1], [1.18, 1.36]]), 2.04, 0.05), new Color(0.02, 0.025, 0.03)),
    pintar(caixa(5.1, 0.16, 2.04, 0, 1.1, 0), '#ff7a1a'),
    ...[[-1.7, 0.85], [-1.7, -0.85], [1.75, 0.85], [1.75, -0.85]].map(([x, z]) => pintar(cilindro(0.38, 0.38, 0.26, 14).rotateX(Math.PI / 2).translate(x, 0.38, z), new Color(0.01, 0.01, 0.012))),
  ]).rotateY(-Math.PI / 2);
  const furgao = new Mesh(furgaoGeo, new MeshStandardMaterial({ vertexColors: true, roughness: 0.36, metalness: 0.3 }));
  furgao.position.set(-VIA.borda - 2.3, 0.16, Z2 - 8);
  furgao.castShadow = true;
  const matBarra = new MeshBasicMaterial({ color: linear(COR.ambar, 1) });
  const barra = new Mesh(new BoxGeometry(1.25, 0.13, 0.28), matBarra);
  barra.position.set(furgao.position.x, 2.56, furgao.position.z + 1.0);
  barra.rotation.y = Math.PI / 2;
  const fachoGiro = new Mesh(new CylinderGeometry(0.15, 2.4, 9, 24, 1, true).rotateZ(Math.PI / 2).translate(4.5, 0, 0), materialFacho(COR.ambar, 0.0));
  fachoGiro.position.copy(barra.position);
  cena.add(furgao, barra, fachoGiro);
  // Cones com faixas refletivas, isolando a área de trabalho.
  const coneGeo = juntar([
    pintar(new LatheGeometry([[0.001, 0.74], [0.04, 0.74], [0.16, 0.06], [0.001, 0.06]].map(([x, y]) => new Vector2(x, y)), 18), '#ff5a14'),
    pintar(new LatheGeometry([[0.083, 0.5], [0.112, 0.36], [0.107, 0.36], [0.078, 0.5]].map(([x, y]) => new Vector2(x, y)), 18).scale(1.04, 1, 1.04), new Color(1.3, 1.3, 1.3)),
    pintar(new LatheGeometry([[0.122, 0.3], [0.142, 0.2], [0.137, 0.2], [0.117, 0.3]].map(([x, y]) => new Vector2(x, y)), 18).scale(1.04, 1, 1.04), new Color(1.3, 1.3, 1.3)),
    pintar(caixa(0.42, 0.05, 0.42, 0, 0.025, 0), '#1a1a1a'),
  ]);
  const posCones = [[-7.72, Z2 + 4.6], [-7.72, Z2 + 2.4], [-7.72, Z2 + 0.2], [-7.72, Z2 - 2.0], [-7.72, Z2 - 4.2], [-8.7, Z2 - 5.4]];
  const cones = new InstancedMesh(coneGeo, new MeshStandardMaterial({ vertexColors: true, roughness: 0.55 }), posCones.length);
  posCones.forEach(([x, z], i) => {
    aux.position.set(x, x < -VIA.borda ? 0.16 : 0, z);
    aux.rotation.set(0, i, 0);
    aux.updateMatrix();
    cones.setMatrixAt(i, aux.matrix);
  });
  cones.castShadow = true;
  cena.add(cones);
  // Refletor de trabalho num tripé, apontado para o gabinete.
  const refletor = new Group();
  const tripe = new Mesh(juntar([
    ...[0, 2.1, 4.2].map((a) => pintar(cilindro(0.018, 0.018, 1.7, 5).translate(0, 0.85, 0).rotateZ(0.22).rotateY(a), '#2a2f36')),
    pintar(cilindro(0.025, 0.025, 0.9, 6, 0, 1.95, 0), '#2a2f36'),
    pintar(caixa(0.5, 0.36, 0.14, 0, 2.45, 0), '#22262c'),
  ]), new MeshStandardMaterial({ vertexColors: true, roughness: 0.6, metalness: 0.5 }));
  const lampadaRefletor = new Mesh(new PlaneGeometry(0.42, 0.28), new MeshBasicMaterial({ color: linear('#eef4ff', 0) }));
  lampadaRefletor.position.set(0, 2.45, 0.075);
  refletor.add(tripe, lampadaRefletor);
  refletor.position.set(XE - 1.2, 0.16, Z2 + 2.6);
  refletor.lookAt(XE + 0.85, 1.0, Z2);
  cena.add(refletor);

  // ---------- Cenário 3: equipamentos lado a lado, com a prioridade de revisão ----------
  const XR = VIA.borda + 2.0;
  const LADO_COLUNA = -1.9;
  const pecasRevisao = [];
  for (const item of PRIORIDADES) {
    const g = new Group();
    if (item.tipo === 'cancela') {
      const listras = textura(imagem(256, 16, (c) => {
        for (let i = 0; i < 8; i++) {
          c.fillStyle = i % 2 ? '#f4f4f4' : '#d81f26';
          c.fillRect(i * 32, 0, 32, 16);
        }
      }));
      const poste = new Mesh(extrusao(retangulo(0.42, 1.1, 0.05), 0.36, 0.03).translate(0, 0.55, 0), new MeshStandardMaterial({ color: '#f2b705', roughness: 0.5, metalness: 0.2 }));
      const braco = new Mesh(new BoxGeometry(0.1, 0.1, 3.6).translate(0, 0, -1.8),
        new MeshStandardMaterial({ map: listras, emissiveMap: listras, emissive: '#ffffff', emissiveIntensity: 0.22, roughness: 0.4 }));
      braco.position.set(0, 0.92, -0.1);
      g.add(poste, braco);
    } else if (item.tipo === 'patinete') {
      const patinete = new Mesh(juntar([
        pintar(caixa(0.17, 0.05, 1.0, 0, 0.12, 0), '#1c232c'),
        pintar(cilindro(0.025, 0.025, 1.05, 8).rotateX(-0.22).translate(0, 0.62, -0.5), '#3a4452'),
        pintar(cilindro(0.02, 0.02, 0.52, 8).rotateZ(Math.PI / 2).translate(0, 1.14, -0.62), '#3a4452'),
        ...[-0.5, 0.48].map((z) => pintar(cilindro(0.1, 0.1, 0.05, 14).rotateZ(Math.PI / 2).translate(0, 0.1, z), '#0b0d10')),
      ]), new MeshStandardMaterial({ vertexColors: true, roughness: 0.4, metalness: 0.5 }));
      const farolete = new Mesh(new BoxGeometry(0.06, 0.04, 0.02), matTurquesa);
      farolete.position.set(0, 1.02, -0.58);
      patinete.rotation.z = 0.12;
      g.add(patinete, farolete);
    } else if (item.tipo === 'lombada') {
      g.add(lombada(0.72, false).g);
    } else {
      const camera = new Mesh(juntar([
        pintar(cilindro(0.08, 0.1, 4.6, 12, 0, 2.3, 0), '#5a6472'),
        pintar(cilindro(0.04, 0.04, 0.9, 8).rotateX(Math.PI / 2).translate(0, 4.5, -0.45), '#5a6472'),
        pintar(cilindro(0.2, 0.2, 0.12, 20, 0, 4.42, -0.9), '#e5e8ec'),
        pintar(new SphereGeometry(0.18, 20, 10, 0, Math.PI * 2, Math.PI / 2, Math.PI / 2).translate(0, 4.37, -0.9), new Color(0.02, 0.025, 0.035)),
      ]), new MeshStandardMaterial({ vertexColors: true, roughness: 0.4, metalness: 0.5 }));
      g.add(camera);
    }
    g.traverse((o) => {
      if (o.isMesh) {
        o.castShadow = true;
        o.receiveShadow = true;
      }
    });
    g.position.set(XR, 0.16, item.z);
    cena.add(g);
    // Coluna de prioridade atrás e ao lado da peça (as duas aparecem de frente), base em anel e o número no topo.
    const principal = item === PRIORIDADES[0];
    const cor = principal ? COR.ambar : COR.turquesa;
    const alturaColuna = 0.6 + item.valor * 0.1;
    const coluna = new Mesh(new CylinderGeometry(0.3, 0.3, alturaColuna, 32, 1, true).translate(0, alturaColuna / 2, 0), materialColuna(cor));
    coluna.position.set(XR + 1.2, 0.16, item.z + LADO_COLUNA);
    const base = new Mesh(new TorusGeometry(0.42, 0.025, 6, 48).rotateX(Math.PI / 2), new MeshBasicMaterial({ color: linear(cor, 2.2), transparent: true, blending: AdditiveBlending, depthWrite: false }));
    base.position.set(XR + 1.2, 0.19, item.z + LADO_COLUNA);
    const telaRotulo = document.createElement('canvas');
    telaRotulo.width = 256;
    telaRotulo.height = 128;
    const texRotulo = textura(telaRotulo);
    const desenharRotulo = () => {
      const c = telaRotulo.getContext('2d');
      c.clearRect(0, 0, 256, 128);
      c.fillStyle = '#ffffff';
      c.textBaseline = 'alphabetic';
      c.font = '800 84px Overpass, system-ui, sans-serif';
      const w = c.measureText(String(item.valor)).width;
      c.font = '700 34px Overpass, system-ui, sans-serif';
      const w2 = c.measureText('/100').width;
      const x = (256 - w - w2 - 6) / 2;
      c.font = '800 84px Overpass, system-ui, sans-serif';
      c.fillText(String(item.valor), x, 100);
      c.globalAlpha = 0.72;
      c.font = '700 34px Overpass, system-ui, sans-serif';
      c.fillText('/100', x + w + 6, 100);
      texRotulo.needsUpdate = true;
    };
    desenharRotulo();
    fontePronta.then(desenharRotulo);
    const rotulo = new Sprite(new SpriteMaterial({ map: texRotulo, color: linear(cor, principal ? 2.4 : 1.6), transparent: true, depthWrite: false, fog: true }));
    rotulo.scale.set(1.3, 0.65, 1);
    rotulo.position.set(XR + 1.2, 0.16 + alturaColuna + 0.55, item.z + LADO_COLUNA);
    cena.add(coluna, base, rotulo);
    pecasRevisao.push({ item, coluna, base, rotulo, alturaColuna, principal });
  }
  // Varredura: linha no chão e uma cortina de luz que corre ao longo das peças.
  const varredura = new Group();
  const linha = new Mesh(new PlaneGeometry(4.6, 0.35).rotateX(-Math.PI / 2), new MeshBasicMaterial({ color: linear(COR.turquesa, 3), alphaMap: tex.mancha, transparent: true, blending: AdditiveBlending, depthWrite: false }));
  linha.position.y = 0.2;
  const cortina = new Mesh(new PlaneGeometry(4.6, 4.5).translate(0, 2.25, 0), new MeshBasicMaterial({ color: linear(COR.turquesa, 0.32), alphaMap: tex.rastro, transparent: true, blending: AdditiveBlending, depthWrite: false, side: DoubleSide }));
  varredura.add(linha, cortina);
  varredura.position.set(XR + 0.1, 0, PRIORIDADES[0].z + 6);
  cena.add(varredura);

  // ---------- Câmeras: caminhos ligados à rolagem (p de 0 a 1) ----------
  const caminho = (pontos) => new CatmullRomCurve3(pontos.map((p) => new Vector3(...p)), false, 'centripetal');
  const CAMINHOS = {
    radar: {
      pos: caminho([[-4, 26, 52], [1, 15, 32], [6.5, 6, 18], [10.7, 2.0, 7.2]]),
      alvo: caminho([[5, 0, -8], [7, 1, -4], [8.6, 2.2, -1], [9.1, 2.95, -0.4]]),
    },
    manutencao: {
      pos: caminho([[8, 14, Z2 + 32], [1.5, 6.8, Z2 + 18], [-4.5, 4.2, Z2 + 11.5], [-4.6, 2.1, Z2 + 8.6], [-4.4, 4.4, Z2 + 6.4], [-3.0, 6.7, Z2 + 5.2]]),
      alvo: caminho([[-7.5, 2.5, Z2], [-8.3, 1.8, Z2], [-8.6, 1.5, Z2 - 0.1], [-8.7, 1.4, Z2 - 0.2], [-7.4, 3.6, Z2], [-5.3, 5.75, Z2]]),
    },
    revisoes: {
      // De frente para a calçada, do canteiro e acima dos carros: um travelling que passa por cada equipamento, da maior
      // prioridade para a menor.
      pos: caminho([[0.6, 4.6, -282], [0.8, 4.2, -289.5], [0.8, 4.2, -297], [0.8, 4.2, -304.5], [0.8, 4.2, -312]]),
      alvo: caminho([[10.8, 2.1, -288], [10.8, 2.3, -289.5], [10.8, 2.3, -297], [10.8, 2.3, -304.5], [10.8, 2.3, -312]]),
    },
  };
  const posCam = new Vector3();
  const alvoCam = new Vector3();
  const EIXO_Z = new Vector3(0, 0, 1);
  const q = new Quaternion();

  // ---------- Pós-processamento ----------
  const alvoRender = new WebGLRenderTarget(1, 1, { type: HalfFloatType, samples: celular ? 0 : 4 });
  const composer = new EffectComposer(renderer, alvoRender);
  composer.addPass(new RenderPass(cena, camera));
  const bloom = new UnrealBloomPass(new Vector2(256, 256), 0.62, 0.42, 0.92);
  composer.addPass(bloom);
  composer.addPass(new OutputPass());
  const filme = new ShaderPass(FILME);
  composer.addPass(filme);
  if (celular) filme.uniforms.grao.value = 0.035;

  // ---------- Arranjos de luz por plano ----------
  function arranjar(nome) {
    for (const l of [chave, giro, ponto1, ponto2]) l.intensity = 0;
    ponto2.distance = 14;
    fachoGiro.material.uniforms.intensidade.value = 0;
    if (nome === 'radar') {
      chave.color.set('#ffd2a0');
      chave.position.copy(LUZ_RADAR);
      chave.target.position.set(VIA.borda + 1.5, 0, CENARIO.radar);
      chave.angle = 0.85;
      chave.distance = 30;
      chave.intensity = 260;
      ponto1.color.set(COR.ambar);
      ponto1.position.set(VIA.borda + 1.2, 3.0, CENARIO.radar + 1.2);
      ponto2.position.set(VIA.borda + 1.2, 3.55, CENARIO.radar + 0.6);
    } else if (nome === 'manutencao') {
      chave.color.set('#eef4ff');
      chave.position.set(XE - 1.2, 2.6, Z2 + 2.6);
      chave.target.position.set(XE + 0.85, 0.9, Z2);
      chave.angle = 0.5;
      chave.distance = 16;
      giro.position.copy(barra.position);
      ponto1.color.set('#9fffe0');
      ponto1.position.set(XE + 0.85, 0.95, Z2 + 0.75);
      ponto2.position.set(XE + 4.3, 5.3, Z2 + 1.4);
      ponto2.distance = 5;
    } else {
      chave.color.set('#ffd2a0');
      chave.position.copy(lampadaPerto(1, PRIORIDADES[1].z));
      chave.target.position.set(XR, 0, PRIORIDADES[1].z);
      chave.angle = 0.95;
      chave.distance = 30;
      chave.intensity = 220;
      ponto1.color.set(COR.ambar);
      ponto1.position.set(XR + 0.9, 2.2, PRIORIDADES[0].z);
      ponto2.position.set(XR + 0.9, 1.6, PRIORIDADES[2].z);
    }
    chave.target.updateMatrixWorld();
  }

  // ---------- Quadro ----------
  let arranjado = null;
  // Sem pós-processamento: desenho direto no canvas (tone mapping do renderer, sem bloom nem filme). Usado quando a
  // placa de vídeo devolve a imagem intermediária (ponto flutuante, antisserrilhado) preta.
  let direto = false;
  function quadro(nome, { p, t, parado }) {
    if (arranjado !== nome) {
      arranjar(nome);
      arranjado = nome;
    }
    // e = andamento da história. O plano fica inteiro na janela mais ou menos de p = 0,37 a 0,63; a história corre de
    // p = 0,04 a 0,6, então já terminou quando o plano chega ao meio da tela e começa a sair.
    const e = MathUtils.clamp((p - 0.04) / 0.56, 0, 1);
    const k = suave(0, 1, e) * 0.7 + e * 0.3;
    const cam = CAMINHOS[nome];
    cam.pos.getPoint(k, posCam);
    cam.alvo.getPoint(k, alvoCam);
    if (!parado) {
      // Câmera na mão: deriva lenta de poucos centímetros.
      posCam.x += Math.sin(t * 0.61) * 0.05 + Math.sin(t * 1.37) * 0.02;
      posCam.y += Math.sin(t * 0.83 + 1.3) * 0.04;
      alvoCam.x += Math.sin(t * 0.47 + 0.4) * 0.05;
    }
    camera.position.copy(posCam);
    camera.lookAt(alvoCam);
    ceu.position.copy(posCam);
    refletir(posCam);
    // Um leve giro no eixo da lente (dutch) que se desfaz ao chegar.
    q.setFromAxisAngle(EIXO_Z, (1 - e) * (nome === 'manutencao' ? -0.05 : 0.04));
    camera.quaternion.multiply(q);
    posicionarTransito(t);
    let info = null;

    if (nome === 'radar') {
      // Leitura: o painel mostra a velocidade do último veículo das faixas da direita que cruzou os laços. O instante da
      // passagem sai da posição (z = z0 − s − v·t), então não depende da taxa de quadros.
      const dist = VIA.z0 - (CENARIO.radar + 11.5);
      let ultimaLeitura = -99;
      for (const v of veiculos) {
        if (v.faixa.dir > 0) continue;
        const voltas = Math.floor((v.faixa.v * t + v.s - dist) / VIA.comprimento);
        const quando = (dist - v.s + voltas * VIA.comprimento) / v.faixa.v;
        if (quando <= t && quando > ultimaLeitura) {
          ultimaLeitura = quando;
          lomb.ultima = Math.round(v.faixa.v * 3.6) + v.jitter;
          lomb.faixaLida = FAIXAS.indexOf(v.faixa);
        }
      }
      const desde = t - ultimaLeitura;
      const valor = parado ? 38 : desde < 4.5 ? lomb.ultima : null;
      if (valor !== lomb.valor) {
        desenharPainel(lomb.ctx, valor);
        lomb.texPainel.needsUpdate = true;
        lomb.valor = valor;
      }
      lacos.forEach((l, i) => {
        l.material.opacity = !parado && lomb.faixaLida === i ? Math.max(0, 1 - desde / 0.9) : 0;
      });
      ondas.forEach((o, i) => {
        const fase = parado ? 0.35 + i * 0.22 : ((t * 0.8 + i / 3) % 1);
        o.scale.setScalar(0.8 + fase * 2.2);
        o.material.opacity = Math.sin(Math.PI * Math.min(fase * 1.3, 1)) * 0.75;
      });
      const acima = valor != null && valor > LIMITE;
      ponto1.color.set(acima ? '#ff3a24' : COR.ambar);
      ponto1.intensity = valor == null ? 1.2 : 5;
      ponto2.intensity = 2.2;
    } else if (nome === 'manutencao') {
      // Da falha à conclusão, conforme a rolagem: porta, refletor, giroflex e o anel de situação.
      const execucao = suave(0.24, 0.34, e) * (1 - suave(0.66, 0.76, e));
      const fase = e < 0.28 ? 'falha' : e < 0.71 ? 'execucao' : 'concluida';
      dobradica.rotation.y = -1.9 * execucao;
      miolo.visible = execucao > 0.02;
      chave.intensity = 55 * execucao;
      tingir(lampadaRefletor.material.color, '#eef4ff', 4 * execucao);
      const pisca = parado ? 1 : 0.55 + 0.45 * Math.sign(Math.sin(t * 7));
      if (fase === 'falha') tingir(matSituacao.color, '#ff3a24', 2.4 * pisca);
      else if (fase === 'execucao') tingir(matSituacao.color, COR.ambar, 2.4);
      else tingir(matSituacao.color, COR.turquesa, 1.8);
      ponto2.color.copy(matSituacao.color).multiplyScalar(0.3);
      ponto2.intensity = 1.2;
      ponto1.intensity = 0.2 * execucao;
      const angulo = parado ? 3.7 : t * 4.2; // parado, o facho aponta para longe da lente
      giro.intensity = 160 * execucao;
      giro.target.position.set(barra.position.x + Math.cos(angulo) * 8, 0.2, barra.position.z + Math.sin(angulo) * 8);
      giro.target.updateMatrixWorld();
      fachoGiro.rotation.set(0, -angulo, 0);
      fachoGiro.material.uniforms.intensidade.value = 0.22 * execucao;
      tingir(matBarra.color, COR.ambar, 0.6 + 4 * execucao * (0.6 + 0.4 * Math.abs(Math.sin(angulo * 2))));
      info = { fase };
    } else {
      // Revisões: a varredura passa pelas peças e as colunas sobem até a prioridade de cada uma.
      const zv = MathUtils.lerp(PRIORIDADES[0].z + 3, PRIORIDADES[3].z - 3, MathUtils.clamp(e / 0.8, 0, 1));
      varredura.position.z = zv;
      const fimVarredura = suave(0.82, 0.92, e);
      linha.material.opacity = 1 - fimVarredura;
      cortina.material.opacity = 1 - fimVarredura;
      for (const peca of pecasRevisao) {
        const cresce = suave(peca.item.z + 1.5, peca.item.z - 1.5, zv - LADO_COLUNA);
        peca.coluna.material.uniforms.altura.value = Math.max(0.001, cresce);
        peca.coluna.material.uniforms.tempo.value = parado ? 0 : t;
        peca.coluna.material.uniforms.brilho.value = peca.principal ? 1 + suave(0.1, 0.3, e) * 0.8 : 0.85;
        peca.rotulo.position.y = 0.16 + peca.alturaColuna * cresce + 0.55;
        peca.rotulo.material.opacity = suave(0.6, 1, cresce);
        peca.base.material.opacity = 0.35 + cresce * 0.65;
      }
      ponto1.intensity = 6 * suave(0.05, 0.25, e);
      ponto2.intensity = 1.5;
      // A luz-chave acompanha o travelling: cada equipamento fica iluminado quando passa na frente da câmera.
      chave.position.set(XR - 4, 6.5, posCam.z + 1.5);
      chave.target.position.set(XR, 0.5, posCam.z - 0.5);
      chave.target.updateMatrixWorld();
    }

    filme.uniforms.tempo.value = parado ? 0 : t;
    if (direto) renderer.render(cena, camera);
    else composer.render();
    return info;
  }

  return {
    canvas,
    /** Compila os shaders sem travar a página (KHR_parallel_shader_compile quando existe). */
    preparar: () => {
      arranjar('radar');
      posicionarTransito(0);
      refletir(camera.position);
      return renderer.compileAsync(cena, camera).catch(() => {});
    },
    /** Tamanho em px CSS e densidade de pixels (o bloom trabalha em meia resolução). */
    tamanho(w, h, densidade) {
      const largura = Math.max(1, Math.round(w));
      const altura = Math.max(1, Math.round(h));
      renderer.setPixelRatio(densidade);
      renderer.setSize(largura, altura, false);
      composer.setPixelRatio(densidade);
      composer.setSize(largura, altura);
      bloom.setSize(Math.round(largura * densidade / 2), Math.round(altura * densidade / 2));
      const aspecto = largura / altura;
      camera.aspect = aspecto;
      // Mantém o campo horizontal do formato 2,39:1 em telas mais estreitas (o celular vê mais acima e abaixo).
      const horizontal = 2 * Math.atan(Math.tan(MathUtils.degToRad(30) / 2) * 2.39);
      camera.fov = MathUtils.radToDeg(2 * Math.atan(Math.tan(horizontal / 2) / Math.max(aspecto, 1)));
      camera.updateProjectionMatrix();
      filme.uniforms.aspecto.value = aspecto;
    },
    quadro,
    simplificar() {
      direto = true;
    },
  };
}
