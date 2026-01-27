import { _ as _export_sfc, d as useAuthStore, j as __nuxt_component_0$2 } from './server.mjs';
import { defineComponent, computed, mergeProps, unref, useSSRContext } from 'vue';
import { ssrRenderAttrs, ssrRenderStyle, ssrRenderAttr, ssrRenderComponent, ssrRenderClass } from 'vue/server-renderer';
import { _ as _imports_0 } from './virtual_public-CJ1CIvfL.mjs';
import { useRoute, useRouter } from 'vue-router';
import { Icon } from '@iconify/vue';
import '../_/nitro.mjs';
import 'node:http';
import 'node:https';
import 'node:events';
import 'node:buffer';
import 'node:fs';
import 'node:path';
import 'node:url';
import 'node:crypto';
import 'pinia';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'unhead/server';
import 'devalue';
import 'unhead/plugins';
import 'unhead/utils';

const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "index",
  __ssrInlineRender: true,
  setup(__props) {
    const route = useRoute();
    useRouter();
    const authStore = useAuthStore();
    const activeTab = computed(() => {
      const tab = route.query.tab;
      return ["login", "register"].includes(tab) ? tab : "login";
    });
    return (_ctx, _push, _parent, _attrs) => {
      const _component_ClientOnly = __nuxt_component_0$2;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-12 items-center min-h-screen w-full relative py-4 overflow-hidden" }, _attrs))} data-v-69a6d3fe>`);
      if (unref(authStore).isLoading) {
        _push(`<div class="fixed inset-0 bg-black/60 backdrop-blur-md z-50 flex items-center justify-center" data-v-69a6d3fe><div class="bg-white/95 rounded-3xl p-10 shadow-2xl flex flex-col items-center space-y-6" data-v-69a6d3fe><div class="relative w-20 h-20" data-v-69a6d3fe><div class="absolute inset-0 border-4 border-vikinger-purple/30 rounded-full" data-v-69a6d3fe></div><div class="absolute inset-0 border-4 border-transparent border-t-vikinger-purple border-r-vikinger-cyan rounded-full animate-spin" data-v-69a6d3fe></div><div class="absolute inset-2 border-4 border-transparent border-b-vikinger-cyan rounded-full animate-spin" style="${ssrRenderStyle({ "animation-direction": "reverse", "animation-duration": "1s" })}" data-v-69a6d3fe></div></div><div class="text-center" data-v-69a6d3fe><p class="text-gray-800 font-bold text-xl" data-v-69a6d3fe>\u0E01\u0E33\u0E25\u0E31\u0E07\u0E14\u0E33\u0E40\u0E19\u0E34\u0E19\u0E01\u0E32\u0E23...</p><p class="text-gray-500 text-sm mt-1" data-v-69a6d3fe>\u0E42\u0E1B\u0E23\u0E14\u0E23\u0E2D\u0E2A\u0E31\u0E01\u0E04\u0E23\u0E39\u0E48</p></div></div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="flex flex-col items-center lg:items-start text-center lg:text-left space-y-4 lg:space-y-6 relative px-4 lg:px-8" data-v-69a6d3fe><div class="relative group" data-v-69a6d3fe><div class="absolute -inset-4 bg-gradient-to-r from-vikinger-purple to-vikinger-cyan rounded-full blur-xl opacity-50 group-hover:opacity-75 transition-opacity" data-v-69a6d3fe></div><div class="relative w-20 h-20 bg-gradient-to-br from-white to-gray-100 rounded-2xl flex items-center justify-center shadow-2xl transform group-hover:scale-110 transition-transform duration-300" data-v-69a6d3fe><img${ssrRenderAttr("src", _imports_0)} alt="Nuxni Logo" class="w-12 h-12" data-v-69a6d3fe></div></div><div class="space-y-2" data-v-69a6d3fe><h1 class="text-5xl sm:text-6xl lg:text-7xl font-black tracking-tight font-audiowide" data-v-69a6d3fe><span class="bg-clip-text text-transparent bg-gradient-to-r from-white via-vikinger-cyan to-vikinger-purple animate-gradient-x" data-v-69a6d3fe> NUXNAN </span></h1><p class="text-gray-300 max-w-md mx-auto lg:mx-0 text-base lg:text-lg font-medium leading-relaxed" data-v-69a6d3fe> \u0E40\u0E23\u0E35\u0E22\u0E19\u0E43\u0E2B\u0E49\u0E2A\u0E19\u0E38\u0E01 \u0E40\u0E25\u0E48\u0E19\u0E43\u0E2B\u0E49\u0E44\u0E14\u0E49\u0E04\u0E27\u0E32\u0E21\u0E23\u0E39\u0E49 \u0E2A\u0E39\u0E48\u0E01\u0E32\u0E23\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E23\u0E32\u0E22\u0E44\u0E14\u0E49 </p></div><div class="flex flex-wrap gap-2 justify-center lg:justify-start" data-v-69a6d3fe><div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white/10 backdrop-blur-sm border border-white/20" data-v-69a6d3fe>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:book-24-regular",
        class: "w-4 h-4 text-vikinger-cyan"
      }, null, _parent));
      _push(`<span class="text-white text-xs font-medium" data-v-69a6d3fe>\u0E40\u0E23\u0E35\u0E22\u0E19\u0E2D\u0E2D\u0E19\u0E44\u0E25\u0E19\u0E4C</span></div><div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white/10 backdrop-blur-sm border border-white/20" data-v-69a6d3fe>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:games-24-regular",
        class: "w-4 h-4 text-vikinger-purple"
      }, null, _parent));
      _push(`<span class="text-white text-xs font-medium" data-v-69a6d3fe>\u0E40\u0E01\u0E21\u0E01\u0E32\u0E23\u0E40\u0E23\u0E35\u0E22\u0E19\u0E23\u0E39\u0E49</span></div><div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white/10 backdrop-blur-sm border border-white/20" data-v-69a6d3fe>`);
      _push(ssrRenderComponent(unref(Icon), {
        icon: "fluent:money-24-regular",
        class: "w-4 h-4 text-yellow-400"
      }, null, _parent));
      _push(`<span class="text-white text-xs font-medium" data-v-69a6d3fe>\u0E2A\u0E23\u0E49\u0E32\u0E07\u0E23\u0E32\u0E22\u0E44\u0E14\u0E49</span></div></div><div class="flex gap-2 bg-white/10 backdrop-blur-md rounded-xl p-2 border border-white/20 shadow-xl" data-v-69a6d3fe><button class="${ssrRenderClass([
        activeTab.value === "login" ? "bg-gradient-to-r from-vikinger-purple to-vikinger-blue text-white shadow-lg scale-100" : "text-white/80 hover:bg-white/10 hover:text-white",
        "flex-1 px-6 sm:px-10 py-2.5 rounded-lg font-bold transition-all duration-300 text-sm"
      ])}" data-v-69a6d3fe><span class="relative z-10" data-v-69a6d3fe>\u0E40\u0E02\u0E49\u0E32\u0E2A\u0E39\u0E48\u0E23\u0E30\u0E1A\u0E1A</span></button><button class="${ssrRenderClass([
        activeTab.value === "register" ? "bg-gradient-to-r from-vikinger-cyan to-vikinger-purple text-white shadow-lg scale-100" : "text-white/80 hover:bg-white/10 hover:text-white",
        "flex-1 px-6 sm:px-10 py-2.5 rounded-lg font-bold transition-all duration-300 text-sm"
      ])}" data-v-69a6d3fe><span class="relative z-10" data-v-69a6d3fe>\u0E2A\u0E21\u0E31\u0E04\u0E23\u0E2A\u0E21\u0E32\u0E0A\u0E34\u0E01</span></button></div><div class="hidden lg:grid grid-cols-3 gap-4 w-full max-w-sm" data-v-69a6d3fe><div class="text-center" data-v-69a6d3fe><div class="text-2xl font-black text-white" data-v-69a6d3fe>10K+</div><div class="text-gray-400 text-xs" data-v-69a6d3fe>\u0E1C\u0E39\u0E49\u0E43\u0E0A\u0E49\u0E07\u0E32\u0E19</div></div><div class="text-center" data-v-69a6d3fe><div class="text-2xl font-black text-white" data-v-69a6d3fe>500+</div><div class="text-gray-400 text-xs" data-v-69a6d3fe>\u0E1A\u0E17\u0E40\u0E23\u0E35\u0E22\u0E19</div></div><div class="text-center" data-v-69a6d3fe><div class="text-2xl font-black text-white" data-v-69a6d3fe>4.9</div><div class="text-gray-400 text-xs" data-v-69a6d3fe>\u0E04\u0E30\u0E41\u0E19\u0E19</div></div></div></div><div class="flex justify-center lg:justify-end relative px-4 lg:px-8" data-v-69a6d3fe>`);
      _push(ssrRenderComponent(_component_ClientOnly, null, {}, _parent));
      _push(`</div></div>`);
    };
  }
});
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/auth/index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const index = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-69a6d3fe"]]);

export { index as default };
//# sourceMappingURL=index-rWAAgq8z.mjs.map
