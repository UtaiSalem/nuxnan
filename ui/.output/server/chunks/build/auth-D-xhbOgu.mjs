import { defineComponent, mergeProps, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderList, ssrRenderStyle, ssrRenderSlot } from 'vue/server-renderer';
import { _ as _export_sfc } from './server.mjs';
import '../_/nitro.mjs';
import 'node:http';
import 'node:https';
import 'node:events';
import 'node:buffer';
import 'vue-router';
import 'node:fs';
import 'node:path';
import 'node:url';
import 'node:crypto';
import 'pinia';
import '@iconify/vue';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/plugins';
import 'unhead/utils';

const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "auth",
  __ssrInlineRender: true,
  setup(__props) {
    const getWarpStarStyle = (index) => {
      const angle = index / 100 * 360;
      const distance = Math.random() * 50 + 50;
      const size = Math.random() * 2 + 1;
      const duration = Math.random() * 3 + 2;
      const delay = Math.random() * 5;
      return {
        "--angle": `${angle}deg`,
        "--distance": `${distance}%`,
        width: `${size}px`,
        height: `${size}px`,
        animationDuration: `${duration}s`,
        animationDelay: `${delay}s`
      };
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "min-h-screen w-full bg-black relative overflow-y-auto scrollbar-hide" }, _attrs))} data-v-0b35ec24><div class="absolute inset-0 z-0" data-v-0b35ec24><div class="absolute inset-0 bg-gradient-to-b from-[#0a0a1a] via-[#0d0d2b] to-[#1a0a2e]" data-v-0b35ec24></div><div class="stars-layer-1" data-v-0b35ec24></div><div class="stars-layer-2" data-v-0b35ec24></div><div class="stars-layer-3" data-v-0b35ec24></div><div class="absolute top-1/4 -left-20 w-96 h-96 bg-purple-600/30 rounded-full filter blur-[100px] animate-pulse-slow" data-v-0b35ec24></div><div class="absolute bottom-1/4 -right-20 w-80 h-80 bg-blue-600/20 rounded-full filter blur-[80px] animate-pulse-slow animation-delay-2000" data-v-0b35ec24></div><div class="absolute top-1/2 left-1/3 w-64 h-64 bg-cyan-500/15 rounded-full filter blur-[60px] animate-pulse-slow animation-delay-4000" data-v-0b35ec24></div><div class="warp-stars" data-v-0b35ec24><!--[-->`);
      ssrRenderList(100, (i) => {
        _push(`<div class="warp-star" style="${ssrRenderStyle(getWarpStarStyle(i))}" data-v-0b35ec24></div>`);
      });
      _push(`<!--]--></div><div class="shooting-star shooting-star-1" data-v-0b35ec24></div><div class="shooting-star shooting-star-2" data-v-0b35ec24></div><div class="shooting-star shooting-star-3" data-v-0b35ec24></div><div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px]" data-v-0b35ec24><div class="absolute inset-0 bg-gradient-radial from-purple-500/10 via-transparent to-transparent rounded-full animate-spin-slow" data-v-0b35ec24></div><div class="absolute inset-10 bg-gradient-radial from-cyan-500/10 via-transparent to-transparent rounded-full animate-spin-reverse" data-v-0b35ec24></div></div></div><div class="relative z-10 min-h-screen flex items-center justify-center p-4 lg:p-8 py-12" data-v-0b35ec24><div class="w-full max-w-7xl mx-auto" data-v-0b35ec24>`);
      ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent);
      _push(`</div></div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("layouts/auth.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const auth = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-0b35ec24"]]);

export { auth as default };
//# sourceMappingURL=auth-D-xhbOgu.mjs.map
