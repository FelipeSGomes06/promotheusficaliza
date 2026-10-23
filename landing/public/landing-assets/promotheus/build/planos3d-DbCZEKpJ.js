import{A as e,B as t,C as n,D as r,F as i,H as a,J as o,K as s,L as c,M as l,P as u,Q as d,S as f,U as p,V as ee,W as m,Z as te,a as h,at as g,b as ne,c as re,d as _,f as v,g as ie,h as ae,it as y,k as b,l as oe,m as se,n as ce,o as x,ot as S,p as le,q as C,r as ue,rt as w,s as T,tt as E,v as D,w as O,x as k,y as de,z as A}from"./three.module-BROWA2qQ.js";function fe(e,t=!1){let n=e[0].index!==null,r=new Set(Object.keys(e[0].attributes)),i=new Set(Object.keys(e[0].morphAttributes)),a={},o={},s=e[0].morphTargetsRelative,c=new T,l=0;for(let u=0;u<e.length;++u){let d=e[u],f=0;if(n!==(d.index!==null))return console.error(`THREE.BufferGeometryUtils: .mergeGeometries() failed with geometry at index `+u+`. All geometries must have compatible attributes; make sure index attribute exists among all geometries, or in none of them.`),null;for(let e in d.attributes){if(!r.has(e))return console.error(`THREE.BufferGeometryUtils: .mergeGeometries() failed with geometry at index `+u+`. All geometries must have compatible attributes; make sure "`+e+`" attribute exists among all geometries, or in none of them.`),null;a[e]===void 0&&(a[e]=[]),a[e].push(d.attributes[e]),f++}if(f!==r.size)return console.error(`THREE.BufferGeometryUtils: .mergeGeometries() failed with geometry at index `+u+`. Make sure all geometries have the same number of attributes.`),null;if(s!==d.morphTargetsRelative)return console.error(`THREE.BufferGeometryUtils: .mergeGeometries() failed with geometry at index `+u+`. .morphTargetsRelative must be consistent throughout all geometries.`),null;for(let e in d.morphAttributes){if(!i.has(e))return console.error(`THREE.BufferGeometryUtils: .mergeGeometries() failed with geometry at index `+u+`.  .morphAttributes must be consistent throughout all geometries.`),null;o[e]===void 0&&(o[e]=[]),o[e].push(d.morphAttributes[e])}if(t){let e;if(n)e=d.index.count;else if(d.attributes.position!==void 0)e=d.attributes.position.count;else return console.error(`THREE.BufferGeometryUtils: .mergeGeometries() failed with geometry at index `+u+`. The geometry must have either an index or a position attribute`),null;c.addGroup(l,e,u),l+=e}}if(n){let t=0,n=[];for(let r=0;r<e.length;++r){let i=e[r].index;for(let e=0;e<i.count;++e)n.push(i.getX(e)+t);t+=e[r].attributes.position.count}c.setIndex(n)}for(let e in a){let t=pe(a[e]);if(!t)return console.error(`THREE.BufferGeometryUtils: .mergeGeometries() failed while trying to merge the `+e+` attribute.`),null;c.setAttribute(e,t)}for(let e in o){let t=o[e][0].length;if(t!==0){c.morphAttributes=c.morphAttributes||{},c.morphAttributes[e]=[];for(let n=0;n<t;++n){let t=[];for(let r=0;r<o[e].length;++r)t.push(o[e][r][n]);let r=pe(t);if(!r)return console.error(`THREE.BufferGeometryUtils: .mergeGeometries() failed while trying to merge the `+e+` morphAttribute.`),null;c.morphAttributes[e].push(r)}}}return c}function pe(e){let t,n,r,i=-1,a=0;for(let o=0;o<e.length;++o){let s=e[o];if(t===void 0&&(t=s.array.constructor),t!==s.array.constructor)return console.error(`THREE.BufferGeometryUtils: .mergeAttributes() failed. BufferAttribute.array must be of consistent array types across matching attributes.`),null;if(n===void 0&&(n=s.itemSize),n!==s.itemSize)return console.error(`THREE.BufferGeometryUtils: .mergeAttributes() failed. BufferAttribute.itemSize must be consistent across matching attributes.`),null;if(r===void 0&&(r=s.normalized),r!==s.normalized)return console.error(`THREE.BufferGeometryUtils: .mergeAttributes() failed. BufferAttribute.normalized must be consistent across matching attributes.`),null;if(i===-1&&(i=s.gpuType),i!==s.gpuType)return console.error(`THREE.BufferGeometryUtils: .mergeAttributes() failed. BufferAttribute.gpuType must be consistent across matching attributes.`),null;a+=s.count*n}let o=new t(a),s=new x(o,n,r),c=0;for(let t=0;t<e.length;++t){let r=e[t];if(r.isInterleavedBufferAttribute){let e=c/n;for(let t=0,i=r.count;t<i;t++)for(let i=0;i<n;i++){let n=r.getComponent(t,i);s.setComponent(t+e,i,n)}}else o.set(r.array,c);c+=r.count*n}return i!==void 0&&(s.gpuType=i),s}var j={name:`CopyShader`,uniforms:{tDiffuse:{value:null},opacity:{value:1}},vertexShader:`

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


		}`},M=class{constructor(){this.isPass=!0,this.enabled=!0,this.needsSwap=!0,this.clear=!1,this.renderToScreen=!1}setSize(){}render(){console.error(`THREE.Pass: .render() must be implemented in derived pass.`)}dispose(){}},me=new i(-1,1,1,-1,0,1),he=new class extends T{constructor(){super(),this.setAttribute(`position`,new D([-1,3,0,-1,-1,0,3,-1,0],3)),this.setAttribute(`uv`,new D([0,2,0,0,2,0],2))}},N=class{constructor(e){this._mesh=new b(he,e)}dispose(){this._mesh.geometry.dispose()}render(e){e.render(this._mesh,me)}get material(){return this._mesh.material}set material(e){this._mesh.material=e}},ge=class extends M{constructor(e,t=`tDiffuse`){super(),this.textureID=t,this.uniforms=null,this.material=null,e instanceof C?(this.uniforms=e.uniforms,this.material=e):e&&(this.uniforms=w.clone(e.uniforms),this.material=new C({name:e.name===void 0?`unspecified`:e.name,defines:Object.assign({},e.defines),uniforms:this.uniforms,vertexShader:e.vertexShader,fragmentShader:e.fragmentShader})),this._fsQuad=new N(this.material)}render(e,t,n){this.uniforms[this.textureID]&&(this.uniforms[this.textureID].value=n.texture),this._fsQuad.material=this.material,this.renderToScreen?(e.setRenderTarget(null),this._fsQuad.render(e)):(e.setRenderTarget(t),this.clear&&e.clear(e.autoClearColor,e.autoClearDepth,e.autoClearStencil),this._fsQuad.render(e))}dispose(){this.material.dispose(),this._fsQuad.dispose()}},_e=class extends M{constructor(e,t){super(),this.scene=e,this.camera=t,this.clear=!0,this.needsSwap=!1,this.inverse=!1}render(e,t,n){let r=e.getContext(),i=e.state;i.buffers.color.setMask(!1),i.buffers.depth.setMask(!1),i.buffers.color.setLocked(!0),i.buffers.depth.setLocked(!0);let a,o;this.inverse?(a=0,o=1):(a=1,o=0),i.buffers.stencil.setTest(!0),i.buffers.stencil.setOp(r.REPLACE,r.REPLACE,r.REPLACE),i.buffers.stencil.setFunc(r.ALWAYS,a,4294967295),i.buffers.stencil.setClear(o),i.buffers.stencil.setLocked(!0),e.setRenderTarget(n),this.clear&&e.clear(),e.render(this.scene,this.camera),e.setRenderTarget(t),this.clear&&e.clear(),e.render(this.scene,this.camera),i.buffers.color.setLocked(!1),i.buffers.depth.setLocked(!1),i.buffers.color.setMask(!0),i.buffers.depth.setMask(!0),i.buffers.stencil.setLocked(!1),i.buffers.stencil.setFunc(r.EQUAL,1,4294967295),i.buffers.stencil.setOp(r.KEEP,r.KEEP,r.KEEP),i.buffers.stencil.setLocked(!0)}},ve=class extends M{constructor(){super(),this.needsSwap=!1}render(e){e.state.buffers.stencil.setLocked(!1),e.state.buffers.stencil.setTest(!1)}},ye=class{constructor(e,t){if(this.renderer=e,this._pixelRatio=e.getPixelRatio(),t===void 0){let n=e.getSize(new y);this._width=n.width,this._height=n.height,t=new S(this._width*this._pixelRatio,this._height*this._pixelRatio,{type:k}),t.texture.name=`EffectComposer.rt1`}else this._width=t.width,this._height=t.height;this.renderTarget1=t,this.renderTarget2=t.clone(),this.renderTarget2.texture.name=`EffectComposer.rt2`,this.writeBuffer=this.renderTarget1,this.readBuffer=this.renderTarget2,this.renderToScreen=!0,this.passes=[],this.copyPass=new ge(j),this.copyPass.material.blending=0,this.timer=new E}swapBuffers(){let e=this.readBuffer;this.readBuffer=this.writeBuffer,this.writeBuffer=e}addPass(e){this.passes.push(e),e.setSize(this._width*this._pixelRatio,this._height*this._pixelRatio)}insertPass(e,t){this.passes.splice(t,0,e),e.setSize(this._width*this._pixelRatio,this._height*this._pixelRatio)}removePass(e){let t=this.passes.indexOf(e);t!==-1&&this.passes.splice(t,1)}isLastEnabledPass(e){for(let t=e+1;t<this.passes.length;t++)if(this.passes[t].enabled)return!1;return!0}render(e){this.timer.update(),e===void 0&&(e=this.timer.getDelta());let t=this.renderer.getRenderTarget(),n=!1;for(let t=0,r=this.passes.length;t<r;t++){let r=this.passes[t];if(r.enabled!==!1){if(r.renderToScreen=this.renderToScreen&&this.isLastEnabledPass(t),r.render(this.renderer,this.writeBuffer,this.readBuffer,e,n),r.needsSwap){if(n){let t=this.renderer.getContext(),n=this.renderer.state.buffers.stencil;n.setFunc(t.NOTEQUAL,1,4294967295),this.copyPass.render(this.renderer,this.writeBuffer,this.readBuffer,e),n.setFunc(t.EQUAL,1,4294967295)}this.swapBuffers()}_e!==void 0&&(r instanceof _e?n=!0:r instanceof ve&&(n=!1))}}this.renderer.setRenderTarget(t)}reset(e){if(e===void 0){let t=this.renderer.getSize(new y);this._pixelRatio=this.renderer.getPixelRatio(),this._width=t.width,this._height=t.height,e=this.renderTarget1.clone(),e.setSize(this._width*this._pixelRatio,this._height*this._pixelRatio)}this.renderTarget1.dispose(),this.renderTarget2.dispose(),this.renderTarget1=e,this.renderTarget2=e.clone(),this.writeBuffer=this.renderTarget1,this.readBuffer=this.renderTarget2}setSize(e,t){this._width=e,this._height=t;let n=this._width*this._pixelRatio,r=this._height*this._pixelRatio;this.renderTarget1.setSize(n,r),this.renderTarget2.setSize(n,r);for(let e=0;e<this.passes.length;e++)this.passes[e].setSize(n,r)}setPixelRatio(e){this._pixelRatio=e,this.setSize(this._width,this._height)}dispose(){this.renderTarget1.dispose(),this.renderTarget2.dispose(),this.copyPass.dispose()}},be=class extends M{constructor(e,t,n=null,r=null,i=null){super(),this.scene=e,this.camera=t,this.overrideMaterial=n,this.clearColor=r,this.clearAlpha=i,this.clear=!0,this.clearDepth=!1,this.needsSwap=!1,this.isRenderPass=!0,this._oldClearColor=new _}render(e,t,n){let r=e.autoClear;e.autoClear=!1;let i,a;this.overrideMaterial!==null&&(a=this.scene.overrideMaterial,this.scene.overrideMaterial=this.overrideMaterial),this.clearColor!==null&&(e.getClearColor(this._oldClearColor),e.setClearColor(this.clearColor,e.getClearAlpha())),this.clearAlpha!==null&&(i=e.getClearAlpha(),e.setClearAlpha(this.clearAlpha)),this.clearDepth==1&&e.clearDepth(),e.setRenderTarget(this.renderToScreen?null:n),this.clear===!0&&e.clear(e.autoClearColor,e.autoClearDepth,e.autoClearStencil),e.render(this.scene,this.camera),this.clearColor!==null&&e.setClearColor(this._oldClearColor),this.clearAlpha!==null&&e.setClearAlpha(i),this.overrideMaterial!==null&&(this.scene.overrideMaterial=a),e.autoClear=r}},xe={name:`LuminosityHighPassShader`,uniforms:{tDiffuse:{value:null},luminosityThreshold:{value:1},smoothWidth:{value:1},defaultColor:{value:new _(0)},defaultOpacity:{value:0}},vertexShader:`

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

		}`},Se=class t extends M{constructor(t,n=1,r,i){super(),this.strength=n,this.radius=r,this.threshold=i,this.resolution=t===void 0?new y(256,256):new y(t.x,t.y),this.clearColor=new _(0,0,0),this.needsSwap=!1,this.renderTargetsHorizontal=[],this.renderTargetsVertical=[],this.nMips=5;let a=Math.round(this.resolution.x/2),o=Math.round(this.resolution.y/2);this.renderTargetBright=new S(a,o,{type:k,depthBuffer:!1}),this.renderTargetBright.texture.name=`UnrealBloomPass.bright`,this.renderTargetBright.texture.generateMipmaps=!1;for(let e=0;e<this.nMips;e++){let t=new S(a,o,{type:k,depthBuffer:!1});t.texture.name=`UnrealBloomPass.h`+e,t.texture.generateMipmaps=!1,this.renderTargetsHorizontal.push(t);let n=new S(a,o,{type:k,depthBuffer:!1});n.texture.name=`UnrealBloomPass.v`+e,n.texture.generateMipmaps=!1,this.renderTargetsVertical.push(n),a=Math.round(a/2),o=Math.round(o/2)}let s=xe;this.highPassUniforms=w.clone(s.uniforms),this.highPassUniforms.luminosityThreshold.value=i,this.highPassUniforms.smoothWidth.value=.01,this.materialHighPassFilter=new C({uniforms:this.highPassUniforms,vertexShader:s.vertexShader,fragmentShader:s.fragmentShader}),this.separableBlurMaterials=[];let c=[6,10,14,18,22];a=Math.round(this.resolution.x/2),o=Math.round(this.resolution.y/2);for(let e=0;e<this.nMips;e++)this.separableBlurMaterials.push(this._getSeparableBlurMaterial(c[e])),this.separableBlurMaterials[e].uniforms.invSize.value=new y(1/a,1/o),a=Math.round(a/2),o=Math.round(o/2);this.compositeMaterial=this._getCompositeMaterial(this.nMips),this.compositeMaterial.uniforms.blurTexture1.value=this.renderTargetsVertical[0].texture,this.compositeMaterial.uniforms.blurTexture2.value=this.renderTargetsVertical[1].texture,this.compositeMaterial.uniforms.blurTexture3.value=this.renderTargetsVertical[2].texture,this.compositeMaterial.uniforms.blurTexture4.value=this.renderTargetsVertical[3].texture,this.compositeMaterial.uniforms.blurTexture5.value=this.renderTargetsVertical[4].texture,this.compositeMaterial.uniforms.bloomStrength.value=n,this.compositeMaterial.uniforms.bloomRadius.value=.1;let l=[1,.8,.6,.4,.2];this.compositeMaterial.uniforms.bloomFactors.value=l,this.bloomTintColors=[new g(1,1,1),new g(1,1,1),new g(1,1,1),new g(1,1,1),new g(1,1,1)],this.compositeMaterial.uniforms.bloomTintColors.value=this.bloomTintColors,this.copyUniforms=w.clone(j.uniforms),this.blendMaterial=new C({uniforms:this.copyUniforms,vertexShader:j.vertexShader,fragmentShader:j.fragmentShader,premultipliedAlpha:!0,blending:2,depthTest:!1,depthWrite:!1,transparent:!0}),this._oldClearColor=new _,this._oldClearAlpha=1,this._basic=new e,this._fsQuad=new N(null)}dispose(){for(let e=0;e<this.renderTargetsHorizontal.length;e++)this.renderTargetsHorizontal[e].dispose();for(let e=0;e<this.renderTargetsVertical.length;e++)this.renderTargetsVertical[e].dispose();this.renderTargetBright.dispose();for(let e=0;e<this.separableBlurMaterials.length;e++)this.separableBlurMaterials[e].dispose();this.compositeMaterial.dispose(),this.blendMaterial.dispose(),this._basic.dispose(),this._fsQuad.dispose()}setSize(e,t){let n=Math.round(e/2),r=Math.round(t/2);this.renderTargetBright.setSize(n,r);for(let e=0;e<this.nMips;e++)this.renderTargetsHorizontal[e].setSize(n,r),this.renderTargetsVertical[e].setSize(n,r),this.separableBlurMaterials[e].uniforms.invSize.value=new y(1/n,1/r),n=Math.round(n/2),r=Math.round(r/2)}render(e,n,r,i,a){e.getClearColor(this._oldClearColor),this._oldClearAlpha=e.getClearAlpha();let o=e.autoClear;e.autoClear=!1,e.setClearColor(this.clearColor,0),a&&e.state.buffers.stencil.setTest(!1),this.renderToScreen&&(this._fsQuad.material=this._basic,this._basic.map=r.texture,e.setRenderTarget(null),e.clear(),this._fsQuad.render(e)),this.highPassUniforms.tDiffuse.value=r.texture,this.highPassUniforms.luminosityThreshold.value=this.threshold,this._fsQuad.material=this.materialHighPassFilter,e.setRenderTarget(this.renderTargetBright),e.clear(),this._fsQuad.render(e);let s=this.renderTargetBright;for(let n=0;n<this.nMips;n++)this._fsQuad.material=this.separableBlurMaterials[n],this.separableBlurMaterials[n].uniforms.colorTexture.value=s.texture,this.separableBlurMaterials[n].uniforms.direction.value=t.BlurDirectionX,e.setRenderTarget(this.renderTargetsHorizontal[n]),e.clear(),this._fsQuad.render(e),this.separableBlurMaterials[n].uniforms.colorTexture.value=this.renderTargetsHorizontal[n].texture,this.separableBlurMaterials[n].uniforms.direction.value=t.BlurDirectionY,e.setRenderTarget(this.renderTargetsVertical[n]),e.clear(),this._fsQuad.render(e),s=this.renderTargetsVertical[n];this._fsQuad.material=this.compositeMaterial,this.compositeMaterial.uniforms.bloomStrength.value=this.strength,this.compositeMaterial.uniforms.bloomRadius.value=this.radius,this.compositeMaterial.uniforms.bloomTintColors.value=this.bloomTintColors,e.setRenderTarget(this.renderTargetsHorizontal[0]),e.clear(),this._fsQuad.render(e),this._fsQuad.material=this.blendMaterial,this.copyUniforms.tDiffuse.value=this.renderTargetsHorizontal[0].texture,a&&e.state.buffers.stencil.setTest(!0),this.renderToScreen?(e.setRenderTarget(null),this._fsQuad.render(e)):(e.setRenderTarget(r),this._fsQuad.render(e)),e.setClearColor(this._oldClearColor,this._oldClearAlpha),e.autoClear=o}_getSeparableBlurMaterial(e){let t=[],n=e/3;for(let r=0;r<e;r++)t.push(.39894*Math.exp(-.5*r*r/(n*n))/n);let r=[],i=[];for(let n=1;n<e;n+=2){let a=t[n],o=n+1<e?t[n+1]:0,s=a+o;r.push((n*a+(n+1)*o)/s),i.push(s)}return new C({defines:{KERNEL_PAIRS:r.length},uniforms:{colorTexture:{value:null},invSize:{value:new y(.5,.5)},direction:{value:new y(.5,.5)},centerWeight:{value:t[0]},gaussianOffsets:{value:r},gaussianWeights:{value:i}},vertexShader:`

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

				}`})}_getCompositeMaterial(e){return new C({defines:{NUM_MIPS:e},uniforms:{blurTexture1:{value:null},blurTexture2:{value:null},blurTexture3:{value:null},blurTexture4:{value:null},blurTexture5:{value:null},bloomStrength:{value:1},bloomFactors:{value:null},bloomTintColors:{value:null},bloomRadius:{value:0}},vertexShader:`

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

				}`})}};Se.BlurDirectionX=new y(1,0),Se.BlurDirectionY=new y(0,1);var P={name:`OutputShader`,uniforms:{tDiffuse:{value:null},toneMappingExposure:{value:1}},vertexShader:`
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

		}`},Ce=class extends M{constructor(){super(),this.isOutputPass=!0,this.uniforms=w.clone(P.uniforms),this.material=new a({name:P.name,uniforms:this.uniforms,vertexShader:P.vertexShader,fragmentShader:P.fragmentShader}),this._fsQuad=new N(this.material),this._outputColorSpace=null,this._toneMapping=null}render(e,t,n){this.uniforms.tDiffuse.value=n.texture,this.uniforms.toneMappingExposure.value=e.toneMappingExposure,(this._outputColorSpace!==e.outputColorSpace||this._toneMapping!==e.toneMapping)&&(this._outputColorSpace=e.outputColorSpace,this._toneMapping=e.toneMapping,this.material.defines={},v.getTransfer(this._outputColorSpace)===`srgb`&&(this.material.defines.SRGB_TRANSFER=``),this._toneMapping===1?this.material.defines.LINEAR_TONE_MAPPING=``:this._toneMapping===2?this.material.defines.REINHARD_TONE_MAPPING=``:this._toneMapping===3?this.material.defines.CINEON_TONE_MAPPING=``:this._toneMapping===4?this.material.defines.ACES_FILMIC_TONE_MAPPING=``:this._toneMapping===6?this.material.defines.AGX_TONE_MAPPING=``:this._toneMapping===7?this.material.defines.NEUTRAL_TONE_MAPPING=``:this._toneMapping===5&&(this.material.defines.CUSTOM_TONE_MAPPING=``),this.material.needsUpdate=!0),this.renderToScreen===!0?(e.setRenderTarget(null),this._fsQuad.render(e)):(e.setRenderTarget(t),this.clear&&e.clear(e.autoClearColor,e.autoClearDepth,e.autoClearStencil),this._fsQuad.render(e))}dispose(){this.material.dispose(),this._fsQuad.dispose()}},F={turquesa:`#7cf5e1`,ambar:`#ffb21a`,marca:`#2152e8`},I={canteiro:1,borda:8,calcada:12.2,z0:90,comprimento:520},we=[{x:2.75,dir:-1,v:11.2},{x:6.25,dir:-1,v:13.1},{x:-2.75,dir:1,v:12.4},{x:-6.25,dir:1,v:10.6}],L={radar:0},Te=40,Ee={radar:.52};function De(e){let t=e>>>0;return()=>{t=t+1831565813|0;let e=Math.imul(t^t>>>15,1|t);return e=e+Math.imul(e^e>>>7,61|e)^e,((e^e>>>14)>>>0)/4294967296}}var Oe=(e,t,n)=>{let i=r.clamp((n-e)/(t-e),0,1);return i*i*(3-2*i)},R=(e,t=1)=>new _(e).multiplyScalar(t);function ke(e,t,n){let r=document.createElement(`canvas`);return r.width=e,r.height=t,n(r.getContext(`2d`),e,t),r}function z(e,t,n){return ke(e,t,r=>{let i=r.createImageData(e,t);for(let r=0;r<t;r++)for(let a=0;a<e;a++){let o=n(a/(e-1),1-r/(t-1),a,r);i.data.set(o,(r*e+a)*4)}r.putImageData(i,0,0)})}function B(e,{cor:t=!0,repetir:n=null}={}){let r=new re(e);return t&&(r.colorSpace=m),n&&(r.wrapS=p,r.wrapT=p,r.repeat.set(...n)),r.anisotropy=4,r}function V(e,t,n){let i=De(n),a=new Float32Array(e*e),o=0;for(let[n,s]of t){o+=s;let t=Array.from({length:n*n},i),c=(e,r)=>t[(r+n)%n*n+(e+n)%n];for(let t=0;t<e;t++)for(let i=0;i<e;i++){let o=i/e*n,l=t/e*n,u=Math.floor(o),d=Math.floor(l),f=Oe(0,1,o-u),p=Oe(0,1,l-d),ee=r.lerp(c(u,d),c(u+1,d),f),m=r.lerp(c(u,d+1),c(u+1,d+1),f);a[t*e+i]+=r.lerp(ee,m,p)*s}}for(let e=0;e<a.length;e++)a[e]/=o;return a}function H(e,t){let n=e.index?e.toNonIndexed():e,r=t instanceof _?t:new _(t),i=n.attributes.position.count,a=new Float32Array(i*3);for(let e=0;e<i;e++)a.set([r.r,r.g,r.b],e*3);return n.setAttribute(`color`,new x(a,3)),n.attributes.uv||n.setAttribute(`uv`,new x(new Float32Array(i*2),2)),n}var U=e=>fe(e.map(e=>e.attributes.color?e:H(e,`#ffffff`))),W=(e,t,n,r=0,i=0,a=0)=>new h(e,t,n).translate(r,i,a),G=(e,t,n,r,i=0,a=0,o=0)=>new le(e,t,n,r).translate(i,a,o);function Ae(e){return new o(e.map(([e,t])=>new y(e,t)))}function je(e,t,n=.05,r=6){let i=t-2*n,a=new ie(e,{depth:i,bevelEnabled:n>0,bevelThickness:n,bevelSize:n,bevelOffset:-n,bevelSegments:2,curveSegments:r});return a.translate(0,0,-i/2),a}function Me(e,t,n){let r=new o,i=-e/2,a=-t/2;return r.moveTo(i+n,a),r.lineTo(i+e-n,a),r.absarc(i+e-n,a+n,n,-Math.PI/2,0,!1),r.lineTo(i+e,a+t-n),r.absarc(i+e-n,a+t-n,n,0,Math.PI/2,!1),r.lineTo(i+n,a+t),r.absarc(i+n,a+t-n,n,Math.PI/2,Math.PI,!1),r.lineTo(i,a+n),r.absarc(i+n,a+n,n,Math.PI,Math.PI*1.5,!1),r}function Ne(e,t,n){let r=n/2,i=new o;return i.absarc(0,0,e+r,-t,t,!1),i.absarc(e*Math.cos(t),e*Math.sin(t),r,t,t+Math.PI,!1),i.absarc(0,0,e-r,t,-t,!0),i.absarc(e*Math.cos(t),-e*Math.sin(t),r,Math.PI-t,2*Math.PI-t,!1),i}function Pe(e,t,n,r,i){let a=[...e,...t,...n,...e,...n,...r],o=[0,0,1,0,1,1,0,0,1,1,0,1],s=new T;return s.setAttribute(`position`,new x(new Float32Array(a),3)),s.setAttribute(`uv`,new x(new Float32Array(o),2)),s.computeVertexNormals(),H(s,i)}function Fe(){let e=B(z(32,128,(e,t)=>{let n=255*(1-t)**1.8*Math.sin(Math.PI*e)**1.4;return[n,n,n,255]}),{cor:!1}),t=B(z(128,128,(e,t)=>{let n=Math.hypot(e-.5,t-.5)*2,r=255*Math.exp(-n*n*3.2)*(1-Oe(.8,1,n));return[r,r,r,255]}),{cor:!1}),n=V(128,[[4,.5],[16,.3],[64,.2]],7),i=V(128,[[3,.7],[9,.3]],11),a=De(5),o=B(z(128,128,(e,t,r,i)=>{let o=.78+n[i*128+r]*.36+(a()-.5)*.16;return[26*o,30*o,37*o,255]}),{repetir:[9,60]}),s=B(z(128,128,(e,t,n,o)=>{let s=Oe(.42,.66,i[o*128+n]),c=255*r.lerp(.78,.42,s)+(a()-.5)*16;return[c,c,c,255]}),{cor:!1,repetir:[9,60]}),c=De(23);return{rastro:e,mancha:t,asfalto:o,rugosidade:s,janelas:B(ke(512,512,e=>{e.fillStyle=`#07090d`,e.fillRect(0,0,512,512);let t=[`#ffc98a`,`#ffb070`,`#ffd9a8`,`#d6e4ff`,`#9fb8ff`];for(let n=0;n<16;n++){let r=c()>.18;for(let i=0;i<16;i++){let a=i*32,o=n*32;if(n===15){let n=c()>.55;e.fillStyle=n?t[Math.floor(c()*3)]:`#0c1017`,e.globalAlpha=n?.25+c()*.3:1,e.fillRect(a+1,o+10,30,20),e.globalAlpha=1;continue}let s=r&&c()<.24;e.fillStyle=s?t[Math.floor(c()*t.length)]:`#0b0f16`,e.globalAlpha=s?.2+c()*.55:1,e.fillRect(a+10,o+11,13,14),e.globalAlpha=1}}}),{repetir:[1,1]})}}var Ie={0:[`01110`,`10001`,`10011`,`10101`,`11001`,`10001`,`01110`],1:[`00100`,`01100`,`00100`,`00100`,`00100`,`00100`,`01110`],2:[`01110`,`10001`,`00001`,`00010`,`00100`,`01000`,`11111`],3:[`11110`,`00001`,`00001`,`01110`,`00001`,`00001`,`11110`],4:[`00010`,`00110`,`01010`,`10010`,`11111`,`00010`,`00010`],5:[`11111`,`10000`,`11110`,`00001`,`00001`,`10001`,`01110`],6:[`00110`,`01000`,`10000`,`11110`,`10001`,`10001`,`01110`],7:[`11111`,`00001`,`00010`,`00100`,`01000`,`01000`,`01000`],8:[`01110`,`10001`,`10001`,`01110`,`10001`,`10001`,`01110`],9:[`01110`,`10001`,`10001`,`01111`,`00001`,`00010`,`01100`],"-":[`00000`,`00000`,`00000`,`11111`,`00000`,`00000`,`00000`]};function Le(e,t){let n=e.canvas.width,r=e.canvas.height;e.fillStyle=`#030405`,e.fillRect(0,0,n,r);let i=t==null?`--`:String(t).padStart(2,` `),a=t!=null&&t>Te;for(let t=0;t<2;t++){let n=Ie[i[t]]??Ie[`-`];for(let r=0;r<7;r++)for(let o=0;o<5;o++){let s=i[t]!==` `&&n[r][o]===`1`;e.fillStyle=s?a?`#ff3a24`:F.ambar:`#1c1206`,e.beginPath(),e.arc(16+t*76+o*13,25+r*13,s?5.2:4.2,0,Math.PI*2),e.fill()}}e.fillStyle=a?`#ff3a24`:F.ambar,e.font=`800 22px Overpass, system-ui, sans-serif`,e.textBaseline=`alphabetic`,e.fillText(`km/h`,184,103)}var Re=`
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
  }`;function ze(e,t){return new C({uniforms:w.merge([ce.fog,{cor:{value:R(e)},intensidade:{value:t}}]),vertexShader:`
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
      ${Re}
      void main() {
        float borda = pow(abs(dot(normalize(vNormal), normalize(vVista))), 1.8);
        float queda = mix(0.06, 1.0, pow(vAltura, 2.2));
        float a = intensidade * borda * queda * (1.0 - neblina());
        gl_FragColor = vec4(cor * a, 1.0);
      }`,transparent:!0,depthWrite:!1,blending:2,side:2,fog:!0})}var Be={uniforms:{tDiffuse:{value:null},tempo:{value:0},grao:{value:.045},aspecto:{value:2.39}},vertexShader:`
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
    }`};function K({celular:i}){let a;try{a=new ue({antialias:!1,alpha:!1,powerPreference:`high-performance`,failIfMajorPerformanceCaveat:!0})}catch{return null}a.toneMapping=4,a.toneMappingExposure=1.05,a.shadowMap.enabled=!i,a.shadowMap.type=1,a.setClearColor(`#05080d`,1);let o=a.domElement;o.setAttribute(`aria-hidden`,`true`);let p=Fe(),m=new s,re=new _(`#0d1626`);m.fog=new de(re,.0125);let v=new c(30,2.39,.1,1400),ce=new b(new te(1e3,32,16),new C({uniforms:{zenite:{value:R(`#02040a`)},horizonte:{value:re.clone()},cidade:{value:R(`#3b2c2a`,.7)}},vertexShader:`
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
      }`,side:1,depthWrite:!1}));ce.renderOrder=-1,m.add(ce);let x=new f(`#2a4170`,`#07080b`,.6),w=new se(`#7f97cf`,.38);w.position.set(-40,50,-80);let T=new d(`#ffd6a3`,0,40,.75,.55,1.6);T.castShadow=!i,T.shadow.mapSize.set(1024,1024),T.shadow.bias=-4e-4,T.shadow.normalBias=.012,T.shadow.camera.near=1,T.shadow.camera.far=40;let E=new t(F.ambar,0,14,2),D=new t(F.turquesa,0,14,2);m.add(x,w,T,T.target,E,D);let pe=new b(new A(120,I.comprimento+200).rotateX(-Math.PI/2).translate(0,0,I.z0-I.comprimento/2),new l({map:p.asfalto,roughnessMap:p.rugosidade,roughness:1,metalness:.08}));pe.receiveShadow=!0,p.asfalto.repeat.set(120/9,(I.comprimento+200)/9),p.rugosidade.repeat.copy(p.asfalto.repeat);let j=I.comprimento+200,M=I.z0-I.comprimento/2,me=new _(`#3a414c`),he=new _(`#8d96a3`),N=I.calcada-I.borda-.18,_e=U([H(W(N,.16,j,I.borda+.18+N/2,.08,M),me),H(W(N,.16,j,-I.borda-.18-N/2,.08,M),me),H(W(.18,.18,j,I.borda+.09,.09,M),he),H(W(.18,.18,j,-I.borda-.09,.09,M),he),H(W(I.canteiro*2-.24,.22,j,0,.11,M),new _(`#1a2a20`)),H(W(.12,.24,j,I.canteiro-.06,.12,M),he),H(W(.12,.24,j,-I.canteiro+.06,.12,M),he)]),ve=new b(_e,new l({vertexColors:!0,roughness:.82})),xe=new b(U([-1,1].map(e=>H(W(60,.14,j,e*(I.calcada+30),.07,M),`#151a20`))),new l({vertexColors:!0,roughness:.95}));xe.receiveShadow=!0,ve.receiveShadow=!0;let P=[],Ee=new _(`#c9cfd8`);for(let e of[1,-1]){P.push(W(.14,.02,j,e*(I.borda-.35),.006,M));for(let t=I.z0;t>I.z0-I.comprimento;t-=12)P.push(W(.13,.02,4,e*4.5,.006,t))}for(let e of[1,-1])for(let t=I.canteiro+.55;t<I.borda-.4;t+=.95)P.push(W(.48,.02,3.4,e*t,.007,L.radar-9));P.push(W(I.borda-I.canteiro-.3,.02,.4,(I.borda+I.canteiro)/2,.007,L.radar-5.8));let z=new b(U(P.map(e=>H(e,Ee))),new l({vertexColors:!0,roughness:.55}));z.receiveShadow=!0;let V=[],Ie=[];for(let t of we.slice(0,2)){for(let e of[13.5,9.5])for(let[n,r,i,a]of[[2,.06,0,-.8],[2,.06,0,.8],[.06,1.6,-1,0],[.06,1.6,1,0]])Ie.push(W(n,.02,r,t.x+i,.008,L.radar+e+a));let n=new b(new A(2.6,6.2).rotateX(-Math.PI/2),new e({color:R(F.turquesa,1.4),alphaMap:p.mancha,transparent:!0,opacity:0,blending:2,depthWrite:!1}));n.position.set(t.x,.03,L.radar+11.5),V.push(n)}let Re=new b(U(Ie.map(e=>H(e,`#05070a`))),new e({vertexColors:!0}));m.add(pe,ve,xe,z,Re,...V);let K=De(41),Ve=[];for(let e of[1,-1]){let t=I.z0+40;for(;t>I.z0-I.comprimento-120;){let n=10+K()*16,r=10+K()*14,i=K()<.18,a=i?26+K()*22:7+K()*13,o=I.calcada+2+K()*5+(i?8:0),s=new h(r,a,n),c=Math.floor(K()*16)/16,l=s.attributes.uv,u=[[n,a],[n,a],[0,0],[0,0],[r,a],[r,a]];for(let e=0;e<6;e++)for(let t=0;t<4;t++){let n=e*4+t,[r,i]=u[e];r?l.setXY(n,l.getX(n)*r/35.2+c,l.getY(n)*i/51.2):l.setXY(n,.995,.5)}s.translate(e*(o+r/2),a/2,t-n/2),Ve.push(s),t-=n+1+K()*4}}for(let e=0;e<26;e++){let t=14+K()*24,n=18+K()*50,r=new h(t,n,16),i=r.attributes.uv;for(let e=0;e<i.count;e++)i.setXY(e,i.getX(e)*t/35.2,i.getY(e)*n/51.2);r.translate(-160+e*13+K()*6,n/2,I.z0-I.comprimento-140-K()*80),Ve.push(r)}let He=new b(fe(Ve),new e({map:p.janelas,color:R(`#ffffff`,.85)}));m.add(He);let q=[],Ue=(e,t)=>!(e>0&&Math.abs(t-L.radar)<12);for(let e=I.z0+10;e>I.z0-I.comprimento-40;e-=32)Ue(1,e)&&q.push({x:I.borda+.7,z:e,lado:1}),Ue(-1,e-16)&&q.push({x:-I.borda-.7,z:e-16,lado:-1});q.push({x:I.borda+.7,z:L.radar-12,lado:1});let We=2.6,Ge=8.6,Ke=U([H(G(.09,.14,Ge,10,0,Ge/2,0),`#4a525e`),H(G(.05,.05,We,6).rotateZ(Math.PI/2).translate(-2.6/2,8.5,0),`#4a525e`),H(W(.95,.16,.42,-2.6,8.5,0),`#2b3039`)]),qe=new A(.82,.32).rotateX(Math.PI/2).translate(-2.6,8.41,0),Je=new le(.3,4.2,8.299999999999999,28,1,!0).translate(-2.6,8.299999999999999/2,0),Ye=new A(14,14).rotateX(-Math.PI/2).translate(-2.6,.025,0),Xe=new O(Ke,new l({vertexColors:!0,roughness:.5,metalness:.6}),q.length),Ze=new O(qe,new e({color:R(`#ffe2bd`,9),side:2}),q.length),Qe=new O(Je,ze(`#ffd9a8`,.1),q.length),$e=new O(Ye,new e({color:R(`#ffcf96`,.2),alphaMap:p.mancha,transparent:!0,blending:2,depthWrite:!1}),q.length),et=(e,t)=>{let n=q.filter(t=>t.lado===e).reduce((e,n)=>Math.abs(n.z-t)<Math.abs(e.z-t)?n:e);return new g(n.x-e*We,8.299999999999999,n.z)},J=new u;q.forEach((e,t)=>{J.position.set(e.x,0,e.z),J.rotation.set(0,e.lado>0?0:Math.PI,0),J.updateMatrix();for(let e of[Xe,Ze,Qe,$e])e.setMatrixAt(t,J.matrix)}),Xe.castShadow=!0;let Y=new O(new A(1,1).rotateX(-Math.PI/2),new e({color:`#ffffff`,alphaMap:p.mancha,transparent:!0,blending:2,depthWrite:!1,fog:!1}),q.length);Y.instanceMatrix.setUsage(ae),Y.frustumCulled=!1;let tt=R(`#ffc98e`,.42),nt=new _,rt=m.fog.density;function it(e){q.forEach((t,n)=>{let i=t.x-t.lado*We,a=e.x-i,o=e.z-t.z,s=Math.hypot(a,o),c=8.4/(8.4+Math.max(e.y,.3)),l=r.clamp(s*.2*r.clamp(2.5/Math.max(e.y,.5),.15,1.6),.7,9);J.position.set(i+a*c,.03,t.z+o*c),J.rotation.set(0,Math.atan2(a,o),0),J.scale.set(.55,1,l),J.updateMatrix(),Y.setMatrixAt(n,J.matrix);let u=Math.exp(-((rt*s)**2));Y.setColorAt(n,nt.copy(tt).multiplyScalar(u))}),J.scale.set(1,1,1),Y.instanceMatrix.needsUpdate=!0,Y.instanceColor.needsUpdate=!0}m.add(Xe,Ze,Qe,$e,Y);let at=U([H(G(.1,.16,2.6,6,0,1.3,0),`#1d1712`),H(new n(1.5,1).translate(0,3.4,0),`#13261b`),H(new n(1.1,1).translate(.8,3,.4),`#102018`),H(new n(1,1).translate(-.7,3.1,-.5),`#152a1e`)]),ot=[];for(let e=I.z0;e>I.z0-I.comprimento;e-=13+K()*9)e>-18&&e<60||ot.push(e);let st=new O(at,new l({vertexColors:!0,roughness:.95}),ot.length);ot.forEach((e,t)=>{J.position.set(0,.2,e),J.rotation.set(0,K()*6.28,0),J.scale.setScalar(.8+K()*.5),J.updateMatrix(),st.setMatrixAt(t,J.matrix)}),J.scale.setScalar(1),st.castShadow=!0,m.add(st);let ct=U([H(je(Ae([[-2.2,.3],[2.12,.3],[2.22,.5],[2.2,.78],[1.95,.9],[1.15,1],[-1.55,1.04],[-2.12,.98],[-2.24,.78],[-2.25,.45]]),1.76,.07),`#ffffff`),H(je(Ae([[1.16,.98],[.45,1.41],[-.78,1.44],[-1.56,1.03]]),1.6,.06),new _(.025,.028,.035)),...[[-1.38,.78],[-1.38,-.78],[1.4,.78],[1.4,-.78]].map(([e,t])=>H(G(.33,.33,.22,14).rotateX(Math.PI/2).translate(e,.33,t),new _(.01,.01,.012)))]).rotateY(Math.PI/2),lt=U([H(je(Me(12,2.75,.32),2.5,.08,4).translate(0,1.72,0),`#ffffff`),...[[-4.2,1.1],[-4.2,-1.1],[3.6,1.1],[3.6,-1.1]].map(([e,t])=>H(G(.5,.5,.3,14).rotateX(Math.PI/2).translate(e,.5,t),new _(.01,.01,.012)))]).rotateY(Math.PI/2),ut=(e,t,n,r,i=[])=>U([...[-1,1].map(n=>H(W(.36,.13,.06,n*r,t,-e-.01),new _(6,6,6.6))),...[-1,1].map(t=>H(W(.34,.12,.06,t*r,n,e+.01),new _(3.4,.14,.08))),...i]),dt=ut(2.22,.72,.86,.6),ft=[];for(let e of[-1,1])for(let t=-5.2;t<4.4;t+=1.45)ft.push(H(W(.02,.85,1.25,e*1.26,2.15,t),new _(.95,.78,.52)));let pt=ut(6.02,.75,1,.9,[...ft,H(W(1.7,.28,.04,0,2.86,-6.03),new _(3.2,1.6,.2))]),mt=(e,t,n,r,i,a)=>{let o=[];for(let a of[-1,1]){let s=a*r;for(let[r,a,c]of[[t,-e,new _(1.7,1.75,2)],[n,e,new _(2.2,.1,.06)]]){let e=a+i;o.push(Pe([s-.09,r,a],[s+.09,r,a],[s+.09,r,e],[s-.09,r,e],c)),o.push(Pe([s,r-.07,a],[s,r+.07,a],[s,r+.07,e],[s,r-.07,e],c))}}let s=-e-.4,c=s-a;return o.push(Pe([-.9,.03,s],[.9,.03,s],[2.6,.03,c],[-2.6,.03,c],new _(.75,.7,.6))),U(o)},ht=mt(2.22,.72,.86,.6,9,15),gt=mt(6.02,.75,1,.9,9,17),_t=new l({vertexColors:!0,roughness:.32,metalness:.55}),vt=new e({vertexColors:!0}),yt=new e({vertexColors:!0,alphaMap:p.rastro,transparent:!0,blending:2,depthWrite:!1,side:2}),bt=[`#d9dde3`,`#9aa3ad`,`#5c6470`,`#1b1f26`,`#7a1d1d`,`#1f3357`,`#c8ccd2`,`#2c3a2f`],xt=[];we.forEach((e,t)=>{let n=K()*40;for(let r=0;r<7;r++){let i=t===2&&(r===1||r===4);xt.push({faixa:e,s:n,onibus:i,cor:new _(bt[Math.floor(K()*bt.length)]),jitter:Math.round((K()-.5)*7)}),n+=I.comprimento/7*(.7+K()*.6)}});let St=xt.filter(e=>!e.onibus),Ct=xt.filter(e=>e.onibus),X=(e,t,n,r)=>{let i=new O(e,t,n.length);return i.instanceMatrix.setUsage(ae),i.castShadow=r,i.frustumCulled=!1,i},wt=[X(ct,_t,St,!0),X(dt,vt,St,!1),X(ht,yt,St,!1)],Tt=[X(lt,_t,Ct,!0),X(pt,vt,Ct,!1),X(gt,yt,Ct,!1)];St.forEach((e,t)=>wt[0].setColorAt(t,e.cor)),Ct.forEach((e,t)=>Tt[0].setColorAt(t,t?new _(`#e8e2d0`):new _(`#d6dce4`))),m.add(...wt,...Tt);let Et=(e,t)=>{let n=(e.s+e.faixa.v*t)%I.comprimento;return e.faixa.dir<0?I.z0-n:I.z0-I.comprimento+n};function Dt(e){for(let[t,n]of[[St,wt],[Ct,Tt]]){t.forEach((t,r)=>{J.position.set(t.faixa.x,0,Et(t,e)),J.rotation.set(0,t.faixa.dir<0?0:Math.PI,0),J.updateMatrix();for(let e of n)e.setMatrixAt(r,J.matrix)});for(let e of n)e.instanceMatrix.needsUpdate=!0}}let Ot=document.fonts?.load(`800 64px Overpass`).catch(()=>{})??Promise.resolve(),kt=new l({color:`#1f2733`,roughness:.48,metalness:.65}),At=new l({color:`#0d1117`,roughness:.4,metalness:.3}),jt=new e({color:R(F.turquesa,3)});function Mt(t=1,n=!0){let r=new ne,i=new b(U([H(je(Me(.95,1.05,.08),.6,.03).translate(0,.525,0),`#1f2733`),H(W(.34,1.6,.26,0,1.8,0),`#26303d`),H(je(Me(1.56,.9,.1),.32,.04).translate(0,3,0),`#141a22`),H(W(.6,.26,.34,0,3.6,0),`#1a212b`)]),new l({vertexColors:!0,roughness:.45,metalness:.6}));i.castShadow=!0,i.receiveShadow=!0;let a=new b(W(1.2,.06,.85,0,.03,0),new l({color:`#4a515c`,roughness:.9}));a.receiveShadow=!0;let o=new b(new A(1.9,1.5).rotateX(-Math.PI/2),new e({color:`#000000`,alphaMap:p.mancha,transparent:!0,opacity:.85,depthWrite:!1}));o.position.y=.065,r.add(a,o);let s=document.createElement(`canvas`);s.width=256,s.height=128;let c=s.getContext(`2d`);Le(c,null);let u=B(s),d=new b(new A(1.36,.68),new e({map:u,color:R(`#ffffff`,3.4)}));d.position.set(0,3,.165);let f=new b(new h(.44,.035,.02),jt);f.position.set(0,3.52,.175);let ee=new b(new le(.075,.075,.05,20).rotateX(Math.PI/2),At);if(ee.position.set(.16,3.62,.18),r.add(i,d,f,ee),n){let e=ke(256,256,()=>{}),t=()=>{let t=e.getContext(`2d`);t.fillStyle=`#ffffff`,t.beginPath(),t.arc(128,128,124,0,Math.PI*2),t.fill(),t.lineWidth=30,t.strokeStyle=`#d4161c`,t.beginPath(),t.arc(128,128,106,0,Math.PI*2),t.stroke(),t.fillStyle=`#111111`,t.textAlign=`center`,t.font=`800 104px Overpass, system-ui, sans-serif`,t.fillText(String(Te),128,150),t.font=`700 30px Overpass, system-ui, sans-serif`,t.fillText(`km/h`,128,190),n.needsUpdate=!0},n=B(e);t(),Ot.then(t);let i=new b(new le(.44,.44,.03,40).rotateX(Math.PI/2).rotateZ(Math.PI/2),new l({map:n,emissiveMap:n,emissive:`#ffffff`,emissiveIntensity:.12,roughness:.4}));i.position.set(0,4.25,0);let a=new b(new h(.08,.5,.06),kt);a.position.set(0,3.88,-.04),i.castShadow=!0,r.add(i,a)}return r.scale.setScalar(t),{g:r,ctx:c,texPainel:u,valor:void 0}}let Z=Mt();Z.g.position.set(I.borda+1.5,.16,L.radar),Z.g.rotation.y=-.18;let Nt=et(1,L.radar),Pt=[0,1,2].map(()=>{let t=new b(new ie(Ne(.4,.7,.045),{depth:.02,bevelEnabled:!1,curveSegments:12}),new e({color:R(F.turquesa,1.6),transparent:!0,opacity:0,blending:2,depthWrite:!1}));return t.rotation.y=-2.2,t.position.set(I.borda+.95,3.72,L.radar+.95),m.add(t),t});m.add(Z.g);let Ft=e=>new oe(e.map(e=>new g(...e)),!1,`centripetal`),It={radar:{pos:Ft([[-4,26,52],[1,15,32],[6.5,6,18],[10.7,2,7.2]]),alvo:Ft([[5,0,-8],[7,1,-4],[8.6,2.2,-1],[9.1,2.95,-.4]])}},Q=new g,Lt=new g,Rt=new g(0,0,1),zt=new ee,Bt=new S(1,1,{type:k,samples:i?0:4}),$=new ye(a,Bt);$.addPass(new be(m,v));let Vt=new Se(new y(256,256),.62,.42,.92);$.addPass(Vt),$.addPass(new Ce);let Ht=new ge(Be);$.addPass(Ht),i&&(Ht.uniforms.grao.value=.035),T.color.set(`#ffd2a0`),T.position.copy(Nt),T.target.position.set(I.borda+1.5,0,L.radar),T.angle=.85,T.distance=30,T.intensity=260,T.target.updateMatrixWorld(),E.color.set(F.ambar),E.position.set(I.borda+1.2,3,L.radar+1.2),D.position.set(I.borda+1.2,3.55,L.radar+.6),D.intensity=2.2;let Ut=!1;function Wt(e,{p:t,t:n,parado:i}){let o=r.clamp((t-.04)/.56,0,1),s=Oe(0,1,o)*.7+o*.3,c=It[e];c.pos.getPoint(s,Q),c.alvo.getPoint(s,Lt),i||(Q.x+=Math.sin(n*.61)*.05+Math.sin(n*1.37)*.02,Q.y+=Math.sin(n*.83+1.3)*.04,Lt.x+=Math.sin(n*.47+.4)*.05),v.position.copy(Q),v.lookAt(Lt),ce.position.copy(Q),it(Q),zt.setFromAxisAngle(Rt,(1-o)*.04),v.quaternion.multiply(zt),Dt(n);let l=I.z0-(L.radar+11.5),u=-99;for(let e of xt){if(e.faixa.dir>0)continue;let t=Math.floor((e.faixa.v*n+e.s-l)/I.comprimento),r=(l-e.s+t*I.comprimento)/e.faixa.v;r<=n&&r>u&&(u=r,Z.ultima=Math.round(e.faixa.v*3.6)+e.jitter,Z.faixaLida=we.indexOf(e.faixa))}let d=n-u,f=i?38:d<4.5?Z.ultima:null;f!==Z.valor&&(Le(Z.ctx,f),Z.texPainel.needsUpdate=!0,Z.valor=f),V.forEach((e,t)=>{e.material.opacity=!i&&Z.faixaLida===t?Math.max(0,1-d/.9):0}),Pt.forEach((e,t)=>{let r=i?.35+t*.22:(n*.8+t/3)%1;e.scale.setScalar(.8+r*2.2),e.material.opacity=Math.sin(Math.PI*Math.min(r*1.3,1))*.75});let p=f!=null&&f>Te;E.color.set(p?`#ff3a24`:F.ambar),E.intensity=f==null?1.2:5,Ht.uniforms.tempo.value=i?0:n,Ut?a.render(m,v):$.render()}return{canvas:o,preparar:()=>(Dt(0),it(v.position),a.compileAsync(m,v).catch(()=>{})),tamanho(e,t,n){let i=Math.max(1,Math.round(e)),o=Math.max(1,Math.round(t));a.setPixelRatio(n),a.setSize(i,o,!1),$.setPixelRatio(n),$.setSize(i,o),Vt.setSize(Math.round(i*n/2),Math.round(o*n/2));let s=i/o;v.aspect=s;let c=2*Math.atan(Math.tan(r.degToRad(30)/2)*2.39);v.fov=r.radToDeg(2*Math.atan(Math.tan(c/2)/Math.max(s,1))),v.updateProjectionMatrix(),Ht.uniforms.aspecto.value=s},quadro:Wt,simplificar(){Ut=!0}}}export{Ee as REPOUSO,K as criarMundo};