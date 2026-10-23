import{$ as e,A as t,B as n,C as r,D as i,F as a,H as o,J as s,K as c,L as l,M as u,P as d,Q as f,S as p,T as ee,U as m,V as te,W as h,Z as ne,a as g,at as _,b as re,c as ie,d as v,et as ae,f as y,g as oe,h as se,it as b,k as x,l as ce,m as le,n as ue,nt as de,o as fe,ot as pe,p as me,q as S,r as he,rt as C,s as w,tt as T,v as E,w as D,x as ge,y as _e,z as O}from"./three.module-BROWA2qQ.js";function ve(e,t=!1){let n=e[0].index!==null,r=new Set(Object.keys(e[0].attributes)),i=new Set(Object.keys(e[0].morphAttributes)),a={},o={},s=e[0].morphTargetsRelative,c=new w,l=0;for(let u=0;u<e.length;++u){let d=e[u],f=0;if(n!==(d.index!==null))return console.error(`THREE.BufferGeometryUtils: .mergeGeometries() failed with geometry at index `+u+`. All geometries must have compatible attributes; make sure index attribute exists among all geometries, or in none of them.`),null;for(let e in d.attributes){if(!r.has(e))return console.error(`THREE.BufferGeometryUtils: .mergeGeometries() failed with geometry at index `+u+`. All geometries must have compatible attributes; make sure "`+e+`" attribute exists among all geometries, or in none of them.`),null;a[e]===void 0&&(a[e]=[]),a[e].push(d.attributes[e]),f++}if(f!==r.size)return console.error(`THREE.BufferGeometryUtils: .mergeGeometries() failed with geometry at index `+u+`. Make sure all geometries have the same number of attributes.`),null;if(s!==d.morphTargetsRelative)return console.error(`THREE.BufferGeometryUtils: .mergeGeometries() failed with geometry at index `+u+`. .morphTargetsRelative must be consistent throughout all geometries.`),null;for(let e in d.morphAttributes){if(!i.has(e))return console.error(`THREE.BufferGeometryUtils: .mergeGeometries() failed with geometry at index `+u+`.  .morphAttributes must be consistent throughout all geometries.`),null;o[e]===void 0&&(o[e]=[]),o[e].push(d.morphAttributes[e])}if(t){let e;if(n)e=d.index.count;else if(d.attributes.position!==void 0)e=d.attributes.position.count;else return console.error(`THREE.BufferGeometryUtils: .mergeGeometries() failed with geometry at index `+u+`. The geometry must have either an index or a position attribute`),null;c.addGroup(l,e,u),l+=e}}if(n){let t=0,n=[];for(let r=0;r<e.length;++r){let i=e[r].index;for(let e=0;e<i.count;++e)n.push(i.getX(e)+t);t+=e[r].attributes.position.count}c.setIndex(n)}for(let e in a){let t=k(a[e]);if(!t)return console.error(`THREE.BufferGeometryUtils: .mergeGeometries() failed while trying to merge the `+e+` attribute.`),null;c.setAttribute(e,t)}for(let e in o){let t=o[e][0].length;if(t!==0){c.morphAttributes=c.morphAttributes||{},c.morphAttributes[e]=[];for(let n=0;n<t;++n){let t=[];for(let r=0;r<o[e].length;++r)t.push(o[e][r][n]);let r=k(t);if(!r)return console.error(`THREE.BufferGeometryUtils: .mergeGeometries() failed while trying to merge the `+e+` morphAttribute.`),null;c.morphAttributes[e].push(r)}}}return c}function k(e){let t,n,r,i=-1,a=0;for(let o=0;o<e.length;++o){let s=e[o];if(t===void 0&&(t=s.array.constructor),t!==s.array.constructor)return console.error(`THREE.BufferGeometryUtils: .mergeAttributes() failed. BufferAttribute.array must be of consistent array types across matching attributes.`),null;if(n===void 0&&(n=s.itemSize),n!==s.itemSize)return console.error(`THREE.BufferGeometryUtils: .mergeAttributes() failed. BufferAttribute.itemSize must be consistent across matching attributes.`),null;if(r===void 0&&(r=s.normalized),r!==s.normalized)return console.error(`THREE.BufferGeometryUtils: .mergeAttributes() failed. BufferAttribute.normalized must be consistent across matching attributes.`),null;if(i===-1&&(i=s.gpuType),i!==s.gpuType)return console.error(`THREE.BufferGeometryUtils: .mergeAttributes() failed. BufferAttribute.gpuType must be consistent across matching attributes.`),null;a+=s.count*n}let o=new t(a),s=new fe(o,n,r),c=0;for(let t=0;t<e.length;++t){let r=e[t];if(r.isInterleavedBufferAttribute){let e=c/n;for(let t=0,i=r.count;t<i;t++)for(let i=0;i<n;i++){let n=r.getComponent(t,i);s.setComponent(t+e,i,n)}}else o.set(r.array,c);c+=r.count*n}return i!==void 0&&(s.gpuType=i),s}var ye={name:`CopyShader`,uniforms:{tDiffuse:{value:null},opacity:{value:1}},vertexShader:`

		varying vec2 vUv;

		void main() {

			vUv = uv;
			gl_Position = projectionMatrix * modelViewMatrix * vec4( position, 1.0 );

		}`,fragmentShader:`

		uniform float opacity;

		uniform sampler2D tDiffuse;

		varying vec2 vUv;

		void main() {

			vec4 texel = texture2D( tDiffuse, vUv );
			gl_FragColor = opacity * texel;


		}`},A=class{constructor(){this.isPass=!0,this.enabled=!0,this.needsSwap=!0,this.clear=!1,this.renderToScreen=!1}setSize(){}render(){console.error(`THREE.Pass: .render() must be implemented in derived pass.`)}dispose(){}},j=new a(-1,1,1,-1,0,1),be=new class extends w{constructor(){super(),this.setAttribute(`position`,new E([-1,3,0,-1,-1,0,3,-1,0],3)),this.setAttribute(`uv`,new E([0,2,0,0,2,0],2))}},M=class{constructor(e){this._mesh=new x(be,e)}dispose(){this._mesh.geometry.dispose()}render(e){e.render(this._mesh,j)}get material(){return this._mesh.material}set material(e){this._mesh.material=e}},xe=class extends A{constructor(e,t=`tDiffuse`){super(),this.textureID=t,this.uniforms=null,this.material=null,e instanceof S?(this.uniforms=e.uniforms,this.material=e):e&&(this.uniforms=C.clone(e.uniforms),this.material=new S({name:e.name===void 0?`unspecified`:e.name,defines:Object.assign({},e.defines),uniforms:this.uniforms,vertexShader:e.vertexShader,fragmentShader:e.fragmentShader})),this._fsQuad=new M(this.material)}render(e,t,n){this.uniforms[this.textureID]&&(this.uniforms[this.textureID].value=n.texture),this._fsQuad.material=this.material,this.renderToScreen?(e.setRenderTarget(null),this._fsQuad.render(e)):(e.setRenderTarget(t),this.clear&&e.clear(e.autoClearColor,e.autoClearDepth,e.autoClearStencil),this._fsQuad.render(e))}dispose(){this.material.dispose(),this._fsQuad.dispose()}},Se=class extends A{constructor(e,t){super(),this.scene=e,this.camera=t,this.clear=!0,this.needsSwap=!1,this.inverse=!1}render(e,t,n){let r=e.getContext(),i=e.state;i.buffers.color.setMask(!1),i.buffers.depth.setMask(!1),i.buffers.color.setLocked(!0),i.buffers.depth.setLocked(!0);let a,o;this.inverse?(a=0,o=1):(a=1,o=0),i.buffers.stencil.setTest(!0),i.buffers.stencil.setOp(r.REPLACE,r.REPLACE,r.REPLACE),i.buffers.stencil.setFunc(r.ALWAYS,a,4294967295),i.buffers.stencil.setClear(o),i.buffers.stencil.setLocked(!0),e.setRenderTarget(n),this.clear&&e.clear(),e.render(this.scene,this.camera),e.setRenderTarget(t),this.clear&&e.clear(),e.render(this.scene,this.camera),i.buffers.color.setLocked(!1),i.buffers.depth.setLocked(!1),i.buffers.color.setMask(!0),i.buffers.depth.setMask(!0),i.buffers.stencil.setLocked(!1),i.buffers.stencil.setFunc(r.EQUAL,1,4294967295),i.buffers.stencil.setOp(r.KEEP,r.KEEP,r.KEEP),i.buffers.stencil.setLocked(!0)}},Ce=class extends A{constructor(){super(),this.needsSwap=!1}render(e){e.state.buffers.stencil.setLocked(!1),e.state.buffers.stencil.setTest(!1)}},we=class{constructor(e,t){if(this.renderer=e,this._pixelRatio=e.getPixelRatio(),t===void 0){let n=e.getSize(new b);this._width=n.width,this._height=n.height,t=new pe(this._width*this._pixelRatio,this._height*this._pixelRatio,{type:ge}),t.texture.name=`EffectComposer.rt1`}else this._width=t.width,this._height=t.height;this.renderTarget1=t,this.renderTarget2=t.clone(),this.renderTarget2.texture.name=`EffectComposer.rt2`,this.writeBuffer=this.renderTarget1,this.readBuffer=this.renderTarget2,this.renderToScreen=!0,this.passes=[],this.copyPass=new xe(ye),this.copyPass.material.blending=0,this.timer=new T}swapBuffers(){let e=this.readBuffer;this.readBuffer=this.writeBuffer,this.writeBuffer=e}addPass(e){this.passes.push(e),e.setSize(this._width*this._pixelRatio,this._height*this._pixelRatio)}insertPass(e,t){this.passes.splice(t,0,e),e.setSize(this._width*this._pixelRatio,this._height*this._pixelRatio)}removePass(e){let t=this.passes.indexOf(e);t!==-1&&this.passes.splice(t,1)}isLastEnabledPass(e){for(let t=e+1;t<this.passes.length;t++)if(this.passes[t].enabled)return!1;return!0}render(e){this.timer.update(),e===void 0&&(e=this.timer.getDelta());let t=this.renderer.getRenderTarget(),n=!1;for(let t=0,r=this.passes.length;t<r;t++){let r=this.passes[t];if(r.enabled!==!1){if(r.renderToScreen=this.renderToScreen&&this.isLastEnabledPass(t),r.render(this.renderer,this.writeBuffer,this.readBuffer,e,n),r.needsSwap){if(n){let t=this.renderer.getContext(),n=this.renderer.state.buffers.stencil;n.setFunc(t.NOTEQUAL,1,4294967295),this.copyPass.render(this.renderer,this.writeBuffer,this.readBuffer,e),n.setFunc(t.EQUAL,1,4294967295)}this.swapBuffers()}Se!==void 0&&(r instanceof Se?n=!0:r instanceof Ce&&(n=!1))}}this.renderer.setRenderTarget(t)}reset(e){if(e===void 0){let t=this.renderer.getSize(new b);this._pixelRatio=this.renderer.getPixelRatio(),this._width=t.width,this._height=t.height,e=this.renderTarget1.clone(),e.setSize(this._width*this._pixelRatio,this._height*this._pixelRatio)}this.renderTarget1.dispose(),this.renderTarget2.dispose(),this.renderTarget1=e,this.renderTarget2=e.clone(),this.writeBuffer=this.renderTarget1,this.readBuffer=this.renderTarget2}setSize(e,t){this._width=e,this._height=t;let n=this._width*this._pixelRatio,r=this._height*this._pixelRatio;this.renderTarget1.setSize(n,r),this.renderTarget2.setSize(n,r);for(let e=0;e<this.passes.length;e++)this.passes[e].setSize(n,r)}setPixelRatio(e){this._pixelRatio=e,this.setSize(this._width,this._height)}dispose(){this.renderTarget1.dispose(),this.renderTarget2.dispose(),this.copyPass.dispose()}},Te=class extends A{constructor(e,t,n=null,r=null,i=null){super(),this.scene=e,this.camera=t,this.overrideMaterial=n,this.clearColor=r,this.clearAlpha=i,this.clear=!0,this.clearDepth=!1,this.needsSwap=!1,this.isRenderPass=!0,this._oldClearColor=new v}render(e,t,n){let r=e.autoClear;e.autoClear=!1;let i,a;this.overrideMaterial!==null&&(a=this.scene.overrideMaterial,this.scene.overrideMaterial=this.overrideMaterial),this.clearColor!==null&&(e.getClearColor(this._oldClearColor),e.setClearColor(this.clearColor,e.getClearAlpha())),this.clearAlpha!==null&&(i=e.getClearAlpha(),e.setClearAlpha(this.clearAlpha)),this.clearDepth==1&&e.clearDepth(),e.setRenderTarget(this.renderToScreen?null:n),this.clear===!0&&e.clear(e.autoClearColor,e.autoClearDepth,e.autoClearStencil),e.render(this.scene,this.camera),this.clearColor!==null&&e.setClearColor(this._oldClearColor),this.clearAlpha!==null&&e.setClearAlpha(i),this.overrideMaterial!==null&&(this.scene.overrideMaterial=a),e.autoClear=r}},Ee={name:`LuminosityHighPassShader`,uniforms:{tDiffuse:{value:null},luminosityThreshold:{value:1},smoothWidth:{value:1},defaultColor:{value:new v(0)},defaultOpacity:{value:0}},vertexShader:`

		varying vec2 vUv;

		void main() {

			vUv = uv;

			gl_Position = projectionMatrix * modelViewMatrix * vec4( position, 1.0 );

		}`,fragmentShader:`

		uniform sampler2D tDiffuse;
		uniform vec3 defaultColor;
		uniform float defaultOpacity;
		uniform float luminosityThreshold;
		uniform float smoothWidth;

		varying vec2 vUv;

		void main() {

			vec4 texel = texture2D( tDiffuse, vUv );

			float v = luminance( texel.xyz );

			vec4 outputColor = vec4( defaultColor.rgb, defaultOpacity );

			float alpha = smoothstep( luminosityThreshold, luminosityThreshold + smoothWidth, v );

			gl_FragColor = mix( outputColor, texel, alpha );

		}`},De=class e extends A{constructor(e,n=1,r,i){super(),this.strength=n,this.radius=r,this.threshold=i,this.resolution=e===void 0?new b(256,256):new b(e.x,e.y),this.clearColor=new v(0,0,0),this.needsSwap=!1,this.renderTargetsHorizontal=[],this.renderTargetsVertical=[],this.nMips=5;let a=Math.round(this.resolution.x/2),o=Math.round(this.resolution.y/2);this.renderTargetBright=new pe(a,o,{type:ge,depthBuffer:!1}),this.renderTargetBright.texture.name=`UnrealBloomPass.bright`,this.renderTargetBright.texture.generateMipmaps=!1;for(let e=0;e<this.nMips;e++){let t=new pe(a,o,{type:ge,depthBuffer:!1});t.texture.name=`UnrealBloomPass.h`+e,t.texture.generateMipmaps=!1,this.renderTargetsHorizontal.push(t);let n=new pe(a,o,{type:ge,depthBuffer:!1});n.texture.name=`UnrealBloomPass.v`+e,n.texture.generateMipmaps=!1,this.renderTargetsVertical.push(n),a=Math.round(a/2),o=Math.round(o/2)}let s=Ee;this.highPassUniforms=C.clone(s.uniforms),this.highPassUniforms.luminosityThreshold.value=i,this.highPassUniforms.smoothWidth.value=.01,this.materialHighPassFilter=new S({uniforms:this.highPassUniforms,vertexShader:s.vertexShader,fragmentShader:s.fragmentShader}),this.separableBlurMaterials=[];let c=[6,10,14,18,22];a=Math.round(this.resolution.x/2),o=Math.round(this.resolution.y/2);for(let e=0;e<this.nMips;e++)this.separableBlurMaterials.push(this._getSeparableBlurMaterial(c[e])),this.separableBlurMaterials[e].uniforms.invSize.value=new b(1/a,1/o),a=Math.round(a/2),o=Math.round(o/2);this.compositeMaterial=this._getCompositeMaterial(this.nMips),this.compositeMaterial.uniforms.blurTexture1.value=this.renderTargetsVertical[0].texture,this.compositeMaterial.uniforms.blurTexture2.value=this.renderTargetsVertical[1].texture,this.compositeMaterial.uniforms.blurTexture3.value=this.renderTargetsVertical[2].texture,this.compositeMaterial.uniforms.blurTexture4.value=this.renderTargetsVertical[3].texture,this.compositeMaterial.uniforms.blurTexture5.value=this.renderTargetsVertical[4].texture,this.compositeMaterial.uniforms.bloomStrength.value=n,this.compositeMaterial.uniforms.bloomRadius.value=.1;let l=[1,.8,.6,.4,.2];this.compositeMaterial.uniforms.bloomFactors.value=l,this.bloomTintColors=[new _(1,1,1),new _(1,1,1),new _(1,1,1),new _(1,1,1),new _(1,1,1)],this.compositeMaterial.uniforms.bloomTintColors.value=this.bloomTintColors,this.copyUniforms=C.clone(ye.uniforms),this.blendMaterial=new S({uniforms:this.copyUniforms,vertexShader:ye.vertexShader,fragmentShader:ye.fragmentShader,premultipliedAlpha:!0,blending:2,depthTest:!1,depthWrite:!1,transparent:!0}),this._oldClearColor=new v,this._oldClearAlpha=1,this._basic=new t,this._fsQuad=new M(null)}dispose(){for(let e=0;e<this.renderTargetsHorizontal.length;e++)this.renderTargetsHorizontal[e].dispose();for(let e=0;e<this.renderTargetsVertical.length;e++)this.renderTargetsVertical[e].dispose();this.renderTargetBright.dispose();for(let e=0;e<this.separableBlurMaterials.length;e++)this.separableBlurMaterials[e].dispose();this.compositeMaterial.dispose(),this.blendMaterial.dispose(),this._basic.dispose(),this._fsQuad.dispose()}setSize(e,t){let n=Math.round(e/2),r=Math.round(t/2);this.renderTargetBright.setSize(n,r);for(let e=0;e<this.nMips;e++)this.renderTargetsHorizontal[e].setSize(n,r),this.renderTargetsVertical[e].setSize(n,r),this.separableBlurMaterials[e].uniforms.invSize.value=new b(1/n,1/r),n=Math.round(n/2),r=Math.round(r/2)}render(t,n,r,i,a){t.getClearColor(this._oldClearColor),this._oldClearAlpha=t.getClearAlpha();let o=t.autoClear;t.autoClear=!1,t.setClearColor(this.clearColor,0),a&&t.state.buffers.stencil.setTest(!1),this.renderToScreen&&(this._fsQuad.material=this._basic,this._basic.map=r.texture,t.setRenderTarget(null),t.clear(),this._fsQuad.render(t)),this.highPassUniforms.tDiffuse.value=r.texture,this.highPassUniforms.luminosityThreshold.value=this.threshold,this._fsQuad.material=this.materialHighPassFilter,t.setRenderTarget(this.renderTargetBright),t.clear(),this._fsQuad.render(t);let s=this.renderTargetBright;for(let n=0;n<this.nMips;n++)this._fsQuad.material=this.separableBlurMaterials[n],this.separableBlurMaterials[n].uniforms.colorTexture.value=s.texture,this.separableBlurMaterials[n].uniforms.direction.value=e.BlurDirectionX,t.setRenderTarget(this.renderTargetsHorizontal[n]),t.clear(),this._fsQuad.render(t),this.separableBlurMaterials[n].uniforms.colorTexture.value=this.renderTargetsHorizontal[n].texture,this.separableBlurMaterials[n].uniforms.direction.value=e.BlurDirectionY,t.setRenderTarget(this.renderTargetsVertical[n]),t.clear(),this._fsQuad.render(t),s=this.renderTargetsVertical[n];this._fsQuad.material=this.compositeMaterial,this.compositeMaterial.uniforms.bloomStrength.value=this.strength,this.compositeMaterial.uniforms.bloomRadius.value=this.radius,this.compositeMaterial.uniforms.bloomTintColors.value=this.bloomTintColors,t.setRenderTarget(this.renderTargetsHorizontal[0]),t.clear(),this._fsQuad.render(t),this._fsQuad.material=this.blendMaterial,this.copyUniforms.tDiffuse.value=this.renderTargetsHorizontal[0].texture,a&&t.state.buffers.stencil.setTest(!0),this.renderToScreen?(t.setRenderTarget(null),this._fsQuad.render(t)):(t.setRenderTarget(r),this._fsQuad.render(t)),t.setClearColor(this._oldClearColor,this._oldClearAlpha),t.autoClear=o}_getSeparableBlurMaterial(e){let t=[],n=e/3;for(let r=0;r<e;r++)t.push(.39894*Math.exp(-.5*r*r/(n*n))/n);let r=[],i=[];for(let n=1;n<e;n+=2){let a=t[n],o=n+1<e?t[n+1]:0,s=a+o;r.push((n*a+(n+1)*o)/s),i.push(s)}return new S({defines:{KERNEL_PAIRS:r.length},uniforms:{colorTexture:{value:null},invSize:{value:new b(.5,.5)},direction:{value:new b(.5,.5)},centerWeight:{value:t[0]},gaussianOffsets:{value:r},gaussianWeights:{value:i}},vertexShader:`

				varying vec2 vUv;

				void main() {

					vUv = uv;
					gl_Position = projectionMatrix * modelViewMatrix * vec4( position, 1.0 );

				}`,fragmentShader:`

				#include <common>

				varying vec2 vUv;

				uniform sampler2D colorTexture;
				uniform vec2 invSize;
				uniform vec2 direction;
				uniform float centerWeight;
				uniform float gaussianOffsets[KERNEL_PAIRS];
				uniform float gaussianWeights[KERNEL_PAIRS];

				void main() {

					vec3 diffuseSum = texture2D( colorTexture, vUv ).rgb * centerWeight;

					for ( int i = 0; i < KERNEL_PAIRS; i ++ ) {

						vec2 uvOffset = direction * invSize * gaussianOffsets[ i ];
						vec3 sample1 = texture2D( colorTexture, vUv + uvOffset ).rgb;
						vec3 sample2 = texture2D( colorTexture, vUv - uvOffset ).rgb;
						diffuseSum += ( sample1 + sample2 ) * gaussianWeights[ i ];

					}

					gl_FragColor = vec4( diffuseSum, 1.0 );

				}`})}_getCompositeMaterial(e){return new S({defines:{NUM_MIPS:e},uniforms:{blurTexture1:{value:null},blurTexture2:{value:null},blurTexture3:{value:null},blurTexture4:{value:null},blurTexture5:{value:null},bloomStrength:{value:1},bloomFactors:{value:null},bloomTintColors:{value:null},bloomRadius:{value:0}},vertexShader:`

				varying vec2 vUv;

				void main() {

					vUv = uv;
					gl_Position = projectionMatrix * modelViewMatrix * vec4( position, 1.0 );

				}`,fragmentShader:`

				varying vec2 vUv;

				uniform sampler2D blurTexture1;
				uniform sampler2D blurTexture2;
				uniform sampler2D blurTexture3;
				uniform sampler2D blurTexture4;
				uniform sampler2D blurTexture5;
				uniform float bloomStrength;
				uniform float bloomRadius;
				uniform float bloomFactors[NUM_MIPS];
				uniform vec3 bloomTintColors[NUM_MIPS];

				float lerpBloomFactor( const in float factor ) {

					float mirrorFactor = 1.2 - factor;
					return mix( factor, mirrorFactor, bloomRadius );

				}

				void main() {

					// 3.0 for backwards compatibility with previous alpha-based intensity
					vec3 bloom = 3.0 * bloomStrength * (
						lerpBloomFactor( bloomFactors[ 0 ] ) * bloomTintColors[ 0 ] * texture2D( blurTexture1, vUv ).rgb +
						lerpBloomFactor( bloomFactors[ 1 ] ) * bloomTintColors[ 1 ] * texture2D( blurTexture2, vUv ).rgb +
						lerpBloomFactor( bloomFactors[ 2 ] ) * bloomTintColors[ 2 ] * texture2D( blurTexture3, vUv ).rgb +
						lerpBloomFactor( bloomFactors[ 3 ] ) * bloomTintColors[ 3 ] * texture2D( blurTexture4, vUv ).rgb +
						lerpBloomFactor( bloomFactors[ 4 ] ) * bloomTintColors[ 4 ] * texture2D( blurTexture5, vUv ).rgb
					);

					float bloomAlpha = max( bloom.r, max( bloom.g, bloom.b ) );
					gl_FragColor = vec4( bloom, bloomAlpha );

				}`})}};De.BlurDirectionX=new b(1,0),De.BlurDirectionY=new b(0,1);var Oe={name:`OutputShader`,uniforms:{tDiffuse:{value:null},toneMappingExposure:{value:1}},vertexShader:`
		precision highp float;

		uniform mat4 modelViewMatrix;
		uniform mat4 projectionMatrix;

		attribute vec3 position;
		attribute vec2 uv;

		varying vec2 vUv;

		void main() {

			vUv = uv;
			gl_Position = projectionMatrix * modelViewMatrix * vec4( position, 1.0 );

		}`,fragmentShader:`

		precision highp float;

		uniform sampler2D tDiffuse;

		#include <tonemapping_pars_fragment>
		#include <colorspace_pars_fragment>

		varying vec2 vUv;

		void main() {

			gl_FragColor = texture2D( tDiffuse, vUv );

			// tone mapping

			#ifdef LINEAR_TONE_MAPPING

				gl_FragColor.rgb = LinearToneMapping( gl_FragColor.rgb );

			#elif defined( REINHARD_TONE_MAPPING )

				gl_FragColor.rgb = ReinhardToneMapping( gl_FragColor.rgb );

			#elif defined( CINEON_TONE_MAPPING )

				gl_FragColor.rgb = CineonToneMapping( gl_FragColor.rgb );

			#elif defined( ACES_FILMIC_TONE_MAPPING )

				gl_FragColor.rgb = ACESFilmicToneMapping( gl_FragColor.rgb );

			#elif defined( AGX_TONE_MAPPING )

				gl_FragColor.rgb = AgXToneMapping( gl_FragColor.rgb );

			#elif defined( NEUTRAL_TONE_MAPPING )

				gl_FragColor.rgb = NeutralToneMapping( gl_FragColor.rgb );

			#elif defined( CUSTOM_TONE_MAPPING )

				gl_FragColor.rgb = CustomToneMapping( gl_FragColor.rgb );

			#endif

			// color space

			#ifdef SRGB_TRANSFER

				gl_FragColor = sRGBTransferOETF( gl_FragColor );

			#endif

		}`},ke=class extends A{constructor(){super(),this.isOutputPass=!0,this.uniforms=C.clone(Oe.uniforms),this.material=new o({name:Oe.name,uniforms:this.uniforms,vertexShader:Oe.vertexShader,fragmentShader:Oe.fragmentShader}),this._fsQuad=new M(this.material),this._outputColorSpace=null,this._toneMapping=null}render(e,t,n){this.uniforms.tDiffuse.value=n.texture,this.uniforms.toneMappingExposure.value=e.toneMappingExposure,(this._outputColorSpace!==e.outputColorSpace||this._toneMapping!==e.toneMapping)&&(this._outputColorSpace=e.outputColorSpace,this._toneMapping=e.toneMapping,this.material.defines={},y.getTransfer(this._outputColorSpace)===`srgb`&&(this.material.defines.SRGB_TRANSFER=``),this._toneMapping===1?this.material.defines.LINEAR_TONE_MAPPING=``:this._toneMapping===2?this.material.defines.REINHARD_TONE_MAPPING=``:this._toneMapping===3?this.material.defines.CINEON_TONE_MAPPING=``:this._toneMapping===4?this.material.defines.ACES_FILMIC_TONE_MAPPING=``:this._toneMapping===6?this.material.defines.AGX_TONE_MAPPING=``:this._toneMapping===7?this.material.defines.NEUTRAL_TONE_MAPPING=``:this._toneMapping===5&&(this.material.defines.CUSTOM_TONE_MAPPING=``),this.material.needsUpdate=!0),this.renderToScreen===!0?(e.setRenderTarget(null),this._fsQuad.render(e)):(e.setRenderTarget(t),this.clear&&e.clear(e.autoClearColor,e.autoClearDepth,e.autoClearStencil),this._fsQuad.render(e))}dispose(){this.material.dispose(),this._fsQuad.dispose()}},N={turquesa:`#7cf5e1`,ambar:`#ffb21a`,marca:`#2152e8`},P={canteiro:1,borda:8,calcada:12.2,z0:90,comprimento:520},Ae=[{x:2.75,dir:-1,v:11.2},{x:6.25,dir:-1,v:13.1},{x:-2.75,dir:1,v:12.4},{x:-6.25,dir:1,v:10.6}],F={radar:0,manutencao:-150,revisoes:-300},je=40,Me={radar:.52,manutencao:.32,revisoes:.14},I=[{tipo:`cancela`,z:-289,valor:32},{tipo:`patinete`,z:-296.5,valor:27},{tipo:`lombada`,z:-304,valor:18},{tipo:`camera`,z:-311.5,valor:9}];function Ne(e){let t=e>>>0;return()=>{t=t+1831565813|0;let e=Math.imul(t^t>>>15,1|t);return e=e+Math.imul(e^e>>>7,61|e)^e,((e^e>>>14)>>>0)/4294967296}}var L=(e,t,n)=>{let r=i.clamp((n-e)/(t-e),0,1);return r*r*(3-2*r)},R=(e,t=1)=>new v(e).multiplyScalar(t),Pe=(e,t,n)=>e.set(t).multiplyScalar(n);function Fe(e,t,n){let r=document.createElement(`canvas`);return r.width=e,r.height=t,n(r.getContext(`2d`),e,t),r}function Ie(e,t,n){return Fe(e,t,r=>{let i=r.createImageData(e,t);for(let r=0;r<t;r++)for(let a=0;a<e;a++){let o=n(a/(e-1),1-r/(t-1),a,r);i.data.set(o,(r*e+a)*4)}r.putImageData(i,0,0)})}function z(e,{cor:t=!0,repetir:n=null}={}){let r=new ie(e);return t&&(r.colorSpace=h),n&&(r.wrapS=m,r.wrapT=m,r.repeat.set(...n)),r.anisotropy=4,r}function Le(e,t,n){let r=Ne(n),a=new Float32Array(e*e),o=0;for(let[n,s]of t){o+=s;let t=Array.from({length:n*n},r),c=(e,r)=>t[(r+n)%n*n+(e+n)%n];for(let t=0;t<e;t++)for(let r=0;r<e;r++){let o=r/e*n,l=t/e*n,u=Math.floor(o),d=Math.floor(l),f=L(0,1,o-u),p=L(0,1,l-d),ee=i.lerp(c(u,d),c(u+1,d),f),m=i.lerp(c(u,d+1),c(u+1,d+1),f);a[t*e+r]+=i.lerp(ee,m,p)*s}}for(let e=0;e<a.length;e++)a[e]/=o;return a}function B(e,t){let n=e.index?e.toNonIndexed():e,r=t instanceof v?t:new v(t),i=n.attributes.position.count,a=new Float32Array(i*3);for(let e=0;e<i;e++)a.set([r.r,r.g,r.b],e*3);return n.setAttribute(`color`,new fe(a,3)),n.attributes.uv||n.setAttribute(`uv`,new fe(new Float32Array(i*2),2)),n}var V=e=>ve(e.map(e=>e.attributes.color?e:B(e,`#ffffff`))),H=(e,t,n,r=0,i=0,a=0)=>new g(e,t,n).translate(r,i,a),U=(e,t,n,r,i=0,a=0,o=0)=>new me(e,t,n,r).translate(i,a,o);function Re(e){return new s(e.map(([e,t])=>new b(e,t)))}function W(e,t,n=.05,r=6){let i=t-2*n,a=new oe(e,{depth:i,bevelEnabled:n>0,bevelThickness:n,bevelSize:n,bevelOffset:-n,bevelSegments:2,curveSegments:r});return a.translate(0,0,-i/2),a}function ze(e,t,n){let r=new s,i=-e/2,a=-t/2;return r.moveTo(i+n,a),r.lineTo(i+e-n,a),r.absarc(i+e-n,a+n,n,-Math.PI/2,0,!1),r.lineTo(i+e,a+t-n),r.absarc(i+e-n,a+t-n,n,0,Math.PI/2,!1),r.lineTo(i+n,a+t),r.absarc(i+n,a+t-n,n,Math.PI/2,Math.PI,!1),r.lineTo(i,a+n),r.absarc(i+n,a+n,n,Math.PI,Math.PI*1.5,!1),r}function Be(e,t,n){let r=n/2,i=new s;return i.absarc(0,0,e+r,-t,t,!1),i.absarc(e*Math.cos(t),e*Math.sin(t),r,t,t+Math.PI,!1),i.absarc(0,0,e-r,t,-t,!0),i.absarc(e*Math.cos(t),-e*Math.sin(t),r,Math.PI-t,2*Math.PI-t,!1),i}function Ve(e,t,n,r,i){let a=[...e,...t,...n,...e,...n,...r],o=[0,0,1,0,1,1,0,0,1,1,0,1],s=new w;return s.setAttribute(`position`,new fe(new Float32Array(a),3)),s.setAttribute(`uv`,new fe(new Float32Array(o),2)),s.computeVertexNormals(),B(s,i)}function He(){let e=z(Ie(32,128,(e,t)=>{let n=255*(1-t)**1.8*Math.sin(Math.PI*e)**1.4;return[n,n,n,255]}),{cor:!1}),t=z(Ie(128,128,(e,t)=>{let n=Math.hypot(e-.5,t-.5)*2,r=255*Math.exp(-n*n*3.2)*(1-L(.8,1,n));return[r,r,r,255]}),{cor:!1}),n=Le(128,[[4,.5],[16,.3],[64,.2]],7),r=Le(128,[[3,.7],[9,.3]],11),a=Ne(5),o=z(Ie(128,128,(e,t,r,i)=>{let o=.78+n[i*128+r]*.36+(a()-.5)*.16;return[26*o,30*o,37*o,255]}),{repetir:[9,60]}),s=z(Ie(128,128,(e,t,n,o)=>{let s=L(.42,.66,r[o*128+n]),c=255*i.lerp(.78,.42,s)+(a()-.5)*16;return[c,c,c,255]}),{cor:!1,repetir:[9,60]}),c=Ne(23);return{rastro:e,mancha:t,asfalto:o,rugosidade:s,janelas:z(Fe(512,512,e=>{e.fillStyle=`#07090d`,e.fillRect(0,0,512,512);let t=[`#ffc98a`,`#ffb070`,`#ffd9a8`,`#d6e4ff`,`#9fb8ff`];for(let n=0;n<16;n++){let r=c()>.18;for(let i=0;i<16;i++){let a=i*32,o=n*32;if(n===15){let n=c()>.55;e.fillStyle=n?t[Math.floor(c()*3)]:`#0c1017`,e.globalAlpha=n?.25+c()*.3:1,e.fillRect(a+1,o+10,30,20),e.globalAlpha=1;continue}let s=r&&c()<.24;e.fillStyle=s?t[Math.floor(c()*t.length)]:`#0b0f16`,e.globalAlpha=s?.2+c()*.55:1,e.fillRect(a+10,o+11,13,14),e.globalAlpha=1}}}),{repetir:[1,1]})}}var Ue={0:[`01110`,`10001`,`10011`,`10101`,`11001`,`10001`,`01110`],1:[`00100`,`01100`,`00100`,`00100`,`00100`,`00100`,`01110`],2:[`01110`,`10001`,`00001`,`00010`,`00100`,`01000`,`11111`],3:[`11110`,`00001`,`00001`,`01110`,`00001`,`00001`,`11110`],4:[`00010`,`00110`,`01010`,`10010`,`11111`,`00010`,`00010`],5:[`11111`,`10000`,`11110`,`00001`,`00001`,`10001`,`01110`],6:[`00110`,`01000`,`10000`,`11110`,`10001`,`10001`,`01110`],7:[`11111`,`00001`,`00010`,`00100`,`01000`,`01000`,`01000`],8:[`01110`,`10001`,`10001`,`01110`,`10001`,`10001`,`01110`],9:[`01110`,`10001`,`10001`,`01111`,`00001`,`00010`,`01100`],"-":[`00000`,`00000`,`00000`,`11111`,`00000`,`00000`,`00000`]};function We(e,t){let n=e.canvas.width,r=e.canvas.height;e.fillStyle=`#030405`,e.fillRect(0,0,n,r);let i=t==null?`--`:String(t).padStart(2,` `),a=t!=null&&t>je;for(let t=0;t<2;t++){let n=Ue[i[t]]??Ue[`-`];for(let r=0;r<7;r++)for(let o=0;o<5;o++){let s=i[t]!==` `&&n[r][o]===`1`;e.fillStyle=s?a?`#ff3a24`:N.ambar:`#1c1206`,e.beginPath(),e.arc(16+t*76+o*13,25+r*13,s?5.2:4.2,0,Math.PI*2),e.fill()}}e.fillStyle=a?`#ff3a24`:N.ambar,e.font=`800 22px Overpass, system-ui, sans-serif`,e.textBaseline=`alphabetic`,e.fillText(`km/h`,184,103)}var Ge=`
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
  }`;function Ke(e,t){return new S({uniforms:C.merge([ue.fog,{cor:{value:R(e)},intensidade:{value:t}}]),vertexShader:`
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
      }`,fragmentShader:`
      uniform vec3 cor;
      uniform float intensidade;
      #include <common>
      #include <fog_pars_fragment>
      varying float vAltura;
      varying vec3 vNormal;
      varying vec3 vVista;
      ${Ge}
      void main() {
        float borda = pow(abs(dot(normalize(vNormal), normalize(vVista))), 1.8);
        float queda = mix(0.06, 1.0, pow(vAltura, 2.2));
        float a = intensidade * borda * queda * (1.0 - neblina());
        gl_FragColor = vec4(cor * a, 1.0);
      }`,transparent:!0,depthWrite:!1,blending:2,side:2,fog:!0})}function qe(e){return new S({uniforms:C.merge([ue.fog,{cor:{value:R(e)},tempo:{value:0},altura:{value:1},brilho:{value:1}}]),vertexShader:`
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
      }`,fragmentShader:`
      uniform vec3 cor;
      uniform float tempo;
      uniform float altura;
      uniform float brilho;
      #include <common>
      #include <fog_pars_fragment>
      varying vec2 vUv;
      varying vec3 vNormal;
      varying vec3 vVista;
      ${Ge}
      void main() {
        if (vUv.y > altura) discard;
        float borda = 1.0 - abs(dot(normalize(vNormal), normalize(vVista)));
        float faixas = 0.55 + 0.45 * smoothstep(0.35, 0.5, abs(fract(vUv.y * 14.0 - tempo * 0.35) - 0.5));
        float topo = smoothstep(altura - 0.035, altura, vUv.y);
        float a = (0.16 + 0.9 * pow(borda, 2.2)) * faixas + topo * 2.2;
        a *= mix(0.55, 1.0, vUv.y) * brilho * (1.0 - neblina());
        gl_FragColor = vec4(cor * a, 1.0);
      }`,transparent:!0,depthWrite:!1,blending:2,side:2,fog:!0})}var Je={uniforms:{tDiffuse:{value:null},tempo:{value:0},grao:{value:.045},aspecto:{value:2.39}},vertexShader:`
    varying vec2 vUv;
    void main() { vUv = uv; gl_Position = projectionMatrix * modelViewMatrix * vec4(position, 1.0); }`,fragmentShader:`
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
    }`};function Ye({celular:a}){let o;try{o=new he({antialias:!1,alpha:!1,powerPreference:`high-performance`,failIfMajorPerformanceCaveat:!0})}catch{return null}o.toneMapping=4,o.toneMappingExposure=1.05,o.shadowMap.enabled=!a,o.shadowMap.type=1,o.setClearColor(`#05080d`,1);let s=o.domElement;s.setAttribute(`aria-hidden`,`true`);let m=He(),h=new c,ie=new v(`#0d1626`);h.fog=new _e(ie,.0125);let y=new l(30,2.39,.1,1400),ue=new x(new ne(1e3,32,16),new S({uniforms:{zenite:{value:R(`#02040a`)},horizonte:{value:ie.clone()},cidade:{value:R(`#3b2c2a`,.7)}},vertexShader:`
      varying vec3 vDir;
      void main() { vDir = normalize(position); vec4 p = projectionMatrix * modelViewMatrix * vec4(position, 1.0); gl_Position = p.xyww; }`,fragmentShader:`
      uniform vec3 zenite;
      uniform vec3 horizonte;
      uniform vec3 cidade;
      varying vec3 vDir;
      void main() {
        float h = vDir.y;
        vec3 c = mix(horizonte, zenite, smoothstep(-0.02, 0.5, h));
        c += cidade * exp(-max(h, 0.0) * 10.0) * (0.45 + 0.55 * max(-vDir.z, 0.0));
        gl_FragColor = vec4(c, 1.0);
      }`,side:1,depthWrite:!1}));ue.renderOrder=-1,h.add(ue);let fe=new p(`#2a4170`,`#07080b`,.6),C=new le(`#7f97cf`,.38);C.position.set(-40,50,-80);let w=new f(`#ffd6a3`,0,40,.75,.55,1.6);w.castShadow=!a,w.shadow.mapSize.set(1024,1024),w.shadow.bias=-4e-4,w.shadow.normalBias=.012,w.shadow.camera.near=1,w.shadow.camera.far=40;let T=new f(N.ambar,0,26,.42,.7,1.4),E=new n(N.ambar,0,14,2),k=new n(N.turquesa,0,14,2);h.add(fe,C,w,w.target,T,T.target,E,k);let ye=new x(new O(120,P.comprimento+200).rotateX(-Math.PI/2).translate(0,0,P.z0-P.comprimento/2),new u({map:m.asfalto,roughnessMap:m.rugosidade,roughness:1,metalness:.08}));ye.receiveShadow=!0,m.asfalto.repeat.set(120/9,(P.comprimento+200)/9),m.rugosidade.repeat.copy(m.asfalto.repeat);let A=P.comprimento+200,j=P.z0-P.comprimento/2,be=new v(`#3a414c`),M=new v(`#8d96a3`),Se=P.calcada-P.borda-.18,Ce=V([B(H(Se,.16,A,P.borda+.18+Se/2,.08,j),be),B(H(Se,.16,A,-P.borda-.18-Se/2,.08,j),be),B(H(.18,.18,A,P.borda+.09,.09,j),M),B(H(.18,.18,A,-P.borda-.09,.09,j),M),B(H(P.canteiro*2-.24,.22,A,0,.11,j),new v(`#1a2a20`)),B(H(.12,.24,A,P.canteiro-.06,.12,j),M),B(H(.12,.24,A,-P.canteiro+.06,.12,j),M)]),Ee=new x(Ce,new u({vertexColors:!0,roughness:.82})),Oe=new x(V([-1,1].map(e=>B(H(60,.14,A,e*(P.calcada+30),.07,j),`#151a20`))),new u({vertexColors:!0,roughness:.95}));Oe.receiveShadow=!0,Ee.receiveShadow=!0;let Me=[],Ie=new v(`#c9cfd8`);for(let e of[1,-1]){Me.push(H(.14,.02,A,e*(P.borda-.35),.006,j));for(let t=P.z0;t>P.z0-P.comprimento;t-=12)Me.push(H(.13,.02,4,e*4.5,.006,t))}for(let e of[1,-1])for(let t=P.canteiro+.55;t<P.borda-.4;t+=.95)Me.push(H(.48,.02,3.4,e*t,.007,F.radar-9));Me.push(H(P.borda-P.canteiro-.3,.02,.4,(P.borda+P.canteiro)/2,.007,F.radar-5.8));let Le=new x(V(Me.map(e=>B(e,Ie))),new u({vertexColors:!0,roughness:.55}));Le.receiveShadow=!0;let Ue=[],Ge=[];for(let e of Ae.slice(0,2)){for(let t of[13.5,9.5])for(let[n,r,i,a]of[[2,.06,0,-.8],[2,.06,0,.8],[.06,1.6,-1,0],[.06,1.6,1,0]])Ge.push(H(n,.02,r,e.x+i,.008,F.radar+t+a));let n=new x(new O(2.6,6.2).rotateX(-Math.PI/2),new t({color:R(N.turquesa,1.4),alphaMap:m.mancha,transparent:!0,opacity:0,blending:2,depthWrite:!1}));n.position.set(e.x,.03,F.radar+11.5),Ue.push(n)}let Ye=new x(V(Ge.map(e=>B(e,`#05070a`))),new t({vertexColors:!0}));h.add(ye,Ee,Oe,Le,Ye,...Ue);let G=Ne(41),Xe=[];for(let e of[1,-1]){let t=P.z0+40;for(;t>P.z0-P.comprimento-120;){let n=10+G()*16,r=10+G()*14,i=G()<.18,a=i?26+G()*22:7+G()*13,o=P.calcada+2+G()*5+(i?8:0),s=new g(r,a,n),c=Math.floor(G()*16)/16,l=s.attributes.uv,u=[[n,a],[n,a],[0,0],[0,0],[r,a],[r,a]];for(let e=0;e<6;e++)for(let t=0;t<4;t++){let n=e*4+t,[r,i]=u[e];r?l.setXY(n,l.getX(n)*r/35.2+c,l.getY(n)*i/51.2):l.setXY(n,.995,.5)}s.translate(e*(o+r/2),a/2,t-n/2),Xe.push(s),t-=n+1+G()*4}}for(let e=0;e<26;e++){let t=14+G()*24,n=18+G()*50,r=new g(t,n,16),i=r.attributes.uv;for(let e=0;e<i.count;e++)i.setXY(e,i.getX(e)*t/35.2,i.getY(e)*n/51.2);r.translate(-160+e*13+G()*6,n/2,P.z0-P.comprimento-140-G()*80),Xe.push(r)}let Ze=new x(ve(Xe),new t({map:m.janelas,color:R(`#ffffff`,.85)}));h.add(Ze);let K=[],Qe=(e,t)=>!(e>0&&(Math.abs(t-F.radar)<12||t<-268&&t>-322))&&!(e<0&&Math.abs(t-F.manutencao)<16);for(let e=P.z0+10;e>P.z0-P.comprimento-40;e-=32)Qe(1,e)&&K.push({x:P.borda+.7,z:e,lado:1}),Qe(-1,e-16)&&K.push({x:-P.borda-.7,z:e-16,lado:-1});K.push({x:P.borda+.7,z:F.radar-12,lado:1},{x:P.borda+.7,z:-300.2,lado:1});let $e=2.6,et=8.6,tt=V([B(U(.09,.14,et,10,0,et/2,0),`#4a525e`),B(U(.05,.05,$e,6).rotateZ(Math.PI/2).translate(-2.6/2,8.5,0),`#4a525e`),B(H(.95,.16,.42,-2.6,8.5,0),`#2b3039`)]),nt=new O(.82,.32).rotateX(Math.PI/2).translate(-2.6,8.41,0),rt=new me(.3,4.2,8.299999999999999,28,1,!0).translate(-2.6,8.299999999999999/2,0),it=new O(14,14).rotateX(-Math.PI/2).translate(-2.6,.025,0),at=new D(tt,new u({vertexColors:!0,roughness:.5,metalness:.6}),K.length),ot=new D(nt,new t({color:R(`#ffe2bd`,9),side:2}),K.length),st=new D(rt,Ke(`#ffd9a8`,.1),K.length),ct=new D(it,new t({color:R(`#ffcf96`,.2),alphaMap:m.mancha,transparent:!0,blending:2,depthWrite:!1}),K.length),lt=(e,t)=>{let n=K.filter(t=>t.lado===e).reduce((e,n)=>Math.abs(n.z-t)<Math.abs(e.z-t)?n:e);return new _(n.x-e*$e,8.299999999999999,n.z)},q=new d;K.forEach((e,t)=>{q.position.set(e.x,0,e.z),q.rotation.set(0,e.lado>0?0:Math.PI,0),q.updateMatrix();for(let e of[at,ot,st,ct])e.setMatrixAt(t,q.matrix)}),at.castShadow=!0;let J=new D(new O(1,1).rotateX(-Math.PI/2),new t({color:`#ffffff`,alphaMap:m.mancha,transparent:!0,blending:2,depthWrite:!1,fog:!1}),K.length);J.instanceMatrix.setUsage(se),J.frustumCulled=!1;let ut=R(`#ffc98e`,.42),dt=new v,ft=h.fog.density;function pt(e){K.forEach((t,n)=>{let r=t.x-t.lado*$e,a=e.x-r,o=e.z-t.z,s=Math.hypot(a,o),c=8.4/(8.4+Math.max(e.y,.3)),l=i.clamp(s*.2*i.clamp(2.5/Math.max(e.y,.5),.15,1.6),.7,9);q.position.set(r+a*c,.03,t.z+o*c),q.rotation.set(0,Math.atan2(a,o),0),q.scale.set(.55,1,l),q.updateMatrix(),J.setMatrixAt(n,q.matrix);let u=Math.exp(-((ft*s)**2));J.setColorAt(n,dt.copy(ut).multiplyScalar(u))}),q.scale.set(1,1,1),J.instanceMatrix.needsUpdate=!0,J.instanceColor.needsUpdate=!0}h.add(at,ot,st,ct,J);let mt=V([B(U(.1,.16,2.6,6,0,1.3,0),`#1d1712`),B(new r(1.5,1).translate(0,3.4,0),`#13261b`),B(new r(1.1,1).translate(.8,3,.4),`#102018`),B(new r(1,1).translate(-.7,3.1,-.5),`#152a1e`)]),ht=[];for(let e=P.z0;e>P.z0-P.comprimento;e-=13+G()*9)[[-18,60],[F.manutencao-18,F.manutencao+36],[-322,-252]].some(([t,n])=>e>t&&e<n)||ht.push(e);let gt=new D(mt,new u({vertexColors:!0,roughness:.95}),ht.length);ht.forEach((e,t)=>{q.position.set(0,.2,e),q.rotation.set(0,G()*6.28,0),q.scale.setScalar(.8+G()*.5),q.updateMatrix(),gt.setMatrixAt(t,q.matrix)}),q.scale.setScalar(1),gt.castShadow=!0,h.add(gt);let _t=V([B(W(Re([[-2.2,.3],[2.12,.3],[2.22,.5],[2.2,.78],[1.95,.9],[1.15,1],[-1.55,1.04],[-2.12,.98],[-2.24,.78],[-2.25,.45]]),1.76,.07),`#ffffff`),B(W(Re([[1.16,.98],[.45,1.41],[-.78,1.44],[-1.56,1.03]]),1.6,.06),new v(.025,.028,.035)),...[[-1.38,.78],[-1.38,-.78],[1.4,.78],[1.4,-.78]].map(([e,t])=>B(U(.33,.33,.22,14).rotateX(Math.PI/2).translate(e,.33,t),new v(.01,.01,.012)))]).rotateY(Math.PI/2),vt=V([B(W(ze(12,2.75,.32),2.5,.08,4).translate(0,1.72,0),`#ffffff`),...[[-4.2,1.1],[-4.2,-1.1],[3.6,1.1],[3.6,-1.1]].map(([e,t])=>B(U(.5,.5,.3,14).rotateX(Math.PI/2).translate(e,.5,t),new v(.01,.01,.012)))]).rotateY(Math.PI/2),yt=(e,t,n,r,i=[])=>V([...[-1,1].map(n=>B(H(.36,.13,.06,n*r,t,-e-.01),new v(6,6,6.6))),...[-1,1].map(t=>B(H(.34,.12,.06,t*r,n,e+.01),new v(3.4,.14,.08))),...i]),bt=yt(2.22,.72,.86,.6),xt=[];for(let e of[-1,1])for(let t=-5.2;t<4.4;t+=1.45)xt.push(B(H(.02,.85,1.25,e*1.26,2.15,t),new v(.95,.78,.52)));let St=yt(6.02,.75,1,.9,[...xt,B(H(1.7,.28,.04,0,2.86,-6.03),new v(3.2,1.6,.2))]),Ct=(e,t,n,r,i,a)=>{let o=[];for(let a of[-1,1]){let s=a*r;for(let[r,a,c]of[[t,-e,new v(1.7,1.75,2)],[n,e,new v(2.2,.1,.06)]]){let e=a+i;o.push(Ve([s-.09,r,a],[s+.09,r,a],[s+.09,r,e],[s-.09,r,e],c)),o.push(Ve([s,r-.07,a],[s,r+.07,a],[s,r+.07,e],[s,r-.07,e],c))}}let s=-e-.4,c=s-a;return o.push(Ve([-.9,.03,s],[.9,.03,s],[2.6,.03,c],[-2.6,.03,c],new v(.75,.7,.6))),V(o)},wt=Ct(2.22,.72,.86,.6,9,15),Tt=Ct(6.02,.75,1,.9,9,17),Et=new u({vertexColors:!0,roughness:.32,metalness:.55}),Dt=new t({vertexColors:!0}),Ot=new t({vertexColors:!0,alphaMap:m.rastro,transparent:!0,blending:2,depthWrite:!1,side:2}),kt=[`#d9dde3`,`#9aa3ad`,`#5c6470`,`#1b1f26`,`#7a1d1d`,`#1f3357`,`#c8ccd2`,`#2c3a2f`],At=[];Ae.forEach((e,t)=>{let n=G()*40;for(let r=0;r<7;r++){let i=t===2&&(r===1||r===4);At.push({faixa:e,s:n,onibus:i,cor:new v(kt[Math.floor(G()*kt.length)]),jitter:Math.round((G()-.5)*7)}),n+=P.comprimento/7*(.7+G()*.6)}});let jt=At.filter(e=>!e.onibus),Mt=At.filter(e=>e.onibus),Nt=(e,t,n,r)=>{let i=new D(e,t,n.length);return i.instanceMatrix.setUsage(se),i.castShadow=r,i.frustumCulled=!1,i},Pt=[Nt(_t,Et,jt,!0),Nt(bt,Dt,jt,!1),Nt(wt,Ot,jt,!1)],Ft=[Nt(vt,Et,Mt,!0),Nt(St,Dt,Mt,!1),Nt(Tt,Ot,Mt,!1)];jt.forEach((e,t)=>Pt[0].setColorAt(t,e.cor)),Mt.forEach((e,t)=>Ft[0].setColorAt(t,t?new v(`#e8e2d0`):new v(`#d6dce4`))),h.add(...Pt,...Ft);let It=(e,t)=>{let n=(e.s+e.faixa.v*t)%P.comprimento;return e.faixa.dir<0?P.z0-n:P.z0-P.comprimento+n};function Lt(e){for(let[t,n]of[[jt,Pt],[Mt,Ft]]){t.forEach((t,r)=>{q.position.set(t.faixa.x,0,It(t,e)),q.rotation.set(0,t.faixa.dir<0?0:Math.PI,0),q.updateMatrix();for(let e of n)e.setMatrixAt(r,q.matrix)});for(let e of n)e.instanceMatrix.needsUpdate=!0}}let Rt=document.fonts?.load(`800 64px Overpass`).catch(()=>{})??Promise.resolve(),zt=new u({color:`#1f2733`,roughness:.48,metalness:.65}),Bt=new u({color:`#0d1117`,roughness:.4,metalness:.3}),Vt=new t({color:R(N.turquesa,3)});function Ht(e=1,n=!0){let r=new re,i=new x(V([B(W(ze(.95,1.05,.08),.6,.03).translate(0,.525,0),`#1f2733`),B(H(.34,1.6,.26,0,1.8,0),`#26303d`),B(W(ze(1.56,.9,.1),.32,.04).translate(0,3,0),`#141a22`),B(H(.6,.26,.34,0,3.6,0),`#1a212b`)]),new u({vertexColors:!0,roughness:.45,metalness:.6}));i.castShadow=!0,i.receiveShadow=!0;let a=new x(H(1.2,.06,.85,0,.03,0),new u({color:`#4a515c`,roughness:.9}));a.receiveShadow=!0;let o=new x(new O(1.9,1.5).rotateX(-Math.PI/2),new t({color:`#000000`,alphaMap:m.mancha,transparent:!0,opacity:.85,depthWrite:!1}));o.position.y=.065,r.add(a,o);let s=document.createElement(`canvas`);s.width=256,s.height=128;let c=s.getContext(`2d`);We(c,null);let l=z(s),d=new x(new O(1.36,.68),new t({map:l,color:R(`#ffffff`,3.4)}));d.position.set(0,3,.165);let f=new x(new g(.44,.035,.02),Vt);f.position.set(0,3.52,.175);let p=new x(new me(.075,.075,.05,20).rotateX(Math.PI/2),Bt);if(p.position.set(.16,3.62,.18),r.add(i,d,f,p),n){let e=Fe(256,256,()=>{}),t=()=>{let t=e.getContext(`2d`);t.fillStyle=`#ffffff`,t.beginPath(),t.arc(128,128,124,0,Math.PI*2),t.fill(),t.lineWidth=30,t.strokeStyle=`#d4161c`,t.beginPath(),t.arc(128,128,106,0,Math.PI*2),t.stroke(),t.fillStyle=`#111111`,t.textAlign=`center`,t.font=`800 104px Overpass, system-ui, sans-serif`,t.fillText(String(je),128,150),t.font=`700 30px Overpass, system-ui, sans-serif`,t.fillText(`km/h`,128,190),n.needsUpdate=!0},n=z(e);t(),Rt.then(t);let i=new x(new me(.44,.44,.03,40).rotateX(Math.PI/2).rotateZ(Math.PI/2),new u({map:n,emissiveMap:n,emissive:`#ffffff`,emissiveIntensity:.12,roughness:.4}));i.position.set(0,4.25,0);let a=new x(new g(.08,.5,.06),zt);a.position.set(0,3.88,-.04),i.castShadow=!0,r.add(i,a)}return r.scale.setScalar(e),{g:r,ctx:c,texPainel:l,valor:void 0}}let Y=Ht();Y.g.position.set(P.borda+1.5,.16,F.radar),Y.g.rotation.y=-.18;let Ut=lt(1,F.radar),Wt=[0,1,2].map(()=>{let e=new x(new oe(Be(.4,.7,.045),{depth:.02,bevelEnabled:!1,curveSegments:12}),new t({color:R(N.turquesa,1.6),transparent:!0,opacity:0,blending:2,depthWrite:!1}));return e.rotation.y=-2.2,e.position.set(P.borda+.95,3.72,F.radar+.95),h.add(e),e});h.add(Y.g);let X=F.manutencao,Z=-P.borda-1.4,Gt=new re,Kt=new x(V([B(U(.13,.17,6.2,14,0,3.1,0),`#5a6472`),B(U(.08,.08,4.6,10).rotateZ(Math.PI/2).translate(2.3,6,0),`#5a6472`),B(H(.72,.42,.5,4.3,5.68,0),`#aeb6c1`),B(H(.8,.06,.62,4.3,5.92,.04),`#c9cfd8`),B(H(.3,.3,.06,4.3,5.68,.27),`#0b0e13`),B(H(.36,.24,.3,3.55,5.62,0),`#d3d8de`)]),new u({vertexColors:!0,roughness:.45,metalness:.4}));Kt.castShadow=!0;let qt=new t({color:R(`#ff3a24`,3)}),Jt=new x(new de(.2,.028,8,40),qt);Jt.position.set(4.3,5.68,.31);let Yt=new x(W(ze(.9,1.4,.06),.55,.03),new u({color:`#c9ced6`,roughness:.5,metalness:.35}));Yt.position.set(.85,.86,0),Yt.castShadow=!0,Yt.receiveShadow=!0;let Xt=new x(new O(.72,1.18),new t({map:z(Fe(64,96,e=>{e.fillStyle=`#071410`,e.fillRect(0,0,64,96);let t=Ne(3);for(let n=0;n<70;n++)e.fillStyle=t()>.7?`#ffb21a`:t()>.4?`#4dffb0`:`#7cf5e1`,e.globalAlpha=.4+t()*.6,e.fillRect(Math.floor(t()*14)*4+4,Math.floor(t()*22)*4+4,2,2)})),color:R(`#ffffff`,.9)}));Xt.position.set(.85,.86,.281);let Zt=new re;Zt.position.set(.85-.45,.86,.29);let Qt=new x(new g(.88,1.36,.03).translate(.44,0,0),new u({color:`#bfc5ce`,roughness:.45,metalness:.4}));Qt.castShadow=!0,Zt.add(Qt),Gt.add(Kt,Jt,Yt,Xt,Zt),Gt.position.set(Z,.16,X),h.add(Gt);let $t=V([B(W(Re([[-2.6,.38],[2.6,.38],[2.62,1.12],[2.35,1.32],[1.72,2.12],[1.25,2.28],[-2.5,2.3],[-2.62,2.18]]),2,.08),`#e4e7eb`),B(W(Re([[2.34,1.34],[1.72,2.1],[1.18,2.1],[1.18,1.36]]),2.04,.05),new v(.02,.025,.03)),B(H(5.1,.16,2.04,0,1.1,0),`#ff7a1a`),...[[-1.7,.85],[-1.7,-.85],[1.75,.85],[1.75,-.85]].map(([e,t])=>B(U(.38,.38,.26,14).rotateX(Math.PI/2).translate(e,.38,t),new v(.01,.01,.012)))]).rotateY(-Math.PI/2),en=new x($t,new u({vertexColors:!0,roughness:.36,metalness:.3}));en.position.set(-P.borda-2.3,.16,X-8),en.castShadow=!0;let tn=new t({color:R(N.ambar,1)}),nn=new x(new g(1.25,.13,.28),tn);nn.position.set(en.position.x,2.56,en.position.z+1),nn.rotation.y=Math.PI/2;let rn=new x(new me(.15,2.4,9,24,1,!0).rotateZ(Math.PI/2).translate(4.5,0,0),Ke(N.ambar,0));rn.position.copy(nn.position),h.add(en,nn,rn);let an=V([B(new ee([[.001,.74],[.04,.74],[.16,.06],[.001,.06]].map(([e,t])=>new b(e,t)),18),`#ff5a14`),B(new ee([[.083,.5],[.112,.36],[.107,.36],[.078,.5]].map(([e,t])=>new b(e,t)),18).scale(1.04,1,1.04),new v(1.3,1.3,1.3)),B(new ee([[.122,.3],[.142,.2],[.137,.2],[.117,.3]].map(([e,t])=>new b(e,t)),18).scale(1.04,1,1.04),new v(1.3,1.3,1.3)),B(H(.42,.05,.42,0,.025,0),`#1a1a1a`)]),on=[[-7.72,X+4.6],[-7.72,X+2.4],[-7.72,X+.2],[-7.72,X-2],[-7.72,X-4.2],[-8.7,X-5.4]],sn=new D(an,new u({vertexColors:!0,roughness:.55}),on.length);on.forEach(([e,t],n)=>{q.position.set(e,e<-P.borda?.16:0,t),q.rotation.set(0,n,0),q.updateMatrix(),sn.setMatrixAt(n,q.matrix)}),sn.castShadow=!0,h.add(sn);let cn=new re,ln=new x(V([...[0,2.1,4.2].map(e=>B(U(.018,.018,1.7,5).translate(0,.85,0).rotateZ(.22).rotateY(e),`#2a2f36`)),B(U(.025,.025,.9,6,0,1.95,0),`#2a2f36`),B(H(.5,.36,.14,0,2.45,0),`#22262c`)]),new u({vertexColors:!0,roughness:.6,metalness:.5})),un=new x(new O(.42,.28),new t({color:R(`#eef4ff`,0)}));un.position.set(0,2.45,.075),cn.add(ln,un),cn.position.set(Z-1.2,.16,X+2.6),cn.lookAt(Z+.85,1,X),h.add(cn);let Q=P.borda+2,dn=-1.9,fn=[];for(let n of I){let r=new re;if(n.tipo===`cancela`){let e=z(Fe(256,16,e=>{for(let t=0;t<8;t++)e.fillStyle=t%2?`#f4f4f4`:`#d81f26`,e.fillRect(t*32,0,32,16)})),t=new x(W(ze(.42,1.1,.05),.36,.03).translate(0,.55,0),new u({color:`#f2b705`,roughness:.5,metalness:.2})),n=new x(new g(.1,.1,3.6).translate(0,0,-1.8),new u({map:e,emissiveMap:e,emissive:`#ffffff`,emissiveIntensity:.22,roughness:.4}));n.position.set(0,.92,-.1),r.add(t,n)}else if(n.tipo===`patinete`){let e=new x(V([B(H(.17,.05,1,0,.12,0),`#1c232c`),B(U(.025,.025,1.05,8).rotateX(-.22).translate(0,.62,-.5),`#3a4452`),B(U(.02,.02,.52,8).rotateZ(Math.PI/2).translate(0,1.14,-.62),`#3a4452`),...[-.5,.48].map(e=>B(U(.1,.1,.05,14).rotateZ(Math.PI/2).translate(0,.1,e),`#0b0d10`))]),new u({vertexColors:!0,roughness:.4,metalness:.5})),t=new x(new g(.06,.04,.02),Vt);t.position.set(0,1.02,-.58),e.rotation.z=.12,r.add(e,t)}else if(n.tipo===`lombada`)r.add(Ht(.72,!1).g);else{let e=new x(V([B(U(.08,.1,4.6,12,0,2.3,0),`#5a6472`),B(U(.04,.04,.9,8).rotateX(Math.PI/2).translate(0,4.5,-.45),`#5a6472`),B(U(.2,.2,.12,20,0,4.42,-.9),`#e5e8ec`),B(new ne(.18,20,10,0,Math.PI*2,Math.PI/2,Math.PI/2).translate(0,4.37,-.9),new v(.02,.025,.035))]),new u({vertexColors:!0,roughness:.4,metalness:.5}));r.add(e)}r.traverse(e=>{e.isMesh&&(e.castShadow=!0,e.receiveShadow=!0)}),r.position.set(Q,.16,n.z),h.add(r);let i=n===I[0],a=i?N.ambar:N.turquesa,o=.6+n.valor*.1,s=new x(new me(.3,.3,o,32,1,!0).translate(0,o/2,0),qe(a));s.position.set(Q+1.2,.16,n.z+dn);let c=new x(new de(.42,.025,6,48).rotateX(Math.PI/2),new t({color:R(a,2.2),transparent:!0,blending:2,depthWrite:!1}));c.position.set(Q+1.2,.19,n.z+dn);let l=document.createElement(`canvas`);l.width=256,l.height=128;let d=z(l),f=()=>{let e=l.getContext(`2d`);e.clearRect(0,0,256,128),e.fillStyle=`#ffffff`,e.textBaseline=`alphabetic`,e.font=`800 84px Overpass, system-ui, sans-serif`;let t=e.measureText(String(n.valor)).width;e.font=`700 34px Overpass, system-ui, sans-serif`;let r=e.measureText(`/100`).width,i=(256-t-r-6)/2;e.font=`800 84px Overpass, system-ui, sans-serif`,e.fillText(String(n.valor),i,100),e.globalAlpha=.72,e.font=`700 34px Overpass, system-ui, sans-serif`,e.fillText(`/100`,i+t+6,100),d.needsUpdate=!0};f(),Rt.then(f);let p=new e(new ae({map:d,color:R(a,i?2.4:1.6),transparent:!0,depthWrite:!1,fog:!0}));p.scale.set(1.3,.65,1),p.position.set(Q+1.2,.16+o+.55,n.z+dn),h.add(s,c,p),fn.push({item:n,coluna:s,base:c,rotulo:p,alturaColuna:o,principal:i})}let pn=new re,mn=new x(new O(4.6,.35).rotateX(-Math.PI/2),new t({color:R(N.turquesa,3),alphaMap:m.mancha,transparent:!0,blending:2,depthWrite:!1}));mn.position.y=.2;let hn=new x(new O(4.6,4.5).translate(0,2.25,0),new t({color:R(N.turquesa,.32),alphaMap:m.rastro,transparent:!0,blending:2,depthWrite:!1,side:2}));pn.add(mn,hn),pn.position.set(Q+.1,0,I[0].z+6),h.add(pn);let gn=e=>new ce(e.map(e=>new _(...e)),!1,`centripetal`),_n={radar:{pos:gn([[-4,26,52],[1,15,32],[6.5,6,18],[10.7,2,7.2]]),alvo:gn([[5,0,-8],[7,1,-4],[8.6,2.2,-1],[9.1,2.95,-.4]])},manutencao:{pos:gn([[8,14,X+32],[1.5,6.8,X+18],[-4.5,4.2,X+11.5],[-4.6,2.1,X+8.6],[-4.4,4.4,X+6.4],[-3,6.7,X+5.2]]),alvo:gn([[-7.5,2.5,X],[-8.3,1.8,X],[-8.6,1.5,X-.1],[-8.7,1.4,X-.2],[-7.4,3.6,X],[-5.3,5.75,X]])},revisoes:{pos:gn([[.6,4.6,-282],[.8,4.2,-289.5],[.8,4.2,-297],[.8,4.2,-304.5],[.8,4.2,-312]]),alvo:gn([[10.8,2.1,-288],[10.8,2.3,-289.5],[10.8,2.3,-297],[10.8,2.3,-304.5],[10.8,2.3,-312]])}},$=new _,vn=new _,yn=new _(0,0,1),bn=new te,xn=new pe(1,1,{type:ge,samples:a?0:4}),Sn=new we(o,xn);Sn.addPass(new Te(h,y));let Cn=new De(new b(256,256),.62,.42,.92);Sn.addPass(Cn),Sn.addPass(new ke);let wn=new xe(Je);Sn.addPass(wn),a&&(wn.uniforms.grao.value=.035);function Tn(e){for(let e of[w,T,E,k])e.intensity=0;k.distance=14,rn.material.uniforms.intensidade.value=0,e===`radar`?(w.color.set(`#ffd2a0`),w.position.copy(Ut),w.target.position.set(P.borda+1.5,0,F.radar),w.angle=.85,w.distance=30,w.intensity=260,E.color.set(N.ambar),E.position.set(P.borda+1.2,3,F.radar+1.2),k.position.set(P.borda+1.2,3.55,F.radar+.6)):e===`manutencao`?(w.color.set(`#eef4ff`),w.position.set(Z-1.2,2.6,X+2.6),w.target.position.set(Z+.85,.9,X),w.angle=.5,w.distance=16,T.position.copy(nn.position),E.color.set(`#9fffe0`),E.position.set(Z+.85,.95,X+.75),k.position.set(Z+4.3,5.3,X+1.4),k.distance=5):(w.color.set(`#ffd2a0`),w.position.copy(lt(1,I[1].z)),w.target.position.set(Q,0,I[1].z),w.angle=.95,w.distance=30,w.intensity=220,E.color.set(N.ambar),E.position.set(Q+.9,2.2,I[0].z),k.position.set(Q+.9,1.6,I[2].z)),w.target.updateMatrixWorld()}let En=null,Dn=!1;function On(e,{p:t,t:n,parado:r}){En!==e&&(Tn(e),En=e);let a=i.clamp((t-.04)/.56,0,1),s=L(0,1,a)*.7+a*.3,c=_n[e];c.pos.getPoint(s,$),c.alvo.getPoint(s,vn),r||($.x+=Math.sin(n*.61)*.05+Math.sin(n*1.37)*.02,$.y+=Math.sin(n*.83+1.3)*.04,vn.x+=Math.sin(n*.47+.4)*.05),y.position.copy($),y.lookAt(vn),ue.position.copy($),pt($),bn.setFromAxisAngle(yn,(1-a)*(e===`manutencao`?-.05:.04)),y.quaternion.multiply(bn),Lt(n);let l=null;if(e===`radar`){let e=P.z0-(F.radar+11.5),t=-99;for(let r of At){if(r.faixa.dir>0)continue;let i=Math.floor((r.faixa.v*n+r.s-e)/P.comprimento),a=(e-r.s+i*P.comprimento)/r.faixa.v;a<=n&&a>t&&(t=a,Y.ultima=Math.round(r.faixa.v*3.6)+r.jitter,Y.faixaLida=Ae.indexOf(r.faixa))}let i=n-t,a=r?38:i<4.5?Y.ultima:null;a!==Y.valor&&(We(Y.ctx,a),Y.texPainel.needsUpdate=!0,Y.valor=a),Ue.forEach((e,t)=>{e.material.opacity=!r&&Y.faixaLida===t?Math.max(0,1-i/.9):0}),Wt.forEach((e,t)=>{let i=r?.35+t*.22:(n*.8+t/3)%1;e.scale.setScalar(.8+i*2.2),e.material.opacity=Math.sin(Math.PI*Math.min(i*1.3,1))*.75});let o=a!=null&&a>je;E.color.set(o?`#ff3a24`:N.ambar),E.intensity=a==null?1.2:5,k.intensity=2.2}else if(e===`manutencao`){let e=L(.24,.34,a)*(1-L(.66,.76,a)),t=a<.28?`falha`:a<.71?`execucao`:`concluida`;Zt.rotation.y=-1.9*e,Xt.visible=e>.02,w.intensity=55*e,Pe(un.material.color,`#eef4ff`,4*e);let i=r?1:.55+.45*Math.sign(Math.sin(n*7));t===`falha`?Pe(qt.color,`#ff3a24`,2.4*i):t===`execucao`?Pe(qt.color,N.ambar,2.4):Pe(qt.color,N.turquesa,1.8),k.color.copy(qt.color).multiplyScalar(.3),k.intensity=1.2,E.intensity=.2*e;let o=r?3.7:n*4.2;T.intensity=160*e,T.target.position.set(nn.position.x+Math.cos(o)*8,.2,nn.position.z+Math.sin(o)*8),T.target.updateMatrixWorld(),rn.rotation.set(0,-o,0),rn.material.uniforms.intensidade.value=.22*e,Pe(tn.color,N.ambar,.6+4*e*(.6+.4*Math.abs(Math.sin(o*2)))),l={fase:t}}else{let e=i.lerp(I[0].z+3,I[3].z-3,i.clamp(a/.8,0,1));pn.position.z=e;let t=L(.82,.92,a);mn.material.opacity=1-t,hn.material.opacity=1-t;for(let t of fn){let i=L(t.item.z+1.5,t.item.z-1.5,e-dn);t.coluna.material.uniforms.altura.value=Math.max(.001,i),t.coluna.material.uniforms.tempo.value=r?0:n,t.coluna.material.uniforms.brilho.value=t.principal?1+L(.1,.3,a)*.8:.85,t.rotulo.position.y=.16+t.alturaColuna*i+.55,t.rotulo.material.opacity=L(.6,1,i),t.base.material.opacity=.35+i*.65}E.intensity=6*L(.05,.25,a),k.intensity=1.5,w.position.set(Q-4,6.5,$.z+1.5),w.target.position.set(Q,.5,$.z-.5),w.target.updateMatrixWorld()}return wn.uniforms.tempo.value=r?0:n,Dn?o.render(h,y):Sn.render(),l}return{canvas:s,preparar:()=>(Tn(`radar`),Lt(0),pt(y.position),o.compileAsync(h,y).catch(()=>{})),tamanho(e,t,n){let r=Math.max(1,Math.round(e)),a=Math.max(1,Math.round(t));o.setPixelRatio(n),o.setSize(r,a,!1),Sn.setPixelRatio(n),Sn.setSize(r,a),Cn.setSize(Math.round(r*n/2),Math.round(a*n/2));let s=r/a;y.aspect=s;let c=2*Math.atan(Math.tan(i.degToRad(30)/2)*2.39);y.fov=i.radToDeg(2*Math.atan(Math.tan(c/2)/Math.max(s,1))),y.updateProjectionMatrix(),wn.uniforms.aspecto.value=s},quadro:On,simplificar(){Dn=!0}}}export{Me as REPOUSO,Ye as criarMundo};