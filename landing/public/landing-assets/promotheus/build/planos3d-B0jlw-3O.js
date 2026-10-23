import{$ as e,A as t,B as n,C as r,D as i,F as a,H as o,J as s,K as c,L as l,M as u,P as d,Q as f,S as ee,T as te,U as p,V as ne,W as m,Z as re,a as h,at as g,b as ie,c as ae,d as _,et as oe,f as v,g as se,h as ce,it as y,k as b,l as le,m as ue,n as de,nt as fe,o as pe,ot as me,p as he,q as x,r as ge,rt as S,s as C,tt as w,v as T,w as E,x as _e,y as ve,z as D}from"./three.module-BROWA2qQ.js";function ye(e,t=!1){let n=e[0].index!==null,r=new Set(Object.keys(e[0].attributes)),i=new Set(Object.keys(e[0].morphAttributes)),a={},o={},s=e[0].morphTargetsRelative,c=new C,l=0;for(let u=0;u<e.length;++u){let d=e[u],f=0;if(n!==(d.index!==null))return console.error(`THREE.BufferGeometryUtils: .mergeGeometries() failed with geometry at index `+u+`. All geometries must have compatible attributes; make sure index attribute exists among all geometries, or in none of them.`),null;for(let e in d.attributes){if(!r.has(e))return console.error(`THREE.BufferGeometryUtils: .mergeGeometries() failed with geometry at index `+u+`. All geometries must have compatible attributes; make sure "`+e+`" attribute exists among all geometries, or in none of them.`),null;a[e]===void 0&&(a[e]=[]),a[e].push(d.attributes[e]),f++}if(f!==r.size)return console.error(`THREE.BufferGeometryUtils: .mergeGeometries() failed with geometry at index `+u+`. Make sure all geometries have the same number of attributes.`),null;if(s!==d.morphTargetsRelative)return console.error(`THREE.BufferGeometryUtils: .mergeGeometries() failed with geometry at index `+u+`. .morphTargetsRelative must be consistent throughout all geometries.`),null;for(let e in d.morphAttributes){if(!i.has(e))return console.error(`THREE.BufferGeometryUtils: .mergeGeometries() failed with geometry at index `+u+`.  .morphAttributes must be consistent throughout all geometries.`),null;o[e]===void 0&&(o[e]=[]),o[e].push(d.morphAttributes[e])}if(t){let e;if(n)e=d.index.count;else if(d.attributes.position!==void 0)e=d.attributes.position.count;else return console.error(`THREE.BufferGeometryUtils: .mergeGeometries() failed with geometry at index `+u+`. The geometry must have either an index or a position attribute`),null;c.addGroup(l,e,u),l+=e}}if(n){let t=0,n=[];for(let r=0;r<e.length;++r){let i=e[r].index;for(let e=0;e<i.count;++e)n.push(i.getX(e)+t);t+=e[r].attributes.position.count}c.setIndex(n)}for(let e in a){let t=O(a[e]);if(!t)return console.error(`THREE.BufferGeometryUtils: .mergeGeometries() failed while trying to merge the `+e+` attribute.`),null;c.setAttribute(e,t)}for(let e in o){let t=o[e][0].length;if(t!==0){c.morphAttributes=c.morphAttributes||{},c.morphAttributes[e]=[];for(let n=0;n<t;++n){let t=[];for(let r=0;r<o[e].length;++r)t.push(o[e][r][n]);let r=O(t);if(!r)return console.error(`THREE.BufferGeometryUtils: .mergeGeometries() failed while trying to merge the `+e+` morphAttribute.`),null;c.morphAttributes[e].push(r)}}}return c}function O(e){let t,n,r,i=-1,a=0;for(let o=0;o<e.length;++o){let s=e[o];if(t===void 0&&(t=s.array.constructor),t!==s.array.constructor)return console.error(`THREE.BufferGeometryUtils: .mergeAttributes() failed. BufferAttribute.array must be of consistent array types across matching attributes.`),null;if(n===void 0&&(n=s.itemSize),n!==s.itemSize)return console.error(`THREE.BufferGeometryUtils: .mergeAttributes() failed. BufferAttribute.itemSize must be consistent across matching attributes.`),null;if(r===void 0&&(r=s.normalized),r!==s.normalized)return console.error(`THREE.BufferGeometryUtils: .mergeAttributes() failed. BufferAttribute.normalized must be consistent across matching attributes.`),null;if(i===-1&&(i=s.gpuType),i!==s.gpuType)return console.error(`THREE.BufferGeometryUtils: .mergeAttributes() failed. BufferAttribute.gpuType must be consistent across matching attributes.`),null;a+=s.count*n}let o=new t(a),s=new pe(o,n,r),c=0;for(let t=0;t<e.length;++t){let r=e[t];if(r.isInterleavedBufferAttribute){let e=c/n;for(let t=0,i=r.count;t<i;t++)for(let i=0;i<n;i++){let n=r.getComponent(t,i);s.setComponent(t+e,i,n)}}else o.set(r.array,c);c+=r.count*n}return i!==void 0&&(s.gpuType=i),s}var be={name:`CopyShader`,uniforms:{tDiffuse:{value:null},opacity:{value:1}},vertexShader:`

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


		}`},k=class{constructor(){this.isPass=!0,this.enabled=!0,this.needsSwap=!0,this.clear=!1,this.renderToScreen=!1}setSize(){}render(){console.error(`THREE.Pass: .render() must be implemented in derived pass.`)}dispose(){}},A=new a(-1,1,1,-1,0,1),xe=new class extends C{constructor(){super(),this.setAttribute(`position`,new T([-1,3,0,-1,-1,0,3,-1,0],3)),this.setAttribute(`uv`,new T([0,2,0,0,2,0],2))}},j=class{constructor(e){this._mesh=new b(xe,e)}dispose(){this._mesh.geometry.dispose()}render(e){e.render(this._mesh,A)}get material(){return this._mesh.material}set material(e){this._mesh.material=e}},Se=class extends k{constructor(e,t=`tDiffuse`){super(),this.textureID=t,this.uniforms=null,this.material=null,e instanceof x?(this.uniforms=e.uniforms,this.material=e):e&&(this.uniforms=S.clone(e.uniforms),this.material=new x({name:e.name===void 0?`unspecified`:e.name,defines:Object.assign({},e.defines),uniforms:this.uniforms,vertexShader:e.vertexShader,fragmentShader:e.fragmentShader})),this._fsQuad=new j(this.material)}render(e,t,n){this.uniforms[this.textureID]&&(this.uniforms[this.textureID].value=n.texture),this._fsQuad.material=this.material,this.renderToScreen?(e.setRenderTarget(null),this._fsQuad.render(e)):(e.setRenderTarget(t),this.clear&&e.clear(e.autoClearColor,e.autoClearDepth,e.autoClearStencil),this._fsQuad.render(e))}dispose(){this.material.dispose(),this._fsQuad.dispose()}},Ce=class extends k{constructor(e,t){super(),this.scene=e,this.camera=t,this.clear=!0,this.needsSwap=!1,this.inverse=!1}render(e,t,n){let r=e.getContext(),i=e.state;i.buffers.color.setMask(!1),i.buffers.depth.setMask(!1),i.buffers.color.setLocked(!0),i.buffers.depth.setLocked(!0);let a,o;this.inverse?(a=0,o=1):(a=1,o=0),i.buffers.stencil.setTest(!0),i.buffers.stencil.setOp(r.REPLACE,r.REPLACE,r.REPLACE),i.buffers.stencil.setFunc(r.ALWAYS,a,4294967295),i.buffers.stencil.setClear(o),i.buffers.stencil.setLocked(!0),e.setRenderTarget(n),this.clear&&e.clear(),e.render(this.scene,this.camera),e.setRenderTarget(t),this.clear&&e.clear(),e.render(this.scene,this.camera),i.buffers.color.setLocked(!1),i.buffers.depth.setLocked(!1),i.buffers.color.setMask(!0),i.buffers.depth.setMask(!0),i.buffers.stencil.setLocked(!1),i.buffers.stencil.setFunc(r.EQUAL,1,4294967295),i.buffers.stencil.setOp(r.KEEP,r.KEEP,r.KEEP),i.buffers.stencil.setLocked(!0)}},we=class extends k{constructor(){super(),this.needsSwap=!1}render(e){e.state.buffers.stencil.setLocked(!1),e.state.buffers.stencil.setTest(!1)}},Te=class{constructor(e,t){if(this.renderer=e,this._pixelRatio=e.getPixelRatio(),t===void 0){let n=e.getSize(new y);this._width=n.width,this._height=n.height,t=new me(this._width*this._pixelRatio,this._height*this._pixelRatio,{type:_e}),t.texture.name=`EffectComposer.rt1`}else this._width=t.width,this._height=t.height;this.renderTarget1=t,this.renderTarget2=t.clone(),this.renderTarget2.texture.name=`EffectComposer.rt2`,this.writeBuffer=this.renderTarget1,this.readBuffer=this.renderTarget2,this.renderToScreen=!0,this.passes=[],this.copyPass=new Se(be),this.copyPass.material.blending=0,this.timer=new w}swapBuffers(){let e=this.readBuffer;this.readBuffer=this.writeBuffer,this.writeBuffer=e}addPass(e){this.passes.push(e),e.setSize(this._width*this._pixelRatio,this._height*this._pixelRatio)}insertPass(e,t){this.passes.splice(t,0,e),e.setSize(this._width*this._pixelRatio,this._height*this._pixelRatio)}removePass(e){let t=this.passes.indexOf(e);t!==-1&&this.passes.splice(t,1)}isLastEnabledPass(e){for(let t=e+1;t<this.passes.length;t++)if(this.passes[t].enabled)return!1;return!0}render(e){this.timer.update(),e===void 0&&(e=this.timer.getDelta());let t=this.renderer.getRenderTarget(),n=!1;for(let t=0,r=this.passes.length;t<r;t++){let r=this.passes[t];if(r.enabled!==!1){if(r.renderToScreen=this.renderToScreen&&this.isLastEnabledPass(t),r.render(this.renderer,this.writeBuffer,this.readBuffer,e,n),r.needsSwap){if(n){let t=this.renderer.getContext(),n=this.renderer.state.buffers.stencil;n.setFunc(t.NOTEQUAL,1,4294967295),this.copyPass.render(this.renderer,this.writeBuffer,this.readBuffer,e),n.setFunc(t.EQUAL,1,4294967295)}this.swapBuffers()}Ce!==void 0&&(r instanceof Ce?n=!0:r instanceof we&&(n=!1))}}this.renderer.setRenderTarget(t)}reset(e){if(e===void 0){let t=this.renderer.getSize(new y);this._pixelRatio=this.renderer.getPixelRatio(),this._width=t.width,this._height=t.height,e=this.renderTarget1.clone(),e.setSize(this._width*this._pixelRatio,this._height*this._pixelRatio)}this.renderTarget1.dispose(),this.renderTarget2.dispose(),this.renderTarget1=e,this.renderTarget2=e.clone(),this.writeBuffer=this.renderTarget1,this.readBuffer=this.renderTarget2}setSize(e,t){this._width=e,this._height=t;let n=this._width*this._pixelRatio,r=this._height*this._pixelRatio;this.renderTarget1.setSize(n,r),this.renderTarget2.setSize(n,r);for(let e=0;e<this.passes.length;e++)this.passes[e].setSize(n,r)}setPixelRatio(e){this._pixelRatio=e,this.setSize(this._width,this._height)}dispose(){this.renderTarget1.dispose(),this.renderTarget2.dispose(),this.copyPass.dispose()}},Ee=class extends k{constructor(e,t,n=null,r=null,i=null){super(),this.scene=e,this.camera=t,this.overrideMaterial=n,this.clearColor=r,this.clearAlpha=i,this.clear=!0,this.clearDepth=!1,this.needsSwap=!1,this.isRenderPass=!0,this._oldClearColor=new _}render(e,t,n){let r=e.autoClear;e.autoClear=!1;let i,a;this.overrideMaterial!==null&&(a=this.scene.overrideMaterial,this.scene.overrideMaterial=this.overrideMaterial),this.clearColor!==null&&(e.getClearColor(this._oldClearColor),e.setClearColor(this.clearColor,e.getClearAlpha())),this.clearAlpha!==null&&(i=e.getClearAlpha(),e.setClearAlpha(this.clearAlpha)),this.clearDepth==1&&e.clearDepth(),e.setRenderTarget(this.renderToScreen?null:n),this.clear===!0&&e.clear(e.autoClearColor,e.autoClearDepth,e.autoClearStencil),e.render(this.scene,this.camera),this.clearColor!==null&&e.setClearColor(this._oldClearColor),this.clearAlpha!==null&&e.setClearAlpha(i),this.overrideMaterial!==null&&(this.scene.overrideMaterial=a),e.autoClear=r}},De={name:`LuminosityHighPassShader`,uniforms:{tDiffuse:{value:null},luminosityThreshold:{value:1},smoothWidth:{value:1},defaultColor:{value:new _(0)},defaultOpacity:{value:0}},vertexShader:`

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

		}`},Oe=class e extends k{constructor(e,n=1,r,i){super(),this.strength=n,this.radius=r,this.threshold=i,this.resolution=e===void 0?new y(256,256):new y(e.x,e.y),this.clearColor=new _(0,0,0),this.needsSwap=!1,this.renderTargetsHorizontal=[],this.renderTargetsVertical=[],this.nMips=5;let a=Math.round(this.resolution.x/2),o=Math.round(this.resolution.y/2);this.renderTargetBright=new me(a,o,{type:_e,depthBuffer:!1}),this.renderTargetBright.texture.name=`UnrealBloomPass.bright`,this.renderTargetBright.texture.generateMipmaps=!1;for(let e=0;e<this.nMips;e++){let t=new me(a,o,{type:_e,depthBuffer:!1});t.texture.name=`UnrealBloomPass.h`+e,t.texture.generateMipmaps=!1,this.renderTargetsHorizontal.push(t);let n=new me(a,o,{type:_e,depthBuffer:!1});n.texture.name=`UnrealBloomPass.v`+e,n.texture.generateMipmaps=!1,this.renderTargetsVertical.push(n),a=Math.round(a/2),o=Math.round(o/2)}let s=De;this.highPassUniforms=S.clone(s.uniforms),this.highPassUniforms.luminosityThreshold.value=i,this.highPassUniforms.smoothWidth.value=.01,this.materialHighPassFilter=new x({uniforms:this.highPassUniforms,vertexShader:s.vertexShader,fragmentShader:s.fragmentShader}),this.separableBlurMaterials=[];let c=[6,10,14,18,22];a=Math.round(this.resolution.x/2),o=Math.round(this.resolution.y/2);for(let e=0;e<this.nMips;e++)this.separableBlurMaterials.push(this._getSeparableBlurMaterial(c[e])),this.separableBlurMaterials[e].uniforms.invSize.value=new y(1/a,1/o),a=Math.round(a/2),o=Math.round(o/2);this.compositeMaterial=this._getCompositeMaterial(this.nMips),this.compositeMaterial.uniforms.blurTexture1.value=this.renderTargetsVertical[0].texture,this.compositeMaterial.uniforms.blurTexture2.value=this.renderTargetsVertical[1].texture,this.compositeMaterial.uniforms.blurTexture3.value=this.renderTargetsVertical[2].texture,this.compositeMaterial.uniforms.blurTexture4.value=this.renderTargetsVertical[3].texture,this.compositeMaterial.uniforms.blurTexture5.value=this.renderTargetsVertical[4].texture,this.compositeMaterial.uniforms.bloomStrength.value=n,this.compositeMaterial.uniforms.bloomRadius.value=.1;let l=[1,.8,.6,.4,.2];this.compositeMaterial.uniforms.bloomFactors.value=l,this.bloomTintColors=[new g(1,1,1),new g(1,1,1),new g(1,1,1),new g(1,1,1),new g(1,1,1)],this.compositeMaterial.uniforms.bloomTintColors.value=this.bloomTintColors,this.copyUniforms=S.clone(be.uniforms),this.blendMaterial=new x({uniforms:this.copyUniforms,vertexShader:be.vertexShader,fragmentShader:be.fragmentShader,premultipliedAlpha:!0,blending:2,depthTest:!1,depthWrite:!1,transparent:!0}),this._oldClearColor=new _,this._oldClearAlpha=1,this._basic=new t,this._fsQuad=new j(null)}dispose(){for(let e=0;e<this.renderTargetsHorizontal.length;e++)this.renderTargetsHorizontal[e].dispose();for(let e=0;e<this.renderTargetsVertical.length;e++)this.renderTargetsVertical[e].dispose();this.renderTargetBright.dispose();for(let e=0;e<this.separableBlurMaterials.length;e++)this.separableBlurMaterials[e].dispose();this.compositeMaterial.dispose(),this.blendMaterial.dispose(),this._basic.dispose(),this._fsQuad.dispose()}setSize(e,t){let n=Math.round(e/2),r=Math.round(t/2);this.renderTargetBright.setSize(n,r);for(let e=0;e<this.nMips;e++)this.renderTargetsHorizontal[e].setSize(n,r),this.renderTargetsVertical[e].setSize(n,r),this.separableBlurMaterials[e].uniforms.invSize.value=new y(1/n,1/r),n=Math.round(n/2),r=Math.round(r/2)}render(t,n,r,i,a){t.getClearColor(this._oldClearColor),this._oldClearAlpha=t.getClearAlpha();let o=t.autoClear;t.autoClear=!1,t.setClearColor(this.clearColor,0),a&&t.state.buffers.stencil.setTest(!1),this.renderToScreen&&(this._fsQuad.material=this._basic,this._basic.map=r.texture,t.setRenderTarget(null),t.clear(),this._fsQuad.render(t)),this.highPassUniforms.tDiffuse.value=r.texture,this.highPassUniforms.luminosityThreshold.value=this.threshold,this._fsQuad.material=this.materialHighPassFilter,t.setRenderTarget(this.renderTargetBright),t.clear(),this._fsQuad.render(t);let s=this.renderTargetBright;for(let n=0;n<this.nMips;n++)this._fsQuad.material=this.separableBlurMaterials[n],this.separableBlurMaterials[n].uniforms.colorTexture.value=s.texture,this.separableBlurMaterials[n].uniforms.direction.value=e.BlurDirectionX,t.setRenderTarget(this.renderTargetsHorizontal[n]),t.clear(),this._fsQuad.render(t),this.separableBlurMaterials[n].uniforms.colorTexture.value=this.renderTargetsHorizontal[n].texture,this.separableBlurMaterials[n].uniforms.direction.value=e.BlurDirectionY,t.setRenderTarget(this.renderTargetsVertical[n]),t.clear(),this._fsQuad.render(t),s=this.renderTargetsVertical[n];this._fsQuad.material=this.compositeMaterial,this.compositeMaterial.uniforms.bloomStrength.value=this.strength,this.compositeMaterial.uniforms.bloomRadius.value=this.radius,this.compositeMaterial.uniforms.bloomTintColors.value=this.bloomTintColors,t.setRenderTarget(this.renderTargetsHorizontal[0]),t.clear(),this._fsQuad.render(t),this._fsQuad.material=this.blendMaterial,this.copyUniforms.tDiffuse.value=this.renderTargetsHorizontal[0].texture,a&&t.state.buffers.stencil.setTest(!0),this.renderToScreen?(t.setRenderTarget(null),this._fsQuad.render(t)):(t.setRenderTarget(r),this._fsQuad.render(t)),t.setClearColor(this._oldClearColor,this._oldClearAlpha),t.autoClear=o}_getSeparableBlurMaterial(e){let t=[],n=e/3;for(let r=0;r<e;r++)t.push(.39894*Math.exp(-.5*r*r/(n*n))/n);let r=[],i=[];for(let n=1;n<e;n+=2){let a=t[n],o=n+1<e?t[n+1]:0,s=a+o;r.push((n*a+(n+1)*o)/s),i.push(s)}return new x({defines:{KERNEL_PAIRS:r.length},uniforms:{colorTexture:{value:null},invSize:{value:new y(.5,.5)},direction:{value:new y(.5,.5)},centerWeight:{value:t[0]},gaussianOffsets:{value:r},gaussianWeights:{value:i}},vertexShader:`

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

				}`})}_getCompositeMaterial(e){return new x({defines:{NUM_MIPS:e},uniforms:{blurTexture1:{value:null},blurTexture2:{value:null},blurTexture3:{value:null},blurTexture4:{value:null},blurTexture5:{value:null},bloomStrength:{value:1},bloomFactors:{value:null},bloomTintColors:{value:null},bloomRadius:{value:0}},vertexShader:`

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

				}`})}};Oe.BlurDirectionX=new y(1,0),Oe.BlurDirectionY=new y(0,1);var ke={name:`OutputShader`,uniforms:{tDiffuse:{value:null},toneMappingExposure:{value:1}},vertexShader:`
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

		}`},Ae=class extends k{constructor(){super(),this.isOutputPass=!0,this.uniforms=S.clone(ke.uniforms),this.material=new o({name:ke.name,uniforms:this.uniforms,vertexShader:ke.vertexShader,fragmentShader:ke.fragmentShader}),this._fsQuad=new j(this.material),this._outputColorSpace=null,this._toneMapping=null}render(e,t,n){this.uniforms.tDiffuse.value=n.texture,this.uniforms.toneMappingExposure.value=e.toneMappingExposure,(this._outputColorSpace!==e.outputColorSpace||this._toneMapping!==e.toneMapping)&&(this._outputColorSpace=e.outputColorSpace,this._toneMapping=e.toneMapping,this.material.defines={},v.getTransfer(this._outputColorSpace)===`srgb`&&(this.material.defines.SRGB_TRANSFER=``),this._toneMapping===1?this.material.defines.LINEAR_TONE_MAPPING=``:this._toneMapping===2?this.material.defines.REINHARD_TONE_MAPPING=``:this._toneMapping===3?this.material.defines.CINEON_TONE_MAPPING=``:this._toneMapping===4?this.material.defines.ACES_FILMIC_TONE_MAPPING=``:this._toneMapping===6?this.material.defines.AGX_TONE_MAPPING=``:this._toneMapping===7?this.material.defines.NEUTRAL_TONE_MAPPING=``:this._toneMapping===5&&(this.material.defines.CUSTOM_TONE_MAPPING=``),this.material.needsUpdate=!0),this.renderToScreen===!0?(e.setRenderTarget(null),this._fsQuad.render(e)):(e.setRenderTarget(t),this.clear&&e.clear(e.autoClearColor,e.autoClearDepth,e.autoClearStencil),this._fsQuad.render(e))}dispose(){this.material.dispose(),this._fsQuad.dispose()}},M={turquesa:`#7cf5e1`,ambar:`#ffb21a`,marca:`#2152e8`},N={canteiro:1,borda:8,calcada:12.2,z0:90,comprimento:520},je=[{x:2.75,dir:-1,v:11.2},{x:6.25,dir:-1,v:13.1},{x:-2.75,dir:1,v:12.4},{x:-6.25,dir:1,v:10.6}],P={radar:0,manutencao:-150,revisoes:-300},Me=40,Ne={radar:.74,manutencao:.53,revisoes:.8},F=[{tipo:`cancela`,z:-289,valor:32},{tipo:`patinete`,z:-296.5,valor:27},{tipo:`lombada`,z:-304,valor:18},{tipo:`camera`,z:-311.5,valor:9}];function Pe(e){let t=e>>>0;return()=>{t=t+1831565813|0;let e=Math.imul(t^t>>>15,1|t);return e=e+Math.imul(e^e>>>7,61|e)^e,((e^e>>>14)>>>0)/4294967296}}var I=(e,t,n)=>{let r=i.clamp((n-e)/(t-e),0,1);return r*r*(3-2*r)},L=(e,t=1)=>new _(e).multiplyScalar(t),Fe=(e,t,n)=>e.set(t).multiplyScalar(n);function Ie(e,t,n){let r=document.createElement(`canvas`);return r.width=e,r.height=t,n(r.getContext(`2d`),e,t),r}function Le(e,t,n){return Ie(e,t,r=>{let i=r.createImageData(e,t);for(let r=0;r<t;r++)for(let a=0;a<e;a++){let o=n(a/(e-1),1-r/(t-1),a,r);i.data.set(o,(r*e+a)*4)}r.putImageData(i,0,0)})}function R(e,{cor:t=!0,repetir:n=null}={}){let r=new ae(e);return t&&(r.colorSpace=m),n&&(r.wrapS=p,r.wrapT=p,r.repeat.set(...n)),r.anisotropy=4,r}function Re(e,t,n){let r=Pe(n),a=new Float32Array(e*e),o=0;for(let[n,s]of t){o+=s;let t=Array.from({length:n*n},r),c=(e,r)=>t[(r+n)%n*n+(e+n)%n];for(let t=0;t<e;t++)for(let r=0;r<e;r++){let o=r/e*n,l=t/e*n,u=Math.floor(o),d=Math.floor(l),f=I(0,1,o-u),ee=I(0,1,l-d),te=i.lerp(c(u,d),c(u+1,d),f),p=i.lerp(c(u,d+1),c(u+1,d+1),f);a[t*e+r]+=i.lerp(te,p,ee)*s}}for(let e=0;e<a.length;e++)a[e]/=o;return a}function z(e,t){let n=e.index?e.toNonIndexed():e,r=t instanceof _?t:new _(t),i=n.attributes.position.count,a=new Float32Array(i*3);for(let e=0;e<i;e++)a.set([r.r,r.g,r.b],e*3);return n.setAttribute(`color`,new pe(a,3)),n.attributes.uv||n.setAttribute(`uv`,new pe(new Float32Array(i*2),2)),n}var B=e=>ye(e.map(e=>e.attributes.color?e:z(e,`#ffffff`))),V=(e,t,n,r=0,i=0,a=0)=>new h(e,t,n).translate(r,i,a),H=(e,t,n,r,i=0,a=0,o=0)=>new he(e,t,n,r).translate(i,a,o);function ze(e){return new s(e.map(([e,t])=>new y(e,t)))}function U(e,t,n=.05,r=6){let i=t-2*n,a=new se(e,{depth:i,bevelEnabled:n>0,bevelThickness:n,bevelSize:n,bevelOffset:-n,bevelSegments:2,curveSegments:r});return a.translate(0,0,-i/2),a}function Be(e,t,n){let r=new s,i=-e/2,a=-t/2;return r.moveTo(i+n,a),r.lineTo(i+e-n,a),r.absarc(i+e-n,a+n,n,-Math.PI/2,0,!1),r.lineTo(i+e,a+t-n),r.absarc(i+e-n,a+t-n,n,0,Math.PI/2,!1),r.lineTo(i+n,a+t),r.absarc(i+n,a+t-n,n,Math.PI/2,Math.PI,!1),r.lineTo(i,a+n),r.absarc(i+n,a+n,n,Math.PI,Math.PI*1.5,!1),r}function Ve(e,t,n){let r=n/2,i=new s;return i.absarc(0,0,e+r,-t,t,!1),i.absarc(e*Math.cos(t),e*Math.sin(t),r,t,t+Math.PI,!1),i.absarc(0,0,e-r,t,-t,!0),i.absarc(e*Math.cos(t),-e*Math.sin(t),r,Math.PI-t,2*Math.PI-t,!1),i}function He(e,t,n,r,i){let a=[...e,...t,...n,...e,...n,...r],o=[0,0,1,0,1,1,0,0,1,1,0,1],s=new C;return s.setAttribute(`position`,new pe(new Float32Array(a),3)),s.setAttribute(`uv`,new pe(new Float32Array(o),2)),s.computeVertexNormals(),z(s,i)}function Ue(){let e=R(Le(32,128,(e,t)=>{let n=255*(1-t)**1.8*Math.sin(Math.PI*e)**1.4;return[n,n,n,255]}),{cor:!1}),t=R(Le(128,128,(e,t)=>{let n=Math.hypot(e-.5,t-.5)*2,r=255*Math.exp(-n*n*3.2)*(1-I(.8,1,n));return[r,r,r,255]}),{cor:!1}),n=Re(128,[[4,.5],[16,.3],[64,.2]],7),r=Re(128,[[3,.7],[9,.3]],11),a=Pe(5),o=R(Le(128,128,(e,t,r,i)=>{let o=.78+n[i*128+r]*.36+(a()-.5)*.16;return[26*o,30*o,37*o,255]}),{repetir:[9,60]}),s=R(Le(128,128,(e,t,n,o)=>{let s=I(.42,.66,r[o*128+n]),c=255*i.lerp(.78,.42,s)+(a()-.5)*16;return[c,c,c,255]}),{cor:!1,repetir:[9,60]}),c=Pe(23);return{rastro:e,mancha:t,asfalto:o,rugosidade:s,janelas:R(Ie(512,512,e=>{e.fillStyle=`#07090d`,e.fillRect(0,0,512,512);let t=[`#ffc98a`,`#ffb070`,`#ffd9a8`,`#d6e4ff`,`#9fb8ff`];for(let n=0;n<16;n++){let r=c()>.18;for(let i=0;i<16;i++){let a=i*32,o=n*32;if(n===15){let n=c()>.55;e.fillStyle=n?t[Math.floor(c()*3)]:`#0c1017`,e.globalAlpha=n?.25+c()*.3:1,e.fillRect(a+1,o+10,30,20),e.globalAlpha=1;continue}let s=r&&c()<.24;e.fillStyle=s?t[Math.floor(c()*t.length)]:`#0b0f16`,e.globalAlpha=s?.2+c()*.55:1,e.fillRect(a+10,o+11,13,14),e.globalAlpha=1}}}),{repetir:[1,1]})}}var We={0:[`01110`,`10001`,`10011`,`10101`,`11001`,`10001`,`01110`],1:[`00100`,`01100`,`00100`,`00100`,`00100`,`00100`,`01110`],2:[`01110`,`10001`,`00001`,`00010`,`00100`,`01000`,`11111`],3:[`11110`,`00001`,`00001`,`01110`,`00001`,`00001`,`11110`],4:[`00010`,`00110`,`01010`,`10010`,`11111`,`00010`,`00010`],5:[`11111`,`10000`,`11110`,`00001`,`00001`,`10001`,`01110`],6:[`00110`,`01000`,`10000`,`11110`,`10001`,`10001`,`01110`],7:[`11111`,`00001`,`00010`,`00100`,`01000`,`01000`,`01000`],8:[`01110`,`10001`,`10001`,`01110`,`10001`,`10001`,`01110`],9:[`01110`,`10001`,`10001`,`01111`,`00001`,`00010`,`01100`],"-":[`00000`,`00000`,`00000`,`11111`,`00000`,`00000`,`00000`]};function Ge(e,t){let n=e.canvas.width,r=e.canvas.height;e.fillStyle=`#030405`,e.fillRect(0,0,n,r);let i=t==null?`--`:String(t).padStart(2,` `),a=t!=null&&t>Me;for(let t=0;t<2;t++){let n=We[i[t]]??We[`-`];for(let r=0;r<7;r++)for(let o=0;o<5;o++){let s=i[t]!==` `&&n[r][o]===`1`;e.fillStyle=s?a?`#ff3a24`:M.ambar:`#1c1206`,e.beginPath(),e.arc(16+t*76+o*13,25+r*13,s?5.2:4.2,0,Math.PI*2),e.fill()}}e.fillStyle=a?`#ff3a24`:M.ambar,e.font=`800 22px Overpass, system-ui, sans-serif`,e.textBaseline=`alphabetic`,e.fillText(`km/h`,184,103)}var Ke=`
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
  }`;function qe(e,t){return new x({uniforms:S.merge([de.fog,{cor:{value:L(e)},intensidade:{value:t}}]),vertexShader:`
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
      ${Ke}
      void main() {
        float borda = pow(abs(dot(normalize(vNormal), normalize(vVista))), 1.8);
        float queda = mix(0.06, 1.0, pow(vAltura, 2.2));
        float a = intensidade * borda * queda * (1.0 - neblina());
        gl_FragColor = vec4(cor * a, 1.0);
      }`,transparent:!0,depthWrite:!1,blending:2,side:2,fog:!0})}function Je(e){return new x({uniforms:S.merge([de.fog,{cor:{value:L(e)},tempo:{value:0},altura:{value:1},brilho:{value:1}}]),vertexShader:`
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
      ${Ke}
      void main() {
        if (vUv.y > altura) discard;
        float borda = 1.0 - abs(dot(normalize(vNormal), normalize(vVista)));
        float faixas = 0.55 + 0.45 * smoothstep(0.35, 0.5, abs(fract(vUv.y * 14.0 - tempo * 0.35) - 0.5));
        float topo = smoothstep(altura - 0.035, altura, vUv.y);
        float a = (0.16 + 0.9 * pow(borda, 2.2)) * faixas + topo * 2.2;
        a *= mix(0.55, 1.0, vUv.y) * brilho * (1.0 - neblina());
        gl_FragColor = vec4(cor * a, 1.0);
      }`,transparent:!0,depthWrite:!1,blending:2,side:2,fog:!0})}var Ye={uniforms:{tDiffuse:{value:null},tempo:{value:0},grao:{value:.045},aspecto:{value:2.39}},vertexShader:`
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
    }`};function Xe({celular:a}){let o;try{o=new ge({antialias:!1,alpha:!1,powerPreference:`high-performance`,failIfMajorPerformanceCaveat:!0})}catch{return null}o.toneMapping=4,o.toneMappingExposure=1.05,o.shadowMap.enabled=!a,o.shadowMap.type=1,o.setClearColor(`#05080d`,1);let s=o.domElement;s.setAttribute(`aria-hidden`,`true`);let p=Ue(),m=new c,ae=new _(`#0d1626`);m.fog=new ve(ae,.0125);let v=new l(30,2.39,.1,1400),de=new b(new re(1e3,32,16),new x({uniforms:{zenite:{value:L(`#02040a`)},horizonte:{value:ae.clone()},cidade:{value:L(`#3b2c2a`,.7)}},vertexShader:`
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
      }`,side:1,depthWrite:!1}));de.renderOrder=-1,m.add(de);let pe=new ee(`#2a4170`,`#07080b`,.6),S=new ue(`#7f97cf`,.38);S.position.set(-40,50,-80);let C=new f(`#ffd6a3`,0,40,.75,.55,1.6);C.castShadow=!a,C.shadow.mapSize.set(1024,1024),C.shadow.bias=-6e-4,C.shadow.normalBias=.04,C.shadow.camera.near=1,C.shadow.camera.far=40;let w=new f(M.ambar,0,26,.42,.7,1.4),T=new n(M.ambar,0,14,2),O=new n(M.turquesa,0,14,2);m.add(pe,S,C,C.target,w,w.target,T,O);let be=new b(new D(120,N.comprimento+200).rotateX(-Math.PI/2).translate(0,0,N.z0-N.comprimento/2),new u({map:p.asfalto,roughnessMap:p.rugosidade,roughness:1,metalness:.08}));be.receiveShadow=!0,p.asfalto.repeat.set(120/9,(N.comprimento+200)/9),p.rugosidade.repeat.copy(p.asfalto.repeat);let k=N.comprimento+200,A=N.z0-N.comprimento/2,xe=new _(`#3a414c`),j=new _(`#8d96a3`),Ce=N.calcada-N.borda,we=B([z(V(Ce,.16,k,N.borda+Ce/2,.08,A),xe),z(V(Ce,.16,k,-N.borda-Ce/2,.08,A),xe),z(V(.18,.18,k,N.borda+.09,.09,A),j),z(V(.18,.18,k,-N.borda-.09,.09,A),j),z(V(N.canteiro*2,.22,k,0,.11,A),new _(`#1a2a20`)),z(V(.12,.24,k,N.canteiro-.06,.12,A),j),z(V(.12,.24,k,-N.canteiro+.06,.12,A),j)]),De=new b(we,new u({vertexColors:!0,roughness:.82})),ke=new b(B([-1,1].map(e=>z(V(60,.14,k,e*(N.calcada+30),.07,A),`#151a20`))),new u({vertexColors:!0,roughness:.95}));ke.receiveShadow=!0,De.receiveShadow=!0;let Ne=[],Le=new _(`#c9cfd8`);for(let e of[1,-1]){Ne.push(V(.14,.02,k,e*(N.borda-.35),.006,A));for(let t=N.z0;t>N.z0-N.comprimento;t-=12)Ne.push(V(.13,.02,4,e*4.5,.006,t))}for(let e of[1,-1])for(let t=N.canteiro+.55;t<N.borda-.4;t+=.95)Ne.push(V(.48,.02,3.4,e*t,.007,P.radar-9));Ne.push(V(N.borda-N.canteiro-.3,.02,.4,(N.borda+N.canteiro)/2,.007,P.radar-5.8));let Re=new b(B(Ne.map(e=>z(e,Le))),new u({vertexColors:!0,roughness:.55}));Re.receiveShadow=!0;let We=[],Ke=[];for(let e of je.slice(0,2)){for(let t of[13.5,9.5])for(let[n,r,i,a]of[[2,.06,0,-.8],[2,.06,0,.8],[.06,1.6,-1,0],[.06,1.6,1,0]])Ke.push(V(n,.02,r,e.x+i,.008,P.radar+t+a));let n=new b(new D(2.6,6.2).rotateX(-Math.PI/2),new t({color:L(M.turquesa,1.4),alphaMap:p.mancha,transparent:!0,opacity:0,blending:2,depthWrite:!1}));n.position.set(e.x,.03,P.radar+11.5),We.push(n)}let Xe=new b(B(Ke.map(e=>z(e,`#05070a`))),new t({vertexColors:!0}));m.add(be,De,ke,Re,Xe,...We);let W=Pe(41),Ze=[];for(let e of[1,-1]){let t=N.z0+40;for(;t>N.z0-N.comprimento-120;){let n=10+W()*16,r=10+W()*14,i=W()<.18,a=i?26+W()*22:7+W()*13,o=N.calcada+2+W()*5+(i?8:0),s=new h(r,a,n),c=Math.floor(W()*16)/16,l=s.attributes.uv,u=[[n,a],[n,a],[0,0],[0,0],[r,a],[r,a]];for(let e=0;e<6;e++)for(let t=0;t<4;t++){let n=e*4+t,[r,i]=u[e];r?l.setXY(n,l.getX(n)*r/35.2+c,l.getY(n)*i/51.2):l.setXY(n,.995,.5)}s.translate(e*(o+r/2),a/2,t-n/2),Ze.push(s),t-=n+1+W()*4}}for(let e=0;e<26;e++){let t=14+W()*24,n=18+W()*50,r=new h(t,n,16),i=r.attributes.uv;for(let e=0;e<i.count;e++)i.setXY(e,i.getX(e)*t/35.2,i.getY(e)*n/51.2);r.translate(-160+e*13+W()*6,n/2,N.z0-N.comprimento-140-W()*80),Ze.push(r)}let Qe=new b(ye(Ze),new t({map:p.janelas,color:L(`#ffffff`,.85)}));m.add(Qe);let G=[],$e=(e,t)=>!(e>0&&(Math.abs(t-P.radar)<12||t<-268&&t>-322))&&!(e<0&&Math.abs(t-P.manutencao)<16);for(let e=N.z0+10;e>N.z0-N.comprimento-40;e-=32)$e(1,e)&&G.push({x:N.borda+.7,z:e,lado:1}),$e(-1,e-16)&&G.push({x:-N.borda-.7,z:e-16,lado:-1});G.push({x:N.borda+.7,z:P.radar-12,lado:1},{x:N.borda+.7,z:-300.2,lado:1});let et=2.6,tt=8.6,nt=B([z(H(.09,.14,tt,10,0,tt/2,0),`#4a525e`),z(H(.05,.05,et,6).rotateZ(Math.PI/2).translate(-2.6/2,8.5,0),`#4a525e`),z(V(.95,.16,.42,-2.6,8.5,0),`#2b3039`)]),rt=new D(.82,.32).rotateX(Math.PI/2).translate(-2.6,8.41,0),it=new he(.3,4.2,8.299999999999999,28,1,!0).translate(-2.6,8.299999999999999/2,0),at=new D(14,14).rotateX(-Math.PI/2).translate(-2.6,.025,0),ot=new E(nt,new u({vertexColors:!0,roughness:.5,metalness:.6}),G.length),st=new E(rt,new t({color:L(`#ffe2bd`,9),side:2}),G.length),ct=new E(it,qe(`#ffd9a8`,.1),G.length),lt=new E(at,new t({color:L(`#ffcf96`,.2),alphaMap:p.mancha,transparent:!0,blending:2,depthWrite:!1}),G.length),ut=(e,t)=>{let n=G.filter(t=>t.lado===e).reduce((e,n)=>Math.abs(n.z-t)<Math.abs(e.z-t)?n:e);return new g(n.x-e*et,8.299999999999999,n.z)},K=new d;G.forEach((e,t)=>{K.position.set(e.x,0,e.z),K.rotation.set(0,e.lado>0?0:Math.PI,0),K.updateMatrix();for(let e of[ot,st,ct,lt])e.setMatrixAt(t,K.matrix)}),ot.castShadow=!0;let q=new E(new D(1,1).rotateX(-Math.PI/2),new t({color:`#ffffff`,alphaMap:p.mancha,transparent:!0,blending:2,depthWrite:!1,fog:!1}),G.length);q.instanceMatrix.setUsage(ce),q.frustumCulled=!1;let dt=L(`#ffc98e`,.42),ft=new _,pt=m.fog.density;function mt(e){G.forEach((t,n)=>{let r=t.x-t.lado*et,a=e.x-r,o=e.z-t.z,s=Math.hypot(a,o),c=8.4/(8.4+Math.max(e.y,.3)),l=i.clamp(s*.2*i.clamp(2.5/Math.max(e.y,.5),.15,1.6),.7,9);K.position.set(r+a*c,.03,t.z+o*c),K.rotation.set(0,Math.atan2(a,o),0),K.scale.set(.55,1,l),K.updateMatrix(),q.setMatrixAt(n,K.matrix);let u=Math.exp(-((pt*s)**2));q.setColorAt(n,ft.copy(dt).multiplyScalar(u))}),K.scale.set(1,1,1),q.instanceMatrix.needsUpdate=!0,q.instanceColor.needsUpdate=!0}m.add(ot,st,ct,lt,q);let ht=B([z(H(.1,.16,2.6,6,0,1.3,0),`#1d1712`),z(new r(1.5,1).translate(0,3.4,0),`#13261b`),z(new r(1.1,1).translate(.8,3,.4),`#102018`),z(new r(1,1).translate(-.7,3.1,-.5),`#152a1e`)]),gt=[];for(let e=N.z0;e>N.z0-N.comprimento;e-=13+W()*9)[[-18,60],[P.manutencao-18,P.manutencao+36],[-322,-252]].some(([t,n])=>e>t&&e<n)||gt.push(e);let _t=new E(ht,new u({vertexColors:!0,roughness:.95}),gt.length);gt.forEach((e,t)=>{K.position.set(0,.2,e),K.rotation.set(0,W()*6.28,0),K.scale.setScalar(.8+W()*.5),K.updateMatrix(),_t.setMatrixAt(t,K.matrix)}),K.scale.setScalar(1),_t.castShadow=!0,m.add(_t);let vt=B([z(U(ze([[-2.2,.3],[2.12,.3],[2.22,.5],[2.2,.78],[1.95,.9],[1.15,1],[-1.55,1.04],[-2.12,.98],[-2.24,.78],[-2.25,.45]]),1.76,.07),`#ffffff`),z(U(ze([[1.16,.98],[.45,1.41],[-.78,1.44],[-1.56,1.03]]),1.6,.06),new _(.025,.028,.035)),...[[-1.38,.78],[-1.38,-.78],[1.4,.78],[1.4,-.78]].map(([e,t])=>z(H(.33,.33,.22,14).rotateX(Math.PI/2).translate(e,.33,t),new _(.01,.01,.012)))]).rotateY(Math.PI/2),yt=B([z(U(Be(12,2.75,.32),2.5,.08,4).translate(0,1.72,0),`#ffffff`),...[[-4.2,1.1],[-4.2,-1.1],[3.6,1.1],[3.6,-1.1]].map(([e,t])=>z(H(.5,.5,.3,14).rotateX(Math.PI/2).translate(e,.5,t),new _(.01,.01,.012)))]).rotateY(Math.PI/2),bt=(e,t,n,r,i=[])=>B([...[-1,1].map(n=>z(V(.36,.13,.06,n*r,t,-e-.01),new _(6,6,6.6))),...[-1,1].map(t=>z(V(.34,.12,.06,t*r,n,e+.01),new _(3.4,.14,.08))),...i]),xt=bt(2.22,.72,.86,.6),St=[];for(let e of[-1,1])for(let t=-5.2;t<4.4;t+=1.45)St.push(z(V(.02,.85,1.25,e*1.26,2.15,t),new _(.95,.78,.52)));let Ct=bt(6.02,.75,1,.9,[...St,z(V(1.7,.28,.04,0,2.86,-6.03),new _(3.2,1.6,.2))]),wt=(e,t,n,r,i,a)=>{let o=[];for(let a of[-1,1]){let s=a*r;for(let[r,a,c]of[[t,-e,new _(1.7,1.75,2)],[n,e,new _(2.2,.1,.06)]]){let e=a+i;o.push(He([s-.09,r,a],[s+.09,r,a],[s+.09,r,e],[s-.09,r,e],c)),o.push(He([s,r-.07,a],[s,r+.07,a],[s,r+.07,e],[s,r-.07,e],c))}}let s=-e-.4,c=s-a;return o.push(He([-.9,.03,s],[.9,.03,s],[2.6,.03,c],[-2.6,.03,c],new _(.75,.7,.6))),B(o)},Tt=wt(2.22,.72,.86,.6,9,15),Et=wt(6.02,.75,1,.9,9,17),Dt=new u({vertexColors:!0,roughness:.32,metalness:.55}),Ot=new t({vertexColors:!0}),kt=new t({vertexColors:!0,alphaMap:p.rastro,transparent:!0,blending:2,depthWrite:!1,side:2}),At=[`#d9dde3`,`#9aa3ad`,`#5c6470`,`#1b1f26`,`#7a1d1d`,`#1f3357`,`#c8ccd2`,`#2c3a2f`],jt=[];je.forEach((e,t)=>{let n=W()*40;for(let r=0;r<7;r++){let i=t===2&&(r===1||r===4);jt.push({faixa:e,s:n,onibus:i,cor:new _(At[Math.floor(W()*At.length)]),jitter:Math.round((W()-.5)*7)}),n+=N.comprimento/7*(.7+W()*.6)}});let Mt=jt.filter(e=>!e.onibus),Nt=jt.filter(e=>e.onibus),Pt=(e,t,n,r)=>{let i=new E(e,t,n.length);return i.instanceMatrix.setUsage(ce),i.castShadow=r,i.frustumCulled=!1,i},Ft=[Pt(vt,Dt,Mt,!0),Pt(xt,Ot,Mt,!1),Pt(Tt,kt,Mt,!1)],It=[Pt(yt,Dt,Nt,!0),Pt(Ct,Ot,Nt,!1),Pt(Et,kt,Nt,!1)];Mt.forEach((e,t)=>Ft[0].setColorAt(t,e.cor)),Nt.forEach((e,t)=>It[0].setColorAt(t,t?new _(`#e8e2d0`):new _(`#d6dce4`))),m.add(...Ft,...It);let Lt=(e,t)=>{let n=(e.s+e.faixa.v*t)%N.comprimento;return e.faixa.dir<0?N.z0-n:N.z0-N.comprimento+n};function Rt(e){for(let[t,n]of[[Mt,Ft],[Nt,It]]){t.forEach((t,r)=>{K.position.set(t.faixa.x,0,Lt(t,e)),K.rotation.set(0,t.faixa.dir<0?0:Math.PI,0),K.updateMatrix();for(let e of n)e.setMatrixAt(r,K.matrix)});for(let e of n)e.instanceMatrix.needsUpdate=!0}}let zt=document.fonts?.load(`800 64px Overpass`).catch(()=>{})??Promise.resolve(),Bt=new u({color:`#1f2733`,roughness:.48,metalness:.65}),Vt=new u({color:`#0d1117`,roughness:.4,metalness:.3}),Ht=new t({color:L(M.turquesa,3)});function Ut(e=1,n=!0){let r=new ie,i=new b(B([z(U(Be(.95,1.05,.08),.6,.03).translate(0,.68,0),`#1f2733`),z(V(.34,1.5,.26,0,1.95,0),`#26303d`),z(U(Be(1.56,.9,.1),.32,.04).translate(0,3,0),`#141a22`),z(V(.6,.26,.34,0,3.6,0),`#1a212b`)]),new u({vertexColors:!0,roughness:.45,metalness:.6}));i.castShadow=!0,i.receiveShadow=!0;let a=document.createElement(`canvas`);a.width=256,a.height=128;let o=a.getContext(`2d`);Ge(o,null);let s=R(a),c=new b(new D(1.36,.68),new t({map:s,color:L(`#ffffff`,3.4)}));c.position.set(0,3,.165);let l=new b(new h(.44,.035,.02),Ht);l.position.set(0,3.52,.175);let d=new b(new he(.075,.075,.05,20).rotateX(Math.PI/2),Vt);if(d.position.set(.16,3.62,.18),r.add(i,c,l,d),n){let e=Ie(256,256,()=>{}),t=()=>{let t=e.getContext(`2d`);t.fillStyle=`#ffffff`,t.beginPath(),t.arc(128,128,124,0,Math.PI*2),t.fill(),t.lineWidth=30,t.strokeStyle=`#d4161c`,t.beginPath(),t.arc(128,128,106,0,Math.PI*2),t.stroke(),t.fillStyle=`#111111`,t.textAlign=`center`,t.font=`800 104px Overpass, system-ui, sans-serif`,t.fillText(String(Me),128,150),t.font=`700 30px Overpass, system-ui, sans-serif`,t.fillText(`km/h`,128,190),n.needsUpdate=!0},n=R(e);t(),zt.then(t);let i=new b(new he(.44,.44,.03,40).rotateX(Math.PI/2).rotateZ(Math.PI/2),new u({map:n,emissiveMap:n,emissive:`#ffffff`,emissiveIntensity:.12,roughness:.4}));i.position.set(0,4.25,0);let a=new b(new h(.08,.5,.06),Bt);a.position.set(0,3.88,-.04),i.castShadow=!0,r.add(i,a)}return r.scale.setScalar(e),{g:r,ctx:o,texPainel:s,valor:void 0}}let J=Ut();J.g.position.set(N.borda+1.5,.16,P.radar),J.g.rotation.y=-.18;let Wt=ut(1,P.radar),Gt=[0,1,2].map(()=>{let e=new b(new se(Ve(.4,.7,.045),{depth:.02,bevelEnabled:!1,curveSegments:12}),new t({color:L(M.turquesa,1.6),transparent:!0,opacity:0,blending:2,depthWrite:!1}));return e.rotation.y=-2.2,e.position.set(N.borda+1.3,3.75,P.radar+.4),m.add(e),e});m.add(J.g);let Y=P.manutencao,X=-N.borda-1.4,Kt=new ie,qt=new b(B([z(H(.13,.17,6.2,14,0,3.1,0),`#5a6472`),z(H(.08,.08,4.6,10).rotateZ(Math.PI/2).translate(2.3,6,0),`#5a6472`),z(V(.72,.42,.5,4.3,5.68,0),`#aeb6c1`),z(V(.8,.06,.62,4.3,5.92,.04),`#c9cfd8`),z(V(.3,.3,.06,4.3,5.68,.27),`#0b0e13`),z(V(.36,.24,.3,3.55,5.62,0),`#d3d8de`)]),new u({vertexColors:!0,roughness:.45,metalness:.4}));qt.castShadow=!0;let Jt=new t({color:L(`#ff3a24`,3)}),Yt=new b(new fe(.2,.028,8,40),Jt);Yt.position.set(4.3,5.68,.31);let Xt=new b(U(Be(.9,1.4,.06),.55,.03),new u({color:`#c9ced6`,roughness:.5,metalness:.35}));Xt.position.set(.85,.86,0),Xt.castShadow=!0,Xt.receiveShadow=!0;let Zt=new b(new D(.72,1.18),new t({map:R(Ie(64,96,e=>{e.fillStyle=`#071410`,e.fillRect(0,0,64,96);let t=Pe(3);for(let n=0;n<70;n++)e.fillStyle=t()>.7?`#ffb21a`:t()>.4?`#4dffb0`:`#7cf5e1`,e.globalAlpha=.4+t()*.6,e.fillRect(Math.floor(t()*14)*4+4,Math.floor(t()*22)*4+4,2,2)})),color:L(`#ffffff`,.9)}));Zt.position.set(.85,.86,.281);let Qt=new ie;Qt.position.set(.85-.45,.86,.29);let $t=new b(new h(.88,1.36,.03).translate(.44,0,0),new u({color:`#bfc5ce`,roughness:.45,metalness:.4}));$t.castShadow=!0,Qt.add($t),Kt.add(qt,Yt,Xt,Zt,Qt),Kt.position.set(X,.16,Y),m.add(Kt);let en=B([z(U(ze([[-2.6,.38],[2.6,.38],[2.62,1.12],[2.35,1.32],[1.72,2.12],[1.25,2.28],[-2.5,2.3],[-2.62,2.18]]),2,.08),`#e4e7eb`),z(U(ze([[2.34,1.34],[1.72,2.1],[1.18,2.1],[1.18,1.36]]),2.04,.05),new _(.02,.025,.03)),z(V(5.1,.16,2.04,0,1.1,0),`#ff7a1a`),...[[-1.7,.85],[-1.7,-.85],[1.75,.85],[1.75,-.85]].map(([e,t])=>z(H(.38,.38,.26,14).rotateX(Math.PI/2).translate(e,.38,t),new _(.01,.01,.012)))]).rotateY(-Math.PI/2),tn=new b(en,new u({vertexColors:!0,roughness:.36,metalness:.3}));tn.position.set(-N.borda-2.3,.16,Y-8),tn.castShadow=!0;let nn=new t({color:L(M.ambar,1)}),Z=new b(new h(1.25,.13,.28),nn);Z.position.set(tn.position.x,2.56,tn.position.z+1),Z.rotation.y=Math.PI/2;let rn=new b(new he(.15,2.4,9,24,1,!0).rotateZ(Math.PI/2).translate(4.5,0,0),qe(M.ambar,0));rn.position.copy(Z.position),m.add(tn,Z,rn);let an=B([z(new te([[.001,.74],[.04,.74],[.16,.06],[.001,.06]].map(([e,t])=>new y(e,t)),18),`#ff5a14`),z(new te([[.083,.5],[.112,.36],[.107,.36],[.078,.5]].map(([e,t])=>new y(e,t)),18).scale(1.04,1,1.04),new _(1.3,1.3,1.3)),z(new te([[.122,.3],[.142,.2],[.137,.2],[.117,.3]].map(([e,t])=>new y(e,t)),18).scale(1.04,1,1.04),new _(1.3,1.3,1.3)),z(V(.42,.05,.42,0,.025,0),`#1a1a1a`)]),on=[[-7.72,Y+4.6],[-7.72,Y+2.4],[-7.72,Y+.2],[-7.72,Y-2],[-7.72,Y-4.2],[-8.7,Y-5.4]],sn=new E(an,new u({vertexColors:!0,roughness:.55}),on.length);on.forEach(([e,t],n)=>{K.position.set(e,e<-N.borda?.16:0,t),K.rotation.set(0,n,0),K.updateMatrix(),sn.setMatrixAt(n,K.matrix)}),sn.castShadow=!0,m.add(sn);let cn=new ie,ln=new b(B([...[0,2.1,4.2].map(e=>z(H(.018,.018,1.7,5).translate(0,.85,0).rotateZ(.22).rotateY(e),`#2a2f36`)),z(H(.025,.025,.9,6,0,1.95,0),`#2a2f36`),z(V(.5,.36,.14,0,2.45,0),`#22262c`)]),new u({vertexColors:!0,roughness:.6,metalness:.5})),un=new b(new D(.42,.28),new t({color:L(`#eef4ff`,0)}));un.position.set(0,2.45,.075),cn.add(ln,un),cn.position.set(X-1.2,.16,Y+2.6),cn.lookAt(X+.85,1,Y),m.add(cn);let Q=N.borda+2,dn=[];for(let n of F){let r=new ie;if(n.tipo===`cancela`){let e=R(Ie(256,16,e=>{for(let t=0;t<8;t++)e.fillStyle=t%2?`#f4f4f4`:`#d81f26`,e.fillRect(t*32,0,32,16)})),t=new b(U(Be(.42,1.1,.05),.36,.03).translate(0,.55,0),new u({color:`#f2b705`,roughness:.5,metalness:.2})),n=new b(new h(.1,.1,3.6).translate(0,0,-1.8),new u({map:e,emissiveMap:e,emissive:`#ffffff`,emissiveIntensity:.22,roughness:.4}));n.position.set(0,.92,-.1),r.add(t,n)}else if(n.tipo===`patinete`){let e=new b(B([z(V(.17,.05,1,0,.12,0),`#1c232c`),z(H(.025,.025,1.05,8).rotateX(-.22).translate(0,.62,-.5),`#3a4452`),z(H(.02,.02,.52,8).rotateZ(Math.PI/2).translate(0,1.14,-.62),`#3a4452`),...[-.5,.48].map(e=>z(H(.1,.1,.05,14).rotateZ(Math.PI/2).translate(0,.1,e),`#0b0d10`))]),new u({vertexColors:!0,roughness:.4,metalness:.5})),t=new b(new h(.06,.04,.02),Ht);t.position.set(0,1.02,-.58),e.rotation.z=.12,r.add(e,t)}else if(n.tipo===`lombada`)r.add(Ut(.72,!1).g);else{let e=new b(B([z(H(.08,.1,4.6,12,0,2.3,0),`#5a6472`),z(H(.04,.04,.9,8).rotateX(Math.PI/2).translate(0,4.5,-.45),`#5a6472`),z(H(.2,.2,.12,20,0,4.42,-.9),`#e5e8ec`),z(new re(.18,20,10,0,Math.PI*2,Math.PI/2,Math.PI/2).translate(0,4.37,-.9),new _(.02,.025,.035))]),new u({vertexColors:!0,roughness:.4,metalness:.5}));r.add(e)}r.traverse(e=>{e.isMesh&&(e.castShadow=!0,e.receiveShadow=!0)}),r.position.set(Q,.16,n.z),m.add(r);let i=n===F[0],a=i?M.ambar:M.turquesa,o=.6+n.valor*.1,s=new b(new he(.3,.3,o,32,1,!0).translate(0,o/2,0),Je(a));s.position.set(Q+1.2,.16,n.z);let c=new b(new fe(.42,.025,6,48).rotateX(Math.PI/2),new t({color:L(a,2.2),transparent:!0,blending:2,depthWrite:!1}));c.position.set(Q+1.2,.19,n.z);let l=document.createElement(`canvas`);l.width=256,l.height=128;let d=R(l),f=()=>{let e=l.getContext(`2d`);e.clearRect(0,0,256,128),e.fillStyle=`#ffffff`,e.textBaseline=`alphabetic`,e.font=`800 84px Overpass, system-ui, sans-serif`;let t=e.measureText(String(n.valor)).width;e.font=`700 34px Overpass, system-ui, sans-serif`;let r=e.measureText(`/100`).width,i=(256-t-r-6)/2;e.font=`800 84px Overpass, system-ui, sans-serif`,e.fillText(String(n.valor),i,100),e.globalAlpha=.72,e.font=`700 34px Overpass, system-ui, sans-serif`,e.fillText(`/100`,i+t+6,100),d.needsUpdate=!0};f(),zt.then(f);let ee=new e(new oe({map:d,color:L(a,i?2.4:1.6),transparent:!0,depthWrite:!1,fog:!0}));ee.scale.set(1.3,.65,1),ee.position.set(Q+1.2,.16+o+.55,n.z),m.add(s,c,ee),dn.push({item:n,coluna:s,base:c,rotulo:ee,alturaColuna:o,principal:i})}let fn=new ie,pn=new b(new D(4.6,.35).rotateX(-Math.PI/2),new t({color:L(M.turquesa,3),alphaMap:p.mancha,transparent:!0,blending:2,depthWrite:!1}));pn.position.y=.2;let mn=new b(new D(4.6,4.5).translate(0,2.25,0),new t({color:L(M.turquesa,.32),alphaMap:p.rastro,transparent:!0,blending:2,depthWrite:!1,side:2}));fn.add(pn,mn),fn.position.set(Q+.1,0,F[0].z+6),m.add(fn);let hn=e=>new le(e.map(e=>new g(...e)),!1,`centripetal`),gn={radar:{pos:hn([[-4,26,52],[1,15,32],[6.5,6,18],[10.7,2,7.2]]),alvo:hn([[5,0,-8],[7,1,-4],[8.6,2.2,-1],[9.1,2.95,-.4]])},manutencao:{pos:hn([[8,14,Y+32],[1.5,6.8,Y+18],[-4.5,4.2,Y+11.5],[-4.6,2.1,Y+8.6],[-4.4,4.4,Y+6.4],[-3,6.7,Y+5.2]]),alvo:hn([[-7.5,2.5,Y],[-8.3,1.8,Y],[-8.6,1.5,Y-.1],[-8.7,1.4,Y-.2],[-7.4,3.6,Y],[-5.3,5.75,Y]])},revisoes:{pos:hn([[-1,9,-262],[2.4,5.6,-268],[4.5,3.8,-273],[4.6,3.3,-277]]),alvo:hn([[9,1,-300],[9.8,1.6,-297],[10.2,2.2,-295],[10.3,2.4,-294.5]])}},_n=new g,vn=new g,yn=new g(0,0,1),bn=new ne,xn=new me(1,1,{type:_e,samples:a?0:4}),$=new Te(o,xn);$.addPass(new Ee(m,v));let Sn=new Oe(new y(256,256),.62,.42,.92);$.addPass(Sn),$.addPass(new Ae);let Cn=new Se(Ye);$.addPass(Cn),a&&(Cn.uniforms.grao.value=.035);function wn(e){for(let e of[C,w,T,O])e.intensity=0;O.distance=14,rn.material.uniforms.intensidade.value=0,e===`radar`?(C.color.set(`#ffd2a0`),C.position.copy(Wt),C.target.position.set(N.borda+1.5,0,P.radar),C.angle=.85,C.distance=30,C.intensity=260,T.color.set(M.ambar),T.position.set(N.borda+1.2,3,P.radar+1.2),O.position.set(N.borda+1.2,3.55,P.radar+.6)):e===`manutencao`?(C.color.set(`#eef4ff`),C.position.set(X-1.2,2.6,Y+2.6),C.target.position.set(X+.85,.9,Y),C.angle=.5,C.distance=16,w.position.copy(Z.position),T.color.set(`#9fffe0`),T.position.set(X+.85,.95,Y+.75),O.position.set(X+4.3,5.3,Y+1.4),O.distance=5):(C.color.set(`#ffd2a0`),C.position.copy(ut(1,F[1].z)),C.target.position.set(Q,0,F[1].z),C.angle=.95,C.distance=30,C.intensity=300,T.color.set(M.ambar),T.position.set(Q+.9,2.2,F[0].z),O.position.set(Q+.9,1.6,F[2].z)),C.target.updateMatrixWorld()}let Tn=null,En=!1;function Dn(e,{p:t,t:n,parado:r}){Tn!==e&&(wn(e),Tn=e);let a=i.clamp(t,0,1),s=I(.12,.88,a)*.7+i.clamp((a-.12)/.76,0,1)*.3,c=gn[e];c.pos.getPoint(s,_n),c.alvo.getPoint(s,vn),r||(_n.x+=Math.sin(n*.61)*.05+Math.sin(n*1.37)*.02,_n.y+=Math.sin(n*.83+1.3)*.04,vn.x+=Math.sin(n*.47+.4)*.05),v.position.copy(_n),v.lookAt(vn),de.position.copy(_n),mt(_n),bn.setFromAxisAngle(yn,(1-a)*(e===`manutencao`?-.05:.04)),v.quaternion.multiply(bn),Rt(n);let l=null;if(e===`radar`){let e=N.z0-(P.radar+11.5),t=-99;for(let r of jt){if(r.faixa.dir>0)continue;let i=Math.floor((r.faixa.v*n+r.s-e)/N.comprimento),a=(e-r.s+i*N.comprimento)/r.faixa.v;a<=n&&a>t&&(t=a,J.ultima=Math.round(r.faixa.v*3.6)+r.jitter,J.faixaLida=je.indexOf(r.faixa))}let i=n-t,a=r?38:i<4.5?J.ultima:null;a!==J.valor&&(Ge(J.ctx,a),J.texPainel.needsUpdate=!0,J.valor=a),We.forEach((e,t)=>{e.material.opacity=!r&&J.faixaLida===t?Math.max(0,1-i/.9):0}),Gt.forEach((e,t)=>{let i=r?.35+t*.22:(n*.8+t/3)%1;e.scale.setScalar(.6+i*1.7),e.material.opacity=Math.sin(Math.PI*Math.min(i*1.3,1))*.75});let o=a!=null&&a>Me;T.color.set(o?`#ff3a24`:M.ambar),T.intensity=a==null?1.2:5,O.intensity=2.2}else if(e===`manutencao`){let e=I(.32,.42,a)*(1-I(.62,.7,a)),t=a<.36?`falha`:a<.66?`execucao`:`concluida`;Qt.rotation.y=-1.9*e,Zt.visible=e>.02,C.intensity=55*e,Fe(un.material.color,`#eef4ff`,4*e);let i=r?1:.55+.45*Math.sign(Math.sin(n*7));t===`falha`?Fe(Jt.color,`#ff3a24`,2.4*i):t===`execucao`?Fe(Jt.color,M.ambar,2.4):Fe(Jt.color,M.turquesa,1.8),O.color.copy(Jt.color).multiplyScalar(.3),O.intensity=1.2,T.intensity=.2*e;let o=r?3.7:n*4.2;w.intensity=160*e,w.target.position.set(Z.position.x+Math.cos(o)*8,.2,Z.position.z+Math.sin(o)*8),w.target.updateMatrixWorld(),rn.rotation.set(0,-o,0),rn.material.uniforms.intensidade.value=.22*e,Fe(nn.color,M.ambar,.6+4*e*(.6+.4*Math.abs(Math.sin(o*2)))),l={fase:t}}else{let e=i.lerp(F[0].z+6,F[3].z-3,I(.12,.62,a));fn.position.z=e;let t=I(.58,.68,a);pn.material.opacity=1-t,mn.material.opacity=1-t;for(let t of dn){let i=I(t.item.z+1.5,t.item.z-1.5,e);t.coluna.material.uniforms.altura.value=Math.max(.001,i),t.coluna.material.uniforms.tempo.value=r?0:n,t.coluna.material.uniforms.brilho.value=t.principal?1+I(.62,.8,a)*.8:.85,t.rotulo.position.y=.16+t.alturaColuna*i+.55,t.rotulo.material.opacity=I(.6,1,i),t.base.material.opacity=.35+i*.65}T.intensity=6*I(.55,.75,a),O.intensity=1.5}return Cn.uniforms.tempo.value=r?0:n,En?o.render(m,v):$.render(),l}return{canvas:s,preparar:()=>(wn(`radar`),Rt(0),mt(v.position),o.compileAsync(m,v).catch(()=>{})),tamanho(e,t,n){let r=Math.max(1,Math.round(e)),a=Math.max(1,Math.round(t));o.setPixelRatio(n),o.setSize(r,a,!1),$.setPixelRatio(n),$.setSize(r,a),Sn.setSize(Math.round(r*n/2),Math.round(a*n/2));let s=r/a;v.aspect=s;let c=2*Math.atan(Math.tan(i.degToRad(30)/2)*2.39);v.fov=i.radToDeg(2*Math.atan(Math.tan(c/2)/Math.max(s,1))),v.updateProjectionMatrix(),Cn.uniforms.aspecto.value=s},quadro:Dn,simplificar(){En=!0}}}export{Ne as REPOUSO,Xe as criarMundo};